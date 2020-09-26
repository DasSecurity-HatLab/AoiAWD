<template>
  <div id="app">
    <transition name="fade" mode="out-in">
      <router-view></router-view>
    </transition>
  </div>
</template>

<script>
let ws;
// import { setTimeout } from timers;
import axios from "axios";
import bus from "vue-bus";

import config from "./config.js";
export default {
  name: "app",
  mounted: function() {
    this.showWSInput();
  },
  components: {},
  methods: {
    showWSInput() {
      const that = this;
      ws = new WebSocket(config.ws_addr);
      console.log(ws);
      ws.onopen = () => {
        console.log("Websocket通信建立");
        this.$store.dispatch("openWs").catch(err => {
          console.log("修改状态失败");
        });
      };
      ws.onclose = () => {
        console.log("websocket关闭");
        this.$store.dispatch("closeWs").catch(err => {
          console.log("修改状态失败");
        });
        this.$message.error("WebSocket连接丢失");
        setTimeout(this.showWSInput, 5000);
      };
      ws.onmessage = msg => {
        const { type } = JSON.parse(msg.data);
        this.$bus.emit("goto-main-latest");
        switch (type) {
          case "file":
            this.$bus.emit("goto-file-latest");
            break;
          case "process":
            this.$bus.emit("goto-process-latest");
            this.$bus.emit("process-working");
            break;
          case "web":
            this.$bus.emit("goto-web-latest");
            break;
          case "alert":
            this.$bus.emit("goto-alert-latest");
            break;
          case "pwn":
            this.$bus.emit("goto-pwn-latest");
            break;
        }
      };
    }
  }
};
</script>

<style lang="scss">
body {
  margin: 0px;
  padding: 0px;
  font-family: Helvetica Neue, Helvetica, PingFang SC, Hiragino Sans GB,
    Microsoft YaHei, SimSun, sans-serif;
  font-size: 14px;
  -webkit-font-smoothing: antialiased;
}

#app {
  position: absolute;
  top: 0px;
  bottom: 0px;
  width: 100%;
}

.el-submenu [class^="fa"] {
  vertical-align: baseline;
  margin-right: 10px;
}

.el-menu-item [class^="fa"] {
  vertical-align: baseline;
  margin-right: 10px;
}

.toolbar {
  background: #f2f2f2;
  padding: 10px;
  //border:1px solid #dfe6ec;
  margin: 10px 0px;
  .el-form-item {
    margin-bottom: 10px;
  }
}

.fade-enter-active,
.fade-leave-active {
  transition: all 0.2s ease;
}

.fade-enter,
.fade-leave-active {
  opacity: 0;
}
</style>