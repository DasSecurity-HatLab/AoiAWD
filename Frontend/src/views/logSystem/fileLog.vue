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
      size="small"
      v-loading="loading"
      :data="tableData"
      border
      :row-class-name="tableRowClassName"
      style="width: 100%"
    >
      <el-table-column prop="time" label="时间" min-width="25"></el-table-column>
      <el-table-column prop="oper_str" 
                       label="操作" 
                       min-width="25"
                       :filters="[{text:'CREATE', value:'CREATE'},
                                  {text:'MODIFY', value:'MODIFY'},
                                  {text:'CLOSE_WRITE', value:'CLOSE_WRITE'},
                                  {text:'ATTRIB', value:'ATTRIB'},
                                  {text:'DELETE', value:'DELETE'},
                                  {text:'警告', value:'warn'}]"
                       :filter-method="filterTag"
                       filter-placement="bottom-end"></el-table-column>
      <el-table-column label="路径" min-width="100">
        <template slot-scope="scope">
          <span v-if="scope.row.isdir == 1" style="color:green">{{scope.row.path}}</span>
          <span v-if="scope.row.isdir == 0">{{scope.row.path}}</span>
        </template>
      </el-table-column>
      <el-table-column prop="content" label="文件内容">
        <template slot-scope="scope">
          <el-button type="text" @click="download(scope.row)">{{scope.row.content}}</el-button>
        </template>
      </el-table-column>
    </el-table>
  </section>
</template>

<script>
import config from "../../config.js";
import { FileLogSystemApi } from "../../api/index.js";
import logSystemMixins from "../../mixin/logSystem.js";
import axios from "axios";
import { mapGetters } from "vuex";
import { setTimeout } from "timers";

export default {
  mixins: [logSystemMixins],
  methods: {
    tableRowClassName(row) {
      if (row.id == this.highLight) {
        return "_FILE_warn row";
      }
      return "_FILE_" + row.oper;
    },
    download(row) {
      let downloadUrl = `${config.ajax_addr}/downloadfile?id=${row.id}&token=${sessionStorage.getItem("accessToken")}`;
      let link = document.createElement("a");
      link.display = "none";
      link.href = downloadUrl;
      let path = row.path.split("/");
      link.setAttribute("download", path[path.length - 1]);
      link.onload = () => {
        document.body.removeChild(link);
      };
      document.body.appendChild(link);
      link.click();
    },
    changePage(page) {
      this.changePageGenerator(
        FileLogSystemApi.getFileLog(page, 20),
        "setFileLog",
        "/fileLog"
      )();
    },
    gotoLatest() {
      this.gotoLatestGenerator(
        FileLogSystemApi.getFileLog(0, 20),
        "setFileLog"
      )();
    },
    filterTag(value, row){
      return row.oper_str == value;
    },
  },
  computed: {
    ...mapGetters({
      tableData: "getFileLog"
    })
  },
  data() {
    return {
      loading: false,
      lastPage: 0,
      page: 1,
      latestFlag: false,
      highLight: ''
    };
  },
  mounted() {
    this.$bus.on("goto-file-latest", () => {
      if (this.latestFlag) {
        this.gotoLatest();
      }
    });
  }
};
</script>

<style>
._FILE_CREATE {
  background: #979eff !important;
}
._FILE_MODIFY {
  background: #ffe6b1 !important;
}
._FILE_CLOSE_WRITE {
  background: #ffc1b9 !important;
}
._FILE_ATTRIB {
  background: #219481 !important;
}
._FILE_DELETE {
  background: #f083ff !important;
}
._FILE_warn {
  background: red !important;
}
</style>