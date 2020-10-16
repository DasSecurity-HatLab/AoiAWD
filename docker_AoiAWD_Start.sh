# !/bin/sh
if [ "`docker image ls | grep aoiawd_aoiawd`" ];then
        printf "\e\033[33m [!] There is already a image here, do you plan to rebuild it ?(y/N) \033[0m"
        read choice
        if [ "$choice" = 'y' -o "$choice" = 'Y' ];then
            printf "\e\033[32m [+] OK , I'll rebuild it. \033[0m\n"
            docker-compose stop 2>&1 && echo y | docker-compose rm 2>&1
            BUILD_STATUS=`docker-compose build | awk 'END {print}'`
            if [ "$BUILD_STATUS" = "Successfully tagged aoiawd_aoiawd:latest" ];then
                printf "\e\033[32m [+] Rebuild complete! \033[0m\n"
            else
                printf "\e\033[31m [-] Rebuild Failed!"
                exit -1
            fi
        fi
    else
        printf "\e\033[32m [+] Building image. \033[0m\n"
        BUILD_STATUS=`docker-compose build | awk 'END {print}'`
        if [ "$BUILD_STATUS" = "Successfully tagged aoiawd_aoiawd:latest" ];then
            printf "\e\033[32m [+] Build complete! \033[0m\n"
        else
            printf "\e\033[31m [-] Build Failed! \033[0m\n"
            exit -1
        fi
fi

printf "\e\033[32m [+] Then , I'll start the container. \033[0m\n"
RUN_STATUS=`docker-compose up -d 2>&1| awk 'END {print}'`
if [[ "$RUN_STATUS" =~ "done" ]];then
    printf "\e\033[32m [+] Run complete! \033[0m\n"
elif [[ "$RUN_STATUS" =~ "up-to-date" ]];then
    printf "\e\033[33m [!] There is already a container here, do you plan to rerun it ?(y/N) \033[0m"
    read choice
    if [ "$choice" = 'y' -o "$choice" = 'Y' ];then
        printf "\e\033[32m [+] OK , I'll rerun it. \033[0m\n"
        printf "\e\033[32m [+] Deleting... \033[0m\n"
        DELETE_STATUS=`docker-compose stop 2>&1 && echo y | docker-compose rm 2>&1| awk 'END {print}'`
        if [[ "$DELETE_STATUS" =~ "done" ]];then
            printf "\e\033[32m [+] Delete complete! \033[0m\n"
            RERUN_STATUS=`docker-compose up -d  2>&1| awk 'END {print}'`
            if [[ "$RERUN_STATUS" =~ "done" ]];then
                printf "\e\033[32m [+] Rerun complete! \033[0m\n"
            else
                printf "\e\033[31m [-] Rerun Failed! \033[0m\n"
                exit -1
            fi
        else
            printf "\e\033[31m [-] Delete Failed!"
            exit -1
        fi
    fi
else
    printf "\e\033[31m [-] Run Failed! \033[0m\n"
    exit -1
fi

printf "\e\033[32m [+] Finally , I'll get the key. \033[0m\n"
DOCKER_INFO=`docker ps | grep aoiawd_aoiawd`
DOCKER_INFO_ARR=($DOCKER_INFO)
KEY=`docker logs ${DOCKER_INFO_ARR[0]} | tail -n 15 | grep AccessToken | cut -d : -f 5 | cut -d [ -f 1 | sed 's/[[:space:]]//g'`
printf "\e\033[32m [+]AccessToken is ${KEY} \033[0m\n"
printf "\e\033[32m [+]ALL DONE!! \033[0m\n"