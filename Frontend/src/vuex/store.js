import Vue from 'vue'
import Vuex from 'vuex'
import * as actions from './actions'
import * as getters from './getters'

Vue.use(Vuex)

// 应用初始状态
const state = {
    // count: 10
    wsState: false,
    count_alert: 0,
    timestamp_lastupdate: 0,
    timestamp_runningtime: 0,
    webLog: [],
    fileLog: [],
    warnLog: [],
    processLog: [],
    pwnLog: [],
}

// 定义所需的 mutations
const mutations = {
    OPEN_WS() {
        state.wsState = true;
    },
    CLOSE_WS() {
        state.wsState = false;
    },
    SET_COUNT_ALERT(state, count) {
        state.count_alert = count;
    },
    SET_TIMESTAMP_LASTUPDATE(state, time) {
        state.timestamp_lastupdate = time;
    },
    SET_TIMESTAMP_RUNNINGTIME(state, time) {
        state.timestamp_runningtime = time;
    },
    SET_WEB_LOG(state, logs) {
        state.webLog = logs;
    },
    SET_FILE_LOG(state, logs) {
        logs.forEach(e => {
            switch (e.oper) {
                case 'CREATE':
                    e.oper_str = "新建文件";
                    break;
                case 'CLOSE_WRITE':
                    e.oper_str = "写入退出";
                    break;
                case 'MODIFY':
                    e.oper_str = "修改内容";
                    break;
                case 'ATTRIB':
                    e.oper_str = "更改属性";
                    break;
                case 'DELETE':
                    e.oper_str = "文件删除";
                    break;
            }
            e.content = atob(e.content);
        })
        state.fileLog = logs;
    },
    SET_PROCESS_LOG(state, logs) {
        state.processLog = logs;
    },
    SET_WARN_LOG(state, logs) {
        state.warnLog = logs;
    },
    SET_PWN_LOG(state, logs) {
        logs.forEach(e => {
            e.stdin = e.stdin.group + " Groups / " + e.stdin.byte + " Bytes";
            e.stdout = e.stdout.group + " Groups / " + e.stdout.byte + " Bytes";
        })
        state.pwnLog = logs;
    }
}

// 创建 store 实例
export default new Vuex.Store({
    actions,
    getters,
    state,
    mutations
})