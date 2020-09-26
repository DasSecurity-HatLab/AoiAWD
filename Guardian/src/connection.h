#pragma once
#include <sys/select.h>
#include <sys/wait.h>
#include <arpa/inet.h>
#include <sys/types.h>
#include <sys/socket.h>
#include <errno.h>
#include <fcntl.h>

int conn_nonb(struct sockaddr_in sa, int sock, int timeout);
