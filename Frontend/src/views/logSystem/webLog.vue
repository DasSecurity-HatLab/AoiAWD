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
        <span style="margin-left:20px">实时同步</span>
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
      <el-table-column prop="time"
                       label="时间" 
                       sortable
                       min-width="13">
      </el-table-column>
      <el-table-column prop="method" 
                       label="方法" 
                       :filters="[{text:'GET', value:'GET'},
                                  {text:'POST', value:'POST'},
                                  {text:'DELETE', value:'DELETE'},
                                  {text:'OPTIONS', value:'OPTIONS'},
                                  {text:'HEAD', value:'HEAD'},
                                  {text:'PUT', value:'PUT'},
                                  {text:'CONNECT', value:'CONNECT'},
                                  {text:'TRACE', value:'TRACE'},
                                  {text:'PROPFIND', value:'PROPFIND'},
                                  {text:'警告', value:'warn'}]"
                       :filter-method="filterTag"
                       filter-placement="bottom-end"
                       min-width="10">
      </el-table-column>
      <el-table-column prop="remote" 
                       label="IP"
                       :filters="filterIPs" 
                       :filter-method="filterIp"
                       filter-placement="bottom-end"
                       min-width="11">
      </el-table-column>
      <el-table-column prop="uri" 
                       label="URL">
      </el-table-column>
    </el-table>
  </section>
</template>

<script>
import config from "../../config.js";
import { WebLogSystemApi } from "../../api/index.js";
import logSystemMixins from "../../mixin/logSystem.js";
import axios from "axios";
import Vue from "vue";
import { mapGetters } from "vuex";
export default {
  mixins: [logSystemMixins],
  methods: {
    tableRowClassName(row) {
      if (row.id == this.highLight) {
        return "_FILE_warn row";
      }
      return "_WEB_" + row.method;
    },
    choose(row, event) {
      this.$router.push({
        path: "/webLog/singlePage",
        name: "web日志详情",
        query: {
          id: row.id,
          page: this.page
        }
      });
    },
    changePage(page) {
      this.changePageGenerator(
        WebLogSystemApi.getWebLog(page, 20),
        "setWebLog",
        "/webLog"
      )();
    },
    gotoLatest() {
      this.gotoLatestGenerator(WebLogSystemApi.getWebLog(0, 20), "setWebLog")();
    },
    filterTag(value, row){
      return row.method == value;
    },
    filterIp(value, row) {
      return row.remote == value;
    }
  },
  computed: {
    ...mapGetters({
      tableData: "getWebLog"
    })
  },
  watch:{
    tableData(value) {
      let tempSet = new Set();
      let filterIps = [];
      value.forEach(ele => {
        tempSet.add(`${ele.remote}`)
      })
      tempSet.forEach(ip => {
        filterIps.push({
          text:ip,
          value:ip
        })
      })
      this.filterIPs = [...filterIps];
    }
  },
  data() {
    return {
      loading: false,
      lastPage: 0,
      page: 1,
      latestFlag: false,
      highLight: "",
      filterIPs:[]
    };
  },
  mounted() {
    this.$bus.on("goto-web-latest", () => {
      if (this.latestFlag) {
        this.gotoLatest();
      }
    });
  }
};
</script>

<style>
._WEB_GET {
  background: #fef2da !important;
}
._WEB_POST {
  background: #fdd683 !important;
}
._WEB_DELETE {
  background: #fdc95b !important;
}
._WEB_OPTIONS {
  background: #a59cff !important;
}
._WEB_HEAD {
  background: #e7705a !important;
}
._WEB_PUT {
  background: #98d870 !important;
}
._WEB_CONNECT {
  background: #70d8a8 !important;
}
._WEB_TRACE {
  background: rgba(50, 71, 8, 0.1) !important;
}
._WEB_PROPFIND {
  background: #f172f1 !important;
}
._WEB_warn {
  background: red !important;
}
</style>