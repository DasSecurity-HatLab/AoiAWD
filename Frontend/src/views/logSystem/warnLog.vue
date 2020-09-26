<template>
  <section>
    <el-row style="margin-top:20px;margin-bottom:20px;">
      <el-col :span="12" :offset="6">
        <el-pagination
          background
          layout="prev, pager, next, jumper"
          @current-change="changePage"
          :page-size="20"
          :current-page.sync="page"
          :total="lastPage * 20"
        ></el-pagination>
      </el-col>
      <el-col :span="6">
        <el-button @click="gotoLatest">前往最新</el-button>
        <span style="margin-left:20px;">实时同步</span>
        <el-switch v-model="latestFlag"></el-switch>
      </el-col>
    </el-row>
    <el-table
      :size="'small'"
      v-loading="loading"
      :data="tableData"
      border
      :row-class-name="tableRowClassName"
      @row-dblclick="choose"
      style="width: 100%"
    >
      <el-table-column prop="time" label="时间" min-width="8"></el-table-column>
      <el-table-column prop="type" label="类型" min-width="5"></el-table-column>
      <el-table-column prop="plugin" label="插件" min-width="7"></el-table-column>
      <el-table-column prop="message" label="描述" min-width="50"></el-table-column>
    </el-table>
  </section>
</template>

<script>
import config from "../../config.js";
import { WarnLogSystemApi } from "../../api/index.js";
import logSystemMixins from "../../mixin/logSystem.js";
import axios from "axios";
import Vue from "vue";
import { mapGetters } from "vuex";
export default {
  mixins: [logSystemMixins],
  methods: {
    tableRowClassName(row) {
      return "_WARN_" + row.method;
    },
    choose(row, event) {
      const id = row.reference.id;
      const page = row.reference.page;
      let path = "";
      switch (row.type) {
        case "Web":
          path = "/webLog";
          break;
        case "FileSystem":
          path = "/fileLog";
          break;
        case "Process":
          path = "/processLog";
          break;
      }
      this.$router.push({
        path,
        query: {
          page,
          id
        }
      });
    },
    changePage(page) {
      this.changePageGenerator(
        WarnLogSystemApi.getWarnLog(page, 20),
        "setWarnLog",
        "/warnLog"
      )();
    },
    gotoLatest() {
      this.gotoLatestGenerator(
        WarnLogSystemApi.getWarnLog(0, 20),
        "setWarnLog"
      )();
    }
  },
  computed: {
    ...mapGetters({
      tableData: "getWarnLog"
    })
  },
  data() {
    return {
      loading: false,
      lastPage: 0,
      page: 1,
      latestFlag: false
    };
  },
  mounted() {
    this.$bus.on("goto-alert-latest", () => {
      if (this.latestFlag) {
        this.gotoLatest();
      }
    });
  }
};
</script>

<style>
</style>
