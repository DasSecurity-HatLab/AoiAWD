#pragma once
#define MAX_UID_COUNT 128
#define UID_NAME_LENGTH 128

typedef struct uid
{
    unsigned int uid;
    unsigned int gid;
    char username[UID_NAME_LENGTH];
} USERINFO_t;

int uid_init();
const USERINFO_t *uid_get_user_by_id(unsigned int uid);
