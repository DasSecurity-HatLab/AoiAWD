import axios from 'axios';
import config from "../../config.js"
export default {
    getFileLog(page, count) {
        return axios.get(config.ajax_addr + `/listfilesystem?page=${page}&count=${count}`);
    }
}