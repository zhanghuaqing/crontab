<template>
  <div id="app" :class="navclass">
    <div class="container body">
      <div class="main_container">
        <dashboard></dashboard>
        <topnav v-on:changeNav="changeNav"></topnav>
        <div class="right_col" role="main">
          <router-view></router-view>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import dashboard from './components/dashboard/dashboard';
import topnav from './components/topnav/topnav';

export default {
  name: 'app',
  data () {
    return {
      'navclass': 'nav-md',
    }
  },
  components: {
    'dashboard': dashboard,
    'topnav': topnav,
  },
  methods: {
    changeNav(state){
      this.navclass = state ? 'nav-sm' : 'nav-md';
    }
  }
}
</script>

<style>
@import "./common/css/bootstrap.min.css";
@import "./common/css/font-awesome.min.css";
@import "./common/css/custom.min.css";

.nav-md .container.body .right_col {
  padding: 60px 20px;
}
.nav-sm .container.body .right_col {
  padding: 60px 20px;
}
.nav-sm .container.body .col-md-3.left_col {
  z-index: 0;
}

</style>
