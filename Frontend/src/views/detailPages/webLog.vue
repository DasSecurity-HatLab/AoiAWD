<template>
  <div>
    <el-card>
      <div slot="header" class="clearfix">
        <el-button @click="goBack">返回</el-button>
        <el-tag type="success">URL:{{logData.uri}}</el-tag>
        <el-tag type="success">Remote:{{logData.remote}}</el-tag>
        <el-tag type="success">Method:{{logData.method}}</el-tag>
        <el-button style="float: right;" @click="downloadPacket">一键重放</el-button>
      </div>
      <el-collapse :value="['header', 'get', 'post', 'cookie', 'file', 'buffer']">
        <el-collapse-item title="header" name="header">
          <div>
            <div v-for="(value, key) in logData.header">
              <span style="font-weight:600">{{key}}</span>:
              <span>{{value}}</span>
            </div>
          </div>
        </el-collapse-item>
        <el-collapse-item title="get" name="get">
          <div>
            <div v-for="(value, key) in logData.get">
              <span style="font-weight:600">{{key}}</span>:
              <span>{{value}}</span>
            </div>
          </div>
        </el-collapse-item>
        <el-collapse-item title="post" name="post">
          <div>
            <div v-for="(value, key) in logData.post">
              <span style="font-weight:600">{{key}}</span>:
              <span>{{value}}</span>
            </div>
          </div>
        </el-collapse-item>
        <el-collapse-item title="cookie" name="cookie">
          <div>
            <div v-for="(value, key) in logData.cookie">
              <span style="font-weight:600">{{key}}</span>:
              <span>{{value}}</span>
            </div>
          </div>
        </el-collapse-item>
        <el-collapse-item title="file" name="file">
          <div>{{logData.file}}</div>
        </el-collapse-item>
        <el-collapse-item title="buffer" name="buffer">
          <div>{{logData.buffer}}</div>
        </el-collapse-item>
      </el-collapse>
    </el-card>
  </div>
</template>

<script>
import axios from "axios";
import config from "../../config.js";
import array_walk_recursive from "locutus/php/array/array_walk_recursive";
export default {
  methods: {
    goBack() {
      this.$router.push({
        path: "/webLog",
        query: {
          page: this.page
        }
      });
    },
    downloadPacket() {
      let downloadUrl = `${config.ajax_addr}/downloadwebautoscript?id=${
        this.id
      }&token=${sessionStorage.getItem("accessToken")}`;
      let link = document.createElement("a");
      link.display = "none";
      link.href = downloadUrl;
      link.setAttribute("download", `${this.id}.php`);
      console.log(link);
      link.onload = () => {
        document.body.removeChild(link);
      };
      document.body.appendChild(link);
      link.click();
    },
    decodeData(data, key) {
      return decodeURIComponent(data);
    }
  },
  data() {
    return {
      logData: {},
      id: 0,
      page: 1
    };
  },
  created() {
    this.id = this.$route.query.id;
    this.page = this.$route.query.page;
    axios
      .get(`${config.ajax_addr}/webdetail?id=${this.id}`)
      .then(res => {
        let logData = res.data;
        array_walk_recursive(logData, this.decodeData);
        this.logData = logData;
      })
      .catch(err => {
        console.error(err);
      });
  }
};
</script>

<style>
.item {
  margin: 10px;
  font-size: 18px;
}
.item__title {
  font-weight: 800;
  color: greenyellow;
}
</style>