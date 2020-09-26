#define _GNU_SOURCE
#include "log.h"
#include <string.h>
#include <syslog.h>

static size_t writer(void *cookie, char const *data, size_t len);

static cookie_io_functions_t log_fns = {
    NULL,
    (void *)writer,
    NULL,
    NULL};

static size_t writer(void *cookie, char const *data, size_t len)
{
    (void)cookie;
    syslog(6, "%.*s", (int)len, data);
    return len;
}

void use_syslog(FILE **pfp)
{
    *pfp = fopencookie(NULL, "w", log_fns);
    setvbuf(*pfp, NULL, _IOLBF, 1024);
}
