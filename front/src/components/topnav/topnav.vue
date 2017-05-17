<template>
  <div class="top_nav">
          <div class="nav_menu">
            <nav>
              <div class="nav toggle">
                <a id="menu_toggle" @click="navToggle()"><i class="fa fa-bars"></i></a>
              </div>
              <ul class="nav navbar-nav navbar-right">
                <li :class="{'open': profileOpen}" @click="profileClick()">
                  <a href="javascript:;" class="user-profile dropdown-toggle" data-toggle="dropdown" v-bind:aria-expanded="profileOpen">
                    <img v-bind:src="profile_img" alt="">John Doe
                    <span class=" fa fa-angle-down"></span>
                  </a>
                  <ul class="dropdown-menu dropdown-usermenu pull-right">
                    <li><a href="javascript:;"> Profile</a></li>
                    <li>
                      <a href="javascript:;">
                        <span class="badge bg-red pull-right">50%</span>
                        <span>Settings</span>
                      </a>
                    </li>
                    <li><a href="javascript:;">Help</a></li>
                    <li><a href="login.html"><i class="fa fa-sign-out pull-right"></i> Log Out</a></li>
                  </ul>
                </li>
              </ul>
            </nav>
          </div>
</div>

</template>

<script>
export default {
  name: 'topnav',
  data () {
    return {
      navState: 0,
      profile_img: 'http://tva1.sinaimg.cn/crop.1.0.1239.1239.180/005SvAMcjw8eq43b4ne7hj30yi0yf40h.jpg',
      profileOpen: false,
      msg: 'dashboard'
    }
  },
  created() {

  },
  methods: {
   profileClick() {
    this.profileOpen = !this.profileOpen;
   },
   navToggle() {
    this.navState = !this.navState;
    this.$emit('changeNav',this.navState);
   }
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>

</style>
