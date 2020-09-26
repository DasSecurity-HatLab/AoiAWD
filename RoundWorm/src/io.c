#define _GNU_SOURCE
#include "io.h"
#include <inotifytools/inotify.h>
#include <inotifytools/inotifytools.h>
#include <string.h>
#include <stdlib.h>
#include <stdio.h>
#include <errno.h>
#include <sys/stat.h>
#include <errno.h>
#include "common/sender.h"
#include "common/b64.h"
#include "main.h"

int _io_is_dir(char const *path);
#define nasprintf(...) niceassert(-1 != asprintf(__VA_ARGS__), "out of memory")
#define niceassert(cond, mesg) \
    _io_niceassert((long)cond, __LINE__, __FILE__, #cond, mesg)
void _io_niceassert(long cond, int line, char const *file, char const *condstr, char const *mesg);

static int events = IN_CREATE | IN_ATTRIB | IN_MODIFY | IN_CLOSE_WRITE | IN_DELETE | IN_DELETE_SELF;
static SENDER_t *sender;

int io_init()
{
    if (!inotifytools_initialize())
    {
        int error = inotifytools_error();
        fprintf(stderr, "Couldn't initialize inotify: %s\n", strerror(error));
        if (error == EMFILE)
        {
            fprintf(stderr, "Try increasing the value of "
                            "/proc/sys/fs/inotify/max_user_instances\n");
        }
        return EXIT_FAILURE;
    }
    char *delim = ";";
    char *p;
    p = strtok(global_args.watch_dir, delim);
    inotifytools_watch_recursively(p, events | IN_CREATE | IN_MOVED_TO | IN_MOVED_FROM | IN_ISDIR);
    while (p != NULL)
    {
        inotifytools_watch_recursively(p, events | IN_CREATE | IN_MOVED_TO | IN_MOVED_FROM | IN_ISDIR);
        p = strtok(NULL, delim);
    }
    sender = sender_init();
    if (sender == NULL)
    {
        fprintf(stderr, "can't initialize sender\n");
        fprintf(stderr, "%s\n", strerror(errno));
        exit(0);
    }
    return 1;
}

void _io_report(struct inotify_event *event)
{
    char *path;
    unsigned char file_buffer[4096];
    long filesize = 0;
    struct stat my_stat;

    memset(file_buffer, 0, 4096);
    // printf("%s%s   type:%d\n", event->mask);
    if (-1 == asprintf(&path, "%s%s", inotifytools_filename_from_wd(event->wd), event->name))
    {
        return;
    }
    // 检查文件是否为目录
    if (stat(path, &my_stat) == 0)
    {
        if (S_ISREG(my_stat.st_mode))
        {
            // 不是文件夹就判断大小
            if (my_stat.st_size < 4096)
            {
                // printf("%s: %ld\n", path, my_stat.st_size);
                FILE *fp = fopen(path, "rb");
                if (fp == NULL)
                {
                    // fprintf(stderr, "faild to open %s\n", path);
                    filesize = 0;
                }
                else
                {
                    fread(file_buffer, 1, 4096, fp);
                    fclose(fp);
                    filesize = my_stat.st_size;
                }
            }
        }
    }
    // 拼接json
    char *buffer;
    char *file_b64_buffer;

    file_b64_buffer = b64_encode(file_buffer, filesize);

    if (asprintf(&buffer, "{\"type\":\"file\",\"data\":{\"path\":\"%s\",\"mode\":%u,\"event\":%d,\"size\":%ld,\"content\":\"%s\"}}\n", path, my_stat.st_mode, event->mask, my_stat.st_size, file_b64_buffer) == -1)
    {
        free(path);
        return;
    }
    sender_send(sender, buffer);

    free(file_b64_buffer);
    free(buffer);
    free(path);
}

void *io_listen()
{

    int orig_events = events;
    struct inotify_event *event;
    char *moved_from = 0;
    do
    {
        // printf("!");
        // fflush(stdout);
        event = inotifytools_next_event(120);
        if (!event)
        {
            if (!inotifytools_error())
            {
                printf("%s\n", "inotify event timeout");
                continue;
            }
            else
            {
                printf("%s\n", strerror(inotifytools_error()));
                break;
            }
        }
        if ((event->mask & orig_events))
        {
            _io_report(event);
        }
        if (moved_from && !(event->mask & IN_MOVED_TO))
        {
            if (!inotifytools_remove_watch_by_filename(moved_from))
            {
                printf("Error removing watch on %s: %s\n",
                       moved_from, strerror(inotifytools_error()));
            }
            free(moved_from);
            moved_from = 0;
        }

        if ((event->mask & IN_CREATE) ||
            (!moved_from && (event->mask & IN_MOVED_TO)))
        {
            // New file - if it is a directory, watch it
            static char *new_file;

            nasprintf(&new_file, "%s%s",
                      inotifytools_filename_from_wd(event->wd),
                      event->name);

            if (_io_is_dir(new_file) &&
                !inotifytools_watch_recursively(new_file, events))
            {
                printf("Couldn't watch new directory %s: %s\n",
                       new_file, strerror(inotifytools_error()));
            }
            // _io_report(event);
            free(new_file);
        } // IN_CREATE
        else if (event->mask & IN_MOVED_FROM)
        {
            nasprintf(&moved_from, "%s%s/",
                      inotifytools_filename_from_wd(event->wd),
                      event->name);
            // if not watched...
            if (inotifytools_wd_from_filename(moved_from) == -1)
            {
                free(moved_from);
                moved_from = 0;
            }
        } // IN_MOVED_FROM
        else if (event->mask & IN_MOVED_TO)
        {
            if (moved_from)
            {
                static char *new_name;
                nasprintf(&new_name, "%s%s/",
                          inotifytools_filename_from_wd(event->wd),
                          event->name);
                inotifytools_replace_filename(moved_from, new_name);
                free(moved_from);
                moved_from = 0;
            } // moved_from
        }
        fflush(NULL);
    } while (1);
    return NULL;
}

int _io_is_dir(char const *path)
{
    static struct stat my_stat;
    if (-1 == stat(path, &my_stat))
    {
        if (errno == ENOENT)
            return 0;
        fprintf(stderr, "Stat failed on %s: %s\n", path, strerror(errno));
        return 0;
    }
    return S_ISDIR(my_stat.st_mode) && !S_ISLNK(my_stat.st_mode);
}

void _io_niceassert(long cond, int line, char const *file, char const *condstr, char const *mesg)
{
    if (cond)
        return;

    if (mesg)
    {
        fprintf(stderr, "%s:%d assertion ( %s ) failed: %s\n", file, line,
                condstr, mesg);
    }
    else
    {
        fprintf(stderr, "%s:%d assertion ( %s ) failed.\n", file, line,
                condstr);
    }
}