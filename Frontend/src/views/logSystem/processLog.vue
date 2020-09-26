<template>
  <section>
    <el-row style="margin-top:20px;margin-bottom:20px;">
      <el-col :span="10" :offset="6">
        <el-pagination
          background
          layout="prev, pager, next, jumper"
          @current-change="changePage"
          :page-size="20"
          :current-page.sync="page"
          :total="lastPage * 20"
        ></el-pagination>
      </el-col>
      <el-col :span="8">
        <span>
          <el-button @click="gotoLatest">前往最新</el-button>
        </span>
        <span style="margin-left:20px">
          只显示运行中进程
          <el-switch v-model="currentFlag"></el-switch>
        </span>
        <span style="margin-left:20px">
          实时同步
          <el-switch v-model="latestFlag"></el-switch>
        </span>
      </el-col>
    </el-row>
    <el-table
      ref="table"
      :size="'small'"
      v-loading="loading"
      :data="tableData"
      border
      :row-class-name="tableRowClassName"
      style="width: 100%"
    >
      <el-table-column prop="time" label="时间" min-width="80"></el-table-column>
      <el-table-column label="用户" min-width="70">
        <template slot-scope="props">
          <span>{{props.row.user}}({{props.row.uid}})</span>
        </template>
      </el-table-column>
      <el-table-column prop="ppid" label="父进程" min-width="40"></el-table-column>
      <el-table-column prop="pid" label="进程号" min-width="40"></el-table-column>
      <el-table-column prop="bin" label="进程名" min-width="200"></el-table-column>
      <el-table-column prop="arg" label="启动参数" min-width="300"></el-table-column>
    </el-table>
  </section>
</template>

<script>
import config from "../../config.js";
import { ProcessLogSystemApi } from "../../api/index.js";
import logSystemMixins from "../../mixin/logSystem.js";
import axios from "axios";
import { mapGetters } from "vuex";
import { setTimeout } from "timers";
export default {
  mixins: [logSystemMixins],
  methods: {
    tableRowClassName(row) {
      if (row.id == this.highLight) {
        return "_PROCESS_warn row";
      }
      if (this.activeProcess.includes(row.pid)) {
        return "_PROCESS_highlight row";
      }
    },
    changePage(page) {
      this.changePageGenerator(
        ProcessLogSystemApi.getProcessLog(page, 20),
        "setProcessLog",
        "/processLog"
      )();
    },
    gotoLatest() {
      if (this.currentFlag) {
        this.viewCurrentProcess();
        return;
      }
      this.gotoLatestGenerator(
        ProcessLogSystemApi.getProcessLog(0, 20),
        "setProcessLog"
      )();
    },
    getActiveProcess() {
      ProcessLogSystemApi.getActiveProcess().then(res => {
        this.activeProcess = res.data;
      });
    },
    viewCurrentProcess() {
      this.loading = true;
      this.lastPage = "";
      ProcessLogSystemApi.getCurrentProcess().then(res => {
        this.$store.dispatch("setProcessLog", {
          logs: res.data.data
        });
        this.getActiveProcess();
        this.loading = false;
      });
    }
  },
  computed: {
    ...mapGetters({
      tableData: "getProcessLog"
    })
  },
  watch: {
    currentFlag(val) {
      if (val) {
        this.viewCurrentProcess();
        this.latestFlag = true;
      } else {
        this.changePage(1);
      }
    }
  },
  data() {
    return {
      loading: false,
      lastPage: 0,
      page: 1,
      latestFlag: false,
      activeProcess: [],
      currentFlag: false,
      highLight: ""
    };
  },
  mounted() {
    this.$bus.on("goto-process-latest", () => {
      if (this.latestFlag) {
        this.gotoLatest();
      }
    });
    this.$bus.on("process-working", () => {
      this.getActiveProcess();
    });
    this.getActiveProcess();
  }
};
</script>

<style>
._PROCESS_highlight {
  background: #dfca84 !important;
}

._PROCESS_warn {
  background: red !important;
}
</style>