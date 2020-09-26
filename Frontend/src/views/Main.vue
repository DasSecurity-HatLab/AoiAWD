<template>
  <section style="position:absolute;height:calc(100% - 60px);width:calc(100% - 280px)">
    <div style="height:40%">
      <el-row :gutter="10">
        <el-col :span="16" style="height:400px;">
          <el-card>
            <div slot="header" class="clearfix">
              <h2>系统状态</h2>
            </div>
            <el-card>
              <div class="system_status">
                <span>WebSocket 状态:</span>
                <span
                  v-bind:style="{color:webSocketStatus?'green':'red', fontSize:'20px'}"
                >●{{webSocketStatus?'已连接':'已丢失'}}</span>
              </div>
            </el-card>
            <el-card>
              <div style="margin-top:5px" class="system_status">
                <span>最后一次数据更新时间:</span>
                <span>{{lastUpdateTime}}</span>
              </div>
            </el-card>
            <el-card>
              <div class="system_status">
                <span>报警次数:</span>
                <span>{{warningCount}}</span>
              </div>
            </el-card>
            <el-card>
              <div class="system_status">
                <span>系统已运行时间:</span>
                <span>{{allTime}}</span>
              </div>
            </el-card>
          </el-card>
        </el-col>
        <el-col :span="8" style="height:400px;">
          <el-card style="height:385px;">
            <div slot="header" class="clearfix">
              <h2>
                已载入插件
                <el-button style="float: right" @click="reloadPlugin">重载</el-button>
              </h2>
            </div>
            <div>
              <el-table :data="plugData" border style="width: 100% height:100% overflow:scroll">
                <el-table-column prop="name" label="插件名称" min-width="45"></el-table-column>
              </el-table>
            </div>
          </el-card>
        </el-col>
      </el-row>
    </div>
    <div style="height:50%">
      <el-row :gutter="10" style="height:480px">
        <el-col :span="24" style="height:100%;">
          <el-card style="height:90%;">
            <div slot="header" class="clearfix">
              <h2>
                系统警告
                <el-button style="float: right;" @click="showWarnLog()">查看详情</el-button>
              </h2>
            </div>
            <div>
              <el-table :data="tableData" border style="width: 100%">
                <el-table-column prop="time" label="时间" width="180"></el-table-column>
                <el-table-column prop="type" label="类型" width="120"></el-table-column>
                <el-table-column prop="plugin" label="插件" width="120"></el-table-column>
                <el-table-column prop="message" label="描述" min-width="45"></el-table-column>
              </el-table>
            </div>
          </el-card>
        </el-col>
      </el-row>
    </div>
  </section>
</template>

<script>
import config from "../config.js";
import echarts from "echarts";
import axios from "axios";
import { mapGetters } from "vuex";
export default {
  mounted() {
    this.refresh();
    this.getPlugin();
    this.$bus.on("goto-main-latest", () => {
      this.refresh();
    });
  },
  computed: {
    ...mapGetters({
      webSocketStatus: "getWsState"
    })
  },
  data() {
    return {
      lastUpdateTime: "",
      warningCount: 0,
      allTime: "",
      tableData: [],
      plugData: []
    };
  },
  methods: {
    refresh() {
      axios
        .get(config.ajax_addr + `/listalert?page=0&count=8`)
        .then(res => {
          this.tableData = res.data.data;
        });
      axios.get(config.ajax_addr + "/info").then(res => {
        this.lastUpdateTime = res.data.timestamp_lastupdate;
        this.warningCount = res.data.count_alert;
        this.allTime = res.data.timestamp_runningtime;
      });
    },

    showWarnLog() {
      this.$router.push({
        path: "../warnLog"
      });
    },

    reloadPlugin() {
      axios.get(`${config.ajax_addr}/reloadplugin`).then(res => {
        this.getPlugin();
      });
    },

    getPlugin() {
      axios.get(`${config.ajax_addr}/listplugin`).then(res => {
        this.plugData = [];
        res.data.data.forEach(ele => {
          this.plugData.push({
            name: ele
          });
        });
      });
    },

    showWarnLog() {
      this.$router.push({
        path: "../warnLog"
      });
    }
  }
};
</script>

<style scoped>
h1,
h2,
h3,
h4,
h5,
h6 {
  margin: 0;
  border: 0;
}
.system_status {
  /* line-height: 50px; */
  font-size: 17px;
}
</style>