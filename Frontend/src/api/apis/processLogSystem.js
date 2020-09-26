import axios from 'axios';
import config from "../../config.js"
export default {
    getProcessLog(page, count) {
        return axios.get(`${config.ajax_addr}/listprocess?page=${page}&count=${count}`);
    },

    getActiveProcess() {
        return axios.get(`${config.ajax_addr}/currentprocess`);
    },

    getCurrentProcess() {
        return axios.get(`${config.ajax_addr}/listcurrentprocess`);
    }
}