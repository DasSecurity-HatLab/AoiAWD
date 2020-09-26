#pragma once
#include <sys/socket.h>
#include <stdbool.h>

typedef struct sender
{
    int socket;
    bool connected;
} SENDER_t;

SENDER_t *sender_init();
void sender_destory(SENDER_t *instance);
void sender_send(SENDER_t *instance, char *buffer);
