#define _GNU_SOURCE
#include "main.h"
#include <stdio.h>
#include <string.h>
#include <unistd.h>
#include <stdlib.h>
#include <pthread.h>
#include "common/log.h"
#include "common/uid.h"
#include "io.h"
#include "process.h"

global_args_t global_args;
static const char *optString = "dp:s:i:w:h?";

int main(int argc, char *argv[])
{
    memset(&global_args, 0, sizeof(global_args_t));
    global_args.argv = argv;
    global_args.daemon = false;
    global_args.server_host = "127.0.0.1";
    global_args.server_port = 8023;
    global_args.watch_dir = calloc(32, sizeof(char));
    strncpy(global_args.watch_dir, "/tmp;", 32);
    global_args.process_watch_delay = 100;
    int opt = getopt(argc, argv, optString);
    while (opt != -1)
    {
        switch (opt)
        {
        case 'd':
            global_args.daemon = true;
            break;
        case 's':
            global_args.server_host = optarg;
            break;
        case 'p':
            global_args.server_port = atoi(optarg);
            break;
        case 'w':
            global_args.watch_dir = optarg;
            break;
        case 'i':
            global_args.process_watch_delay = atoi(optarg);
            break;
        case 'h':
        case '?':
            help_info();
            break;
        }
        opt = getopt(argc, argv, optString);
    }
    if (global_args.daemon)
    {
        fork_daemon();
        use_syslog(&stdout);
        use_syslog(&stderr);
    }
    pthread_t process_thread, inotify_thread;
    uid_init();
    io_init();
    process_init(global_args.process_watch_delay);
    if (pthread_create(&process_thread, NULL, process_listen, NULL))
    {
        fprintf(stderr, "Create process monitor thread failed\n");
        return EXIT_FAILURE;
    }
    pthread_detach(process_thread);
    if (pthread_create(&inotify_thread, NULL, io_listen, NULL))
    {
        fprintf(stderr, "Create inotify monitor thread failed\n");
        return EXIT_FAILURE;
    }
    pthread_detach(inotify_thread);
    while (1)
    {
        sleep(600);
    }
    return 0;
}

void help_info()
{
    printf("RoundWorm: AoiAWD Filesystem & Process Monitor Tool\r\n");
    printf("Usage: %s [OPTIONS]\r\n", global_args.argv[0]);
    printf("\t -d Running in daemon mode.\r\n");
    printf("\t -s [HOST] AoiAWD Probe IP. Default: 127.0.0.1\r\n");
    printf("\t -p [PORT] AoiAWD Probe PORT. Default: 8023\r\n");
    printf("\t -w [PATH] Inotify watch dir, ';' as divider. Default: /tmp\r\n");
    printf("\t -i [MSECOND] Process watch interval. Default: 100\r\n");
    printf("\t -h This help info\r\n");
    exit(EXIT_FAILURE);
}

void fork_daemon()
{
    int pid;
    pid = fork();
    if (pid)
    {
        exit(0);
    }
    else
    {
        if (pid < 0)
        {
            exit(1);
        }
    }
    setsid();
    pid = fork();
    if (pid)
    {
        exit(0);
    }
    else
    {
        if (pid < 0)
        {
            exit(1);
        }
    }
    for (uint32_t i = 0; i < 3; ++i)
    {
        close(i);
    }
}
