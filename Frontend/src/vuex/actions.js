export const openWs = ({commit}) => {
    commit('OPEN_WS');
}

export const closeWs = ({commit}) => {
    commit('CLOSE_WS');
}

export const setCount = ({commit}, {count}) => {
    commit('SET_COUNT_ALERT', count);
}

export const setUpdateTime = ({commit}, {time}) => {
    commit('SET_TIMESTAMP_LASTUPDATE', time);
}

export const setRunningTime = ({commit}, {time}) => {
    commit('SET_TIMESTAMP_RUNNINGTIME', time);
} 

export const setFileLog = ({commit}, {logs}) => {
    commit('SET_FILE_LOG', logs);
}

export const setWebLog = ({commit}, {logs}) => {
    commit('SET_WEB_LOG', logs);
}

export const setProcessLog = ({commit}, {logs}) => {
    commit('SET_PROCESS_LOG', logs);
}

export const setWarnLog = ({commit}, {logs}) => {
    commit('SET_WARN_LOG', logs);
}

export const setPwnLog = ({commit}, {logs}) => {
    commit('SET_PWN_LOG', logs);
}
