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
        <el-button @click="buildBinary">生成二进制</el-button>
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
      <el-table-column prop="bin" label="执行文件" min-width="5"></el-table-column>
      <el-table-column prop="stdin" label="STDIN" min-width="30"></el-table-column>
      <el-table-column prop="stdout" label="STDOUT" min-width="30"></el-table-column>
    </el-table>
  </section>
</template>

<script>
import config from "../../config.js";
import { PwnLogSystemApi } from "../../api/index.js";
import logSystemMixins from "../../mixin/logSystem.js";
import axios from "axios";
import Vue from "vue";
import { mapGetters } from "vuex";
export default {
  mixins: [logSystemMixins],
  methods: {
    tableRowClassName(row) {
      return "_PWN_" + row.method;
    },
    choose(row, event) {
      const { page } = this;
      const { id, stdin, stdout } = row;
      this.$router.push({
        path: "/pwnLog/singlePage",
        query: {
          page,
          id
        }
      });
    },
    changePage(page) {
      this.changePageGenerator(
        PwnLogSystemApi.getPwnLog(page, 20),
        "setPwnLog",
        "/pwnLog"
      )();
    },
    gotoLatest() {
      this.gotoLatestGenerator(PwnLogSystemApi.getPwnLog(0, 20), "setPwnLog")();
    },
    buildBinary() {
      alert("Not IMPL in frontend");
    }
  },
  computed: {
    ...mapGetters({
      tableData: "getPwnLog"
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
    this.$bus.on("goto-pwn-latest", () => {
      if (this.latestFlag) {
        this.gotoLatest();
      }
    });
  }
};
</script>

<style>
</style>