#define _GNU_SOURCE
#include "process.h"
#include <stdlib.h>
#include <string.h>
#include <time.h>
#include <dirent.h>
#include <unistd.h>
#include <stdio.h>
#include <errno.h>
#include "common/sender.h"

typedef struct process
{
    unsigned int pid;
    unsigned int ppid;
    char cmdline[CMD_LENGTH];
    char param[PAM_LENGTH];
    USERINFO_t user;
} PROCESS_t;

typedef struct
{
    size_t count;
    unsigned int pid_snapshot[MAX_PROCESS_COUNT];
} PROCESS_SNAPSHOT_t;

static unsigned int interval = 0;
static PROCESS_SNAPSHOT_t snapshot;
static SENDER_t *sender;

int process_update();

PROCESS_t *process_get_proc_info(unsigned int pid)
{
    static char virtual_name[256];
    static char proc_addr[32];
    static char line[256];

    memset(virtual_name, 0, 256);
    memset(proc_addr, 0, 256);
    memset(line, 0, 256);

    PROCESS_t *process_info = calloc(1, sizeof(PROCESS_t));
    process_info->pid = pid;

    snprintf(proc_addr, 31, "/proc/%u/status", pid);
    FILE *process_fp = fopen(proc_addr, "r");
    if (process_fp == NULL)
    {
        // fprintf(stderr, "can't open process %u", pid);
        free(process_info);
        return NULL;
    }

    while (fgets(line, 255, process_fp) != NULL)
    {
        if (strstr(line, "Name:") != NULL)
        {
            sscanf(line, "Name: %s", virtual_name);
            continue;
        }
        else if (strstr(line, "PPid:") != NULL)
        {
            sscanf(line, "PPid: %u", &process_info->ppid);
            continue;
        }
        else if (strstr(line, "Uid:") != NULL)
        {
            unsigned int uid;
            sscanf(line, "Uid: %u", &uid);
            USERINFO_t const *user = uid_get_user_by_id(uid);
            if (user == NULL)
            {
                // printf("can't find user %u\n", uid);
                memset(&process_info->user, 0, sizeof(USERINFO_t)); // 查找用户失败，使用0填充
            }
            else
            {
                memcpy(&process_info->user, user, sizeof(USERINFO_t));
            }
        }
    }
    fclose(process_fp);

    // 获取cmdline
    snprintf(proc_addr, 31, "/proc/%u/cmdline", pid);
    process_fp = fopen(proc_addr, "rb");
    if (process_fp == NULL)
    {
        // fprintf(stderr, "can't open process %u", pid);
        free(process_info);
        return NULL;
    }

    char *arg = 0;
    size_t arg_size = 0;
    if (getdelim(&arg, &arg_size, 0, process_fp) == -1)
    {
        strncpy(process_info->cmdline, virtual_name, CMD_LENGTH);
    }
    else
    {
        strncpy(process_info->cmdline, arg, CMD_LENGTH);
        while (getdelim(&arg, &arg_size, 0, process_fp) != -1)
        {
            strncat(process_info->param, arg, PAM_LENGTH);
            strncat(process_info->param, " ", PAM_LENGTH);
        }
    }
    free(arg);
    fclose(process_fp);
    return process_info;
}

int process_init(unsigned int time_interval)
{
    memset(&snapshot, 0, sizeof(PROCESS_SNAPSHOT_t));
    interval = time_interval * 1000;
    sender = sender_init();
    if (sender == NULL)
    {
        fprintf(stderr, "can't create report socket\n");
        fprintf(stderr, "%s\n", strerror(errno));
        exit(0);
    }
    return 1;
}

void *process_listen()
{
    while (1)
    {
        // printf(".");
        // fflush(stdout);
        usleep(interval);
        process_update();
    }
}

int _process_search(PROCESS_SNAPSHOT_t const *snapshot, unsigned int target_pid)
{

    int begin = 0;
    int end = snapshot->count;
    int target = snapshot->count / 2;
    while (1)
    {
        if (begin > end)
        {
            // 找不到了
            return 0;
        }
        if (snapshot->pid_snapshot[target] > target_pid)
        {
            end = target - 1;
            target = (begin + end) / 2;
        }
        else if (snapshot->pid_snapshot[target] < target_pid)
        {
            begin = target + 1;
            target = (end + begin) / 2;
        }
        else
        {
            return 1; // 找到了
        }
    }
}

int process_update()
{
    PROCESS_SNAPSHOT_t current_snapshot;
    memset(&current_snapshot, 0, sizeof(PROCESS_SNAPSHOT_t));
    DIR *dir;
    struct dirent *ptr;
    unsigned int pid;
    char *json_buffer;
    int flag = 0; // 汇报flag
    dir = opendir("/proc");
    while ((ptr = readdir(dir)) != NULL)
    {
        if (sscanf(ptr->d_name, "%d", &pid) == 0)
        {
            continue;
        }
        PROCESS_t *process = process_get_proc_info(pid);
        if (process == NULL)
        {
            // printf("错误\n");
            continue;
        }
        else
        {
            current_snapshot.pid_snapshot[current_snapshot.count] = pid;
            current_snapshot.count++;
            // 检查pid是否是新创建的
            int result = _process_search(&snapshot, pid);
            if (!result)
            {
                int ret = asprintf(&json_buffer, "{\"type\":\"new_process\", \"data\":{\"pid\":%u,\"ppid\":%u,\"uid\":%u,\"username\":\"%s\",\"cmd\":\"%s\",\"param\":\"%s\"}}\n", process->pid, process->ppid, process->user.uid, process->user.username, process->cmdline, process->param);
                if (ret == -1)
                {
                    fputs("error occured when serializing json", stderr);
                }
                else
                {
                    sender_send(sender, json_buffer);
                    free(json_buffer);
                }
                flag = 1;
            }
            free(process);
        }
    }
    closedir(dir);

    // 最后切换进程快照
    memcpy(&snapshot, &current_snapshot, sizeof(PROCESS_SNAPSHOT_t));
    // 发送当前pid
    if (flag)
    {
        char buffer[16];
        if (-1 == asprintf(&json_buffer, "{\"type\":\"pid_list\",\"data\":[%u", snapshot.pid_snapshot[0]))
        {
            return 0;
        }
        sender_send(sender, json_buffer);
        free(json_buffer);
        for (size_t index = 1; index < snapshot.count; index++)
        {
            snprintf(buffer, 16, ",%u", snapshot.pid_snapshot[index]);
            sender_send(sender, buffer);
        }
        sender_send(sender, "]}\n");
    }
    return 0;
}