import axios from 'axios';
import config from "../../config.js"
export default {
    getWebLog(page, count) {
        return axios.get(config.ajax_addr + `/listweb?page=${page}&count=${count}`);
    }
}