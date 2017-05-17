<template>
    <div class="col-md-3 left_col">
          <div class="left_col scroll-view">
            <div class="navbar nav_title" style="border: 0;">
              <a class="site_title"><i class="fa fa-home"></i> <span>Hi, CronJob!</span></a>
            </div>

            <div class="clearfix"></div>

            <!-- menu profile quick info -->
            <div class="profile clearfix">
              <div class="profile_pic">
                <img v-bind:src="profile_img" alt="..." class="img-circle profile_img">
              </div>
              <div class="profile_info">
                <span>Welcome,</span>
                <h2>John Doe</h2>
              </div>
            </div>
            <!-- /menu profile quick info -->
            <br />

            <!-- sidebar menu -->
            <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
              <div class="menu_section">
                <h3>General</h3>
                <ul class="nav side-menu">
                  <li v-for="(item,index) in dashlist" :class="{'active': index === itemActive}" @click="selectDash(index)">
                    <router-link v-bind:to="routerMap[index]"><i class="fa" :class="classMap[index]"></i> {{item}} </router-link>
                  </li>
                </ul>
              </div>
            </div>
            <!-- /sidebar menu -->
          </div>
        </div>
</template>

<script>
export default {
  name: 'dashboard',
  data () {
    return {
      dashlist: [],
      classMap: ['fa-bar-chart-o', 'fa-table', 'fa-desktop'],
      itemActive: 0,
      routerMap: ["/", "/machinelist", "/monitor"],
      profile_img: 'http://tva1.sinaimg.cn/crop.1.0.1239.1239.180/005SvAMcjw8eq43b4ne7hj30yi0yf40h.jpg',
      msg: 'dashboard'
    }
  },
  created() {
    this.dashlist = ['任务列表', '机器列表', '实时监控'];
    this.itemActive = this.routerMap.indexOf(this.$route.path) > 0 ? this.routerMap.indexOf(this.$route.path) : 0;
  },
  methods: {
    selectDash(index){
      this.itemActive = index;
    }
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>

</style>
