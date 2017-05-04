// The Vue build version to load with the `import` command
// (runtime-only or standalone) has been set in webpack.base.conf with an alias.
import Vue from 'vue'
import App from './App'
import router from './router'

Vue.config.productionTip = false

/* eslint-disable no-new */
new Vue({
  el: '#app',
  router,
  template: '<App/>',
  components: { App }
})
//require('./common/js/jquery.min.js')
//require('./common/js/bootstrap.min.js')
//require('./common/js/custom.min.js')
//require('./common/js/jquery-ui.js')
//import '/common/js/jquery.croneditor.js'
//require("exports-loader?$.fn.croneditor!./common/js/jquery.croneditor.js")