import Vue from 'vue'
import App from './App'
import ElementUI from 'element-ui'
import 'element-ui/lib/theme-default/index.css'
import VueRouter from 'vue-router'
import store from './vuex/store'
import Vuex from 'vuex'
import VueBus from 'vue-bus';
import routes from './routes'
import 'font-awesome/css/font-awesome.min.css'
import Axios from 'axios';


Vue.use(ElementUI)
Vue.use(VueRouter)
Vue.use(Vuex)
Vue.use(VueBus)


const router = new VueRouter({
  routes
})

router.beforeEach((to, from, next) => {
  if (to.path == "/login") {
    next();
  }
  if (!sessionStorage.getItem('accessToken')) {
    next("/login")
  } else {
    Axios.defaults.headers['Token'] = sessionStorage.getItem("accessToken");
    next();
  }
})

Axios.interceptors.response.use(response => {
  return response
},
  err => {
    if (String(err).indexOf(403)) {
      router.push({
        path: '/login'
      })
    }
  }
)

new Vue({
  router,
  store,
  render: h => h(App)
}).$mount('#app')

