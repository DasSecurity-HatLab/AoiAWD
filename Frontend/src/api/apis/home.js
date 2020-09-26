import axios from 'axios';
import config from "../../config.js"
export default {
    reloadPlugin() {
        return axios.get(`${config.ajax_addr}/reloadplugin`);
    },
    getPlugin() {
        return axios.get(`${config.ajax_addr}/listplugin`);
    },
    ping() {
        return axios.get(`${config.ajax_addr}/ping`)
    }
}