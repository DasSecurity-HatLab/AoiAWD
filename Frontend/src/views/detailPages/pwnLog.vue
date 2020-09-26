<template>
  <div>
    <el-card>
      <div slot="header" class="clearfix">
        <el-button @click="goBack">返回</el-button>
        <el-tag type="success">Binary: {{this.bin}}</el-tag>
        <el-tag type="success">STDOUT: {{this.stdout}}</el-tag>
        <el-tag type="success">STDIN: {{this.stdin}}</el-tag>
        <el-button style="float: right;" @click="downloadPacket">一键重放</el-button>
        <el-button style="float: right;" @click="downloadStream('all')">下载完整流</el-button>
        <el-button style="float: right;" @click="downloadMap">下载MAP</el-button>
      </div>
      <div class="render">
        <div v-for="(log, index) in streamlog">
          <el-collapse :value="log.type + '-' + index">
            <el-collapse-item :title="log.type" :name="log.type + '-' + index">
              <el-button style="float: right;" @click="downloadStream(index)">导出分组</el-button>
              <pre id="stdout" v-html="hexdump(this.atob(log.buffer))"></pre>
            </el-collapse-item>
          </el-collapse>
        </div>
      </div>
    </el-card>
  </div>
</template>

<script>
import axios from "axios";
import config from "../../config.js";
import { PwnLogSystemApi } from "../../api/index.js";

export default {
  methods: {
    goBack() {
      this.$router.push({
        path: "/pwnLog",
        query: {
          page: this.page
        }
      });
    },
    string2buffer(str) {
      str = str.split("");
      let val = [];
      for (let i = 0; i < str.length; i++) {
        val.push(str[i].charCodeAt().toString(16));
      }
      return new Uint8Array(
        val.map(function(h) {
          return parseInt(h, 16);
        })
      ).buffer;
    },
    hexdump(str) {
      str = this.string2buffer(str);
      let dump =
        "<span style='color:red'>" +
        "\n          0  1  2  3  4  5  6  7  8  9  A  B  C  D  E  F    0123456789ABCDEF" +
        "</span>";
      let view = new DataView(str);
      for (let i = 0; i < str.byteLength; i += 16) {
        dump += `\n<span>${("00000000" + i.toString(16).toUpperCase()).slice(
          -8
        )} </span>`;
        for (let j = 0; j < 16; j++) {
          let ch =
            i + j > str.byteLength - 1
              ? "  "
              : (
                  0 +
                  view
                    .getUint8(i + j)
                    .toString(16)
                    .toUpperCase()
                ).slice(-2);
          dump += `${ch} `;
        }
        function escapeHtml(text) {
          var map = {
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#039;"
          };
          return text.replace(/[&<>"']/g, function(m) {
            return map[m];
          });
        }
        dump += escapeHtml(
          "  " +
            String.fromCharCode
              .apply(null, new Uint8Array(str.slice(i, i + 16)))
              .replace(/[^\x20-\x7E]/g, ".")
        );
      }
      return dump;
    },
    downloadMap() {
      let downloadUrl = `${config.ajax_addr}/downloadpwn?id=${
        this.id
      }&type=maps&token=${sessionStorage.getItem("accessToken")}`;
      let link = document.createElement("a");
      link.display = "none";
      link.href = downloadUrl;
      link.setAttribute("download", `${this.id}-${this.bin}.maps`);
      console.log(link);
      link.onload = () => {
        document.body.removeChild(link);
      };
      document.body.appendChild(link);
      link.click();
    },
    downloadStream(part) {
      let downloadUrl = `${config.ajax_addr}/downloadpwn?id=${
        this.id
      }&type=stream&part=${part}&token=${sessionStorage.getItem(
        "accessToken"
      )}`;
      let link = document.createElement("a");
      link.display = "none";
      link.href = downloadUrl;
      link.setAttribute("download", `${this.id}-${this.bin}-${part}.bin`);
      console.log(link);
      link.onload = () => {
        document.body.removeChild(link);
      };
      document.body.appendChild(link);
      link.click();
    },
    downloadPacket() {
      let downloadUrl = `${config.ajax_addr}/downloadpwnautoscript?id=${
        this.id
      }&token=${sessionStorage.getItem("accessToken")}`;
      let link = document.createElement("a");
      link.display = "none";
      link.href = downloadUrl;
      link.setAttribute("download", `${this.id}.py`);
      console.log(link);
      link.onload = () => {
        document.body.removeChild(link);
      };
      document.body.appendChild(link);
      link.click();
    }
  },
  watch: {},
  data() {
    return {
      streamlog: {},
      id: 0,
      page: 1,
      bin: "",
      time: "",
      stdout: 0,
      stdin: 0
    };
  },
  created() {
    this.id = this.$route.query.id;
    this.page = this.$route.query.page;
    PwnLogSystemApi.getPwnDetail(this.id).then(res => {
      let { stdin, stdout, bin, time, streamlog } = res.data;
      this.stdin = stdin.group + " Groups / " + stdin.byte + " Bytes";
      this.stdout = stdout.group + " Groups / " + stdout.byte + " Bytes";
      this.streamlog = streamlog;
      this.bin = bin;
      this.time = time;
    });
  },
  mounted() {}
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
#line-number {
  color: green;
}
.render span {
  margin-left: 3px;
  margin-right: 3px;
  color: blue;
}
</style>