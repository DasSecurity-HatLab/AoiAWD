#pragma once
#include <stdbool.h>
#include <inttypes.h>

typedef struct
{
    char **argv;
    bool daemon;
    char *server_host;
    uint16_t server_port;
    char *watch_dir;
    uint32_t process_watch_delay;
} global_args_t;

extern global_args_t global_args;

int main(int argc, char *argv[]);
void help_info();
void fork_daemon();