import axios from 'axios';
import config from "../../config.js"
export default {
    getWarnLog(page, count) {
        return axios.get(config.ajax_addr + `/listalert?page=${page}&count=${count}`);
    }
}