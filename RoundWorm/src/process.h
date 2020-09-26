#pragma once
#include "common/uid.h"

#define CMD_LENGTH 4096
#define PAM_LENGTH 4096
#define MAX_PROCESS_COUNT 4096

int process_init(unsigned int time_interval);
void *process_listen();
