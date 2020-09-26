export const getWsState = state => {
    return state.wsState;
}

export const getCountAlert = state => {
    return state.count_alert;
}

export const getLastUpdateTime = state => {
    return state.timestamp_lastupdate;
} 

export const getRunningTime = state => {
    return state.timestamp_runningtime;
}

export const getFileLog = state => {
    return state.fileLog;
}

export const getWebLog = state => {
    return state.webLog;
}

export const getProcessLog = state => {
    return state.processLog;
}

export const getWarnLog = state => {
    return state.warnLog;
}

export const getPwnLog = state => {
    return state.pwnLog;
}
