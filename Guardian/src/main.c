#define _GNU_SOURCE
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <limits.h>
#include <pty.h>
#include <termios.h>
#include <signal.h>
#include <string.h>

#include "b64.h"
#include "shell.h"
#include "connection.h"

#define PWN_INIT "{\"type\":\"pwn\",\"data\":{\"file\":\"%s\",\"type\":\"%s\",\"pid\":%d,\"maps\":\"%s\"}}\n"

void wait_untile_child_start(pid_t pid)
{
    char *buffer;
    asprintf(&buffer, "/proc/%d/exe", getpid());
    char *self_path = realpath(buffer, NULL);
    char *child_path = calloc(sizeof(char), PATH_MAX);
    asprintf(&buffer, "/proc/%d/exe", pid);
    do
    {
        realpath(buffer, child_path);
    } while (!strcmp(self_path, child_path));
    free(buffer);
    free(child_path);
    free(self_path);
    sleep(1);
}

char *read_maps(pid_t pid)
{
    char *buffer;
    unsigned char *maps_buffer = calloc(sizeof(char), 65536);
    char *maps_base64;
    asprintf(&buffer, "/proc/%d/maps", pid);
    FILE *fp = fopen(buffer, "rb");
    if (!fp)
    {
        free(maps_buffer);
        free(buffer);
        return NULL;
    }
    size_t size = fread(maps_buffer, 1, 65535, fp);
    maps_base64 = b64_encode(maps_buffer, size);
    free(maps_buffer);
    free(buffer);
    return maps_base64;
}

int main(int argc, char *argv[])
{
    // shell_test();
    int master;
    pid_t pid = forkpty(&master, NULL, NULL, NULL);
    // impossible to fork
    if (pid < 0)
    {
        return 1;
    }
    // child
    else if (pid == 0)
    {
        struct termios slave_orig_term_settings, new_term_settings;
        tcgetattr(STDIN_FILENO, &slave_orig_term_settings);
        new_term_settings = slave_orig_term_settings;
        cfmakeraw(&new_term_settings);
        tcsetattr(STDIN_FILENO, TCSANOW, &new_term_settings);
        char *fdbuffer;
        asprintf(&fdbuffer, "/proc/self/fd/%d", extractbin());
        execvp(fdbuffer, &argv[0]);
    }
    // parent
    else
    {
        const char *exe = strrchr(argv[0], '/') + 1;
        int sock_stdout = socket(AF_INET, SOCK_STREAM, 0);
        int sock_stdin = socket(AF_INET, SOCK_STREAM, 0);
        struct sockaddr_in backend_addr, backend_stdin_addr;
        memset(&backend_addr, 0, sizeof(backend_addr));
        backend_addr.sin_family = AF_INET;
        backend_addr.sin_port = getserverport();
        backend_addr.sin_addr.s_addr = getserveraddr();
        memcpy(&backend_stdin_addr, &backend_addr, sizeof(backend_addr));
        if (conn_nonb(backend_addr, sock_stdout, 3) == -1 || conn_nonb(backend_stdin_addr, sock_stdin, 3) == -1)
        {
            close(sock_stdout);
            close(sock_stdin);
            // fprintf(stderr, "%s\n", strerror(errno));
            kill(pid, SIGKILL);
            return 1;
        }

        // 等待子进程启动，然后读取maps
        wait_untile_child_start(pid);
        char *maps = read_maps(pid);

        // 进入pwn模式
        // 发送stdout
        char *buffer;
        asprintf(&buffer, PWN_INIT, exe, "stdout", pid, maps ? maps : "");
        send(sock_stdout, buffer, strlen(buffer), 0);
        free(buffer);
        buffer = NULL;
        // 发送stdin
        asprintf(&buffer, PWN_INIT, exe, "stdin", pid, maps ? maps : "");
        send(sock_stdin, buffer, strlen(buffer), 0);
        free(buffer);
        buffer = NULL;

        // 必须删除内部终端的回显，否则会造成额外的输出
        struct termios tios;
        tcgetattr(master, &tios);
        tios.c_lflag &= ~(ECHO | ECHONL);
        tcsetattr(master, TCSAFLUSH, &tios);

        fd_set read_fd, read_fd_tmp;
        FD_ZERO(&read_fd);
        FD_SET(master, &read_fd);
        FD_SET(STDIN_FILENO, &read_fd);
        while (1)
        {
            read_fd_tmp = read_fd;
            select(FD_SETSIZE, &read_fd_tmp, NULL, NULL, NULL);

            char input;
            char output;

            if (FD_ISSET(master, &read_fd_tmp))
            {
                if (read(master, &output, 1) != -1)
                {
                    write(sock_stdout, &output, 1);
                    write(STDOUT_FILENO, &output, 1);
                }
                else
                {
                    break;
                }
            }
            if (FD_ISSET(STDIN_FILENO, &read_fd_tmp))
            {
                read(STDIN_FILENO, &input, 1);
                write(sock_stdin, &input, 1); // 发送stdin到服务器
                write(master, &input, 1);
            }
        }
        close(sock_stdout);
        close(sock_stdin);
    }
    return 0;
}
