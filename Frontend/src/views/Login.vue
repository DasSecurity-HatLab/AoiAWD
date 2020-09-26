<template>
  <el-form :model="ruleForm2" :rules="rules2" ref="ruleForm2" label-position="left" label-width="0px" class="demo-ruleForm login-container">
    <h3 class="title">系统登录</h3>
    <el-form-item prop="accessToken">
      <el-input type="text" v-model="ruleForm2.accessToken" auto-complete="off" placeholder="accessToken"></el-input>
    </el-form-item>
    <el-form-item style="width:100%;">
      <el-button type="primary" style="width:100%;" @click.native.prevent="handleLogin" :loading="logining">登录</el-button>
    </el-form-item>
  </el-form>
</template>

<script>
  import { HomeApi } from "../api/index.js";
  import Axios from 'axios';
  export default {
    data() {
      return {
        logining: false,
        ruleForm2: {
          accessToken: '',
        },
        rules2: {
          accessToken: [
            { required: true, message: '请输入accessToken', trigger: 'blur' },
          ],
        },
        checked: true
      };
    },
    methods: {
      handleLogin() {
        sessionStorage.clear();
        Axios.defaults.headers['Token'] = this.ruleForm2.accessToken;
        HomeApi.ping()
          .then(res => {
            sessionStorage.setItem('accessToken', this.ruleForm2.accessToken);
            this.$router.push({
              path:'/main'
            })
          }).catch(err => {
            Axios.defaults.headers['Token'] = "";
          });
      }
    }
  }

</script>

<style lang="scss" scoped>
  .login-container {
    /*box-shadow: 0 0px 8px 0 rgba(0, 0, 0, 0.06), 0 1px 0px 0 rgba(0, 0, 0, 0.02);*/
    -webkit-border-radius: 5px;
    border-radius: 5px;
    -moz-border-radius: 5px;
    background-clip: padding-box;
    margin: 180px auto;
    width: 350px;
    padding: 35px 35px 15px 35px;
    background: #fff;
    border: 1px solid #eaeaea;
    box-shadow: 0 0 25px #cac6c6;
    .title {
      margin: 0px auto 40px auto;
      text-align: center;
      color: #505458;
    }
    .remember {
      margin: 0px 0px 35px 0px;
    }
  }
</style>