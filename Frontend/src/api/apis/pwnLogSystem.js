import axios from 'axios';
import config from "../../config.js"
export default {
    getPwnLog(page, count) {
        return axios.get(config.ajax_addr + `/listpwn?page=${page}&count=${count}`);
    },

    getPwnDetail(id) {
        return axios.get(`${config.ajax_addr}/pwndetail?id=${id}`)
    }
}