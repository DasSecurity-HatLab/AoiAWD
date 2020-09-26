#include <stdlib.h>
#include <stdio.h>
#include <string.h>
#include "uid.h"

static USERINFO_t user_list[MAX_UID_COUNT];
static size_t user_amount = 0;

int uid_init()
{
    // 获取uid信息
    FILE *fp = fopen("/etc/passwd", "r");
    if (!fp)
    {
        fprintf(stderr, "Can't read passwd file");
        return EXIT_FAILURE;
    }
    char line[128];
    char username[UID_NAME_LENGTH];
    memset(username, 0, UID_NAME_LENGTH);
    int uid = 0;
    int gid = 0;
    size_t index = 0;
    while (fgets(line, 127, fp) != NULL)
    {
        sscanf(line, "%[^:]:x:%d:%d", username, &uid, &gid);
        // printf("(%d, %d) ==> %s\n", uid, gid, username);
        user_list[index].uid = uid;
        user_list[index].gid = gid;
        strncpy(user_list[index].username, username, UID_NAME_LENGTH);
        index++;
    }
    fclose(fp);
    user_amount = index;
    return 1;
}

const USERINFO_t *uid_get_user_by_id(unsigned int uid)
{
    for (size_t index = 0; index < user_amount; index++)
    {
        if (user_list[index].uid == uid)
        {
            return &user_list[index];
        }
    }
    return NULL;
}