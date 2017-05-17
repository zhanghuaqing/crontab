import Vue from 'vue'
import Router from 'vue-router'
import Machinelist from '@/components/machinelist/machinelist'
import Tasklist from '@/components/tasklist/tasklist'
import Monitor from '@/components/monitor/monitor'

Vue.use(Router)

export default new Router({
  routes: [
    {
      path: '/',
      name: 'Tasklist',
      component: Tasklist
    },
    {
      path: '/machinelist',
      name: 'Machinelist',
      component: Machinelist
    },
    {
      path: '/monitor',
      name: 'Monitor',
      component: Monitor
    }
  ]
})
