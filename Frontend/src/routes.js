import Login from './views/Login.vue'
import NotFound from './views/404.vue'
import Main from './views/Main.vue'
import Home from './views/Home.vue'
import webLog from './views/logSystem/webLog.vue'
import fileLog from './views/logSystem/fileLog.vue'
import processLog from './views/logSystem/processLog.vue';
import warnLog from './views/logSystem/warnLog.vue';
import PwnLog from './views/logSystem/pwnLog.vue';
import PwnDetailPage from './views/detailPages/pwnLog.vue';
import webPage from './views/detailPages/webLog.vue';

let routes = [
    {
        path: '/login',
        component: Login,
        name: '',
        hidden: true
    },
    {
        path: '/404',
        component: NotFound,
        name: '',
        hidden: true
    },
    {
        path: '/',
        component: Home,
        name: '首页',
        redirect: '/main',
        iconCls: 'fa fa-home',//图标样式class
        leaf: true,
        children: [
            { path: '/main', component: Main, name: '主页', hidden: true },
            {
                path: '/webLog/singlePage',
                component: webPage,
                name: 'web日志详情',
                hidden: true
            },
            {
                path: '/pwnLog/singlePage',
                component: PwnDetailPage,
                name: 'pwn日志详情',
                hidden: true
            },
        ]
    },
    {
        iconCls: 'fa fa-globe',
        path: '/',
        component: Home,
        leaf: true,
        children: [{
            path: '/webLog', component: webLog, hidden: true, name: 'Web'
        }]
    },
    {
        iconCls: 'fa fa-window-restore',
        path: '/',
        component: Home,
        leaf: true,
        children: [{
            path: '/pwnLog', component: PwnLog, hidden: true, name: 'PWN'
        }]
    },
    {
        iconCls: 'fa fa-save',
        path: '/',
        component: Home,
        leaf: true,
        children: [{
            path: '/fileLog', component: fileLog, hidden: true, name: '文件系统'
        }]
    },
    {
        iconCls: 'fa fa-server',
        path: '/',
        component: Home,
        leaf: true,
        children: [{
            path: '/processLog', component: processLog, hidden: true, name: '进程'
        }]
    },
    {
        iconCls: 'fa fa-exclamation-triangle',
        path: '/',
        component: Home,
        leaf: true,
        children: [{
            path: '/warnLog', component: warnLog, hidden: true, name: '系统警告'
        }]
    },
    {
        path: '*',
        hidden: true,
        redirect: { path: '/404' }
    }
];

export default routes;