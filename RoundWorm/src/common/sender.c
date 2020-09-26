#include "sender.h"
#include <stdlib.h>
#include <string.h>
#include <stdio.h>
#include <sys/types.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <arpa/inet.h>
#include <unistd.h>
#include <errno.h>
#include <arpa/inet.h>
#include "main.h"

SENDER_t *sender_init()
{
    SENDER_t *instance = calloc(1, sizeof(SENDER_t));
    if (instance == NULL)
    {
        fprintf(stderr, "can't create sender instance\n");
        free(instance);
        return NULL;
    }
    instance->socket = socket(AF_INET, SOCK_STREAM, IPPROTO_TCP);
    if (instance->socket == -1)
    {
        fprintf(stderr, "can't create socket\n");
        free(instance);
        return NULL;
    }
    if (setsockopt(instance->socket, SOL_SOCKET, SO_REUSEADDR, &(int){1}, sizeof(int)) < 0)
    {
        close(instance->socket);
        fprintf(stderr, "can't reuse socket\n");
        free(instance);
        return NULL;
    }
    if (setsockopt(instance->socket, SOL_SOCKET, SO_KEEPALIVE, &(int){1}, sizeof(int)) < 0)
    {
        close(instance->socket);
        fprintf(stderr, "can't keepalive socket\n");
        free(instance);
        return NULL;
    }
    struct sockaddr_in report_addr;
    memset(&report_addr, 0, sizeof(report_addr));
    report_addr.sin_port = htons(global_args.server_port);
    report_addr.sin_family = AF_INET;
    report_addr.sin_addr.s_addr = inet_addr(global_args.server_host);
    if (connect(instance->socket, (struct sockaddr *)&report_addr, sizeof(struct sockaddr)) == -1)
    {
        close(instance->socket);
        free(instance);
        return NULL;
    }
    instance->connected = true;
    return instance;
}

void sender_send(SENDER_t *instance, char *buffer)
{
    if (instance == NULL)
    {
        fputs("inavlid sender instance\n", stderr);
        return;
    }
    static struct sockaddr peeraddr;
    static socklen_t peerlen;
    if (instance->connected)
    {
        if (getpeername(instance->socket, &peeraddr, &peerlen) == -1)
        {
            close(instance->socket);
            instance->connected = false;
        }
        else
        {
            send(instance->socket, buffer, strlen(buffer), 0);
        }
    }
    else
    {
        SENDER_t *newsender = sender_init();
        if (newsender == NULL)
        {
            return;
        }
        instance->socket = newsender->socket;
        instance->connected = true;
        free(newsender);
    }
}

void sender_destory(SENDER_t *instance)
{
    close(instance->socket);
    free(instance);
}
