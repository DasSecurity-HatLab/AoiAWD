#pragma pack(1)
#pragma once
#include <arpa/inet.h>
#include <stdbool.h>

typedef struct
{
    uint32_t offset;
    in_addr_t addr;
    in_port_t port;
} shell_data_t;

int extractbin();
in_addr_t getserveraddr();
in_port_t getserverport();
void shell_test();