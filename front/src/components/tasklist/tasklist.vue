<template>
<div>
    <data-tables :data='tableData' :actions-def='getActionsDef()' :checkbox-filter-def='getCheckFilterDef()' :search-def='getSearchDef()' :has-action-col='false' @select='handleSelect' @select-all='handleAllSelect' style="width:100%">
    <el-table-column prop="special_selection_col" type='selection' width='50'></el-table-column>
    <el-table-column prop="id" label="ID" width="80" sortable="custom"></el-table-column>
    <el-table-column prop="name" label="任务名"></el-table-column>
    <el-table-column prop="cron_time" label="执行时刻"></el-table-column>
    <el-table-column label="执行命令">
      <template scope="scope">
        <el-popover trigger="click" placement="top">
          <p>{{ scope.row.cron_cmd }}</p>
          <div slot="reference" class="name-wrapper">
            <el-tag>点击查看</el-tag>
          </div>
        </el-popover>
      </template>
    </el-table-column>
    <el-table-column width="130" label="IP列表">
      <template scope="scope">
          <div v-if="scope.row.select_serverlist[0]">
            <div v-for="item in scope.row.select_serverlist ">
              {{item.ip}}
            </div>
          </div>
          <div v-else>
            所有
          </div>
      </template>
    </el-table-column>
    <el-table-column prop="exec_status" label="任务状态"></el-table-column>
    <el-table-column label="最近执行的进程">
      <template scope="scope">
        <el-popover trigger="click" placement="top">
          <p v-for="(item,key) in scope.row.lastpro_info ">
            {{key}}：{{item}}
          </p>
          <div slot="reference" class="name-wrapper">
            <el-tag>点击查看</el-tag>
          </div>
        </el-popover>
      </template>
    </el-table-column>
    <!-- <el-table-column prop="alter_user" label="修改人"></el-table-column> -->
    <el-table-column label="状态">
       <template scope="scope">
          <!-- <p v-for="item in status_list" v-if="scope.row.status == item.code">{{item.name}}</p> -->
          <p v-if="scope.row.status == 1">
            在线
          </p>
          <p v-else-if="scope.row.status == 2">
            下线
          </p>
          <p v-else>
            未上线
          </p>
      </template>
    </el-table-column>
    <el-table-column label="操作" width="95">
      <template scope="scope">
        <el-tooltip content="编辑" placement="top">
          <el-button size="mini" type="primary" icon="edit" @click="handleEdit(scope.$index, scope.row)"></el-button>
        </el-tooltip>
        <el-tooltip content="查看统计" placement="top">
          <el-button size="mini" type="success" icon="share" @click="handleDelete(scope.$index, scope.row)"></el-button>
        </el-tooltip>
      </template>
    </el-table-column>
  </data-tables>

  <el-dialog :title="dialogFormTitle" v-model="dialogFormVisible">
    <el-form :model="ruleForm" :rules="rules" ref="ruleForm" label-width="100px" class="demo-ruleForm">
      <el-form-item label="任务名称" prop="name">
        <el-input v-model="ruleForm.name"></el-input>
      </el-form-item>
      <el-form-item label="执行时间" prop="max_exectime">
        <el-input v-model="ruleForm.max_exectime" placeholder="0表示没有执行时间限制"></el-input>
      </el-form-item>
      <el-form-item label="可执行IP" prop="" class="iptypeItem">
        <el-radio-group v-model="ruleForm.select_serverlist_isall" @change="serverlistIsallChange">
          <el-radio :label="1">所有IP</el-radio>
          <el-radio :label="0">部分IP</el-radio>
        </el-radio-group>
      </el-form-item>
      <el-form-item label="" prop="select_serverlist">
        <el-checkbox-group v-model="ipOptionsChecked" @change="ipOptionsChange">
          <el-checkbox v-for="(item,key) in ipOptions" :label="item.ip"  :key="item.id" :disabled="ruleForm.select_serverlist_isall == 1"></el-checkbox>
        </el-checkbox-group>
      </el-form-item>
      <el-form-item label="执行机器" prop="server_num">
        <el-radio-group v-model="ruleForm.server_num" @change="serverMachineChange">
          <el-radio :label="0">每台</el-radio>
          <el-radio :label="1">单台</el-radio>
        </el-radio-group>
      </el-form-item>
      <el-form-item label="执行命令" prop="cron_cmd">
        <el-input type="textarea" v-model="ruleForm.cron_cmd"></el-input>
      </el-form-item>

       <el-form-item label="执行时刻" prop="cron_time">
        <el-input v-model="ruleForm.cron_time"></el-input>
        <el-tabs type="border-card">
          <el-tab-pane label="分钟">
            <el-tabs type="card" @tab-click="cronTimeTabClick">
              <el-tab-pane label="每分钟" name="every-min">每分钟执行</el-tab-pane>
              <el-tab-pane label="每隔几分钟" name="each-min">
                <el-slider class="each-slider" v-model="eachMinValue" :min="1" :max="59" @change="eachMinChange" show-input></el-slider>
              </el-tab-pane>
              <el-tab-pane label="自定义分钟数" name="define-min">
                <el-checkbox-group v-model="defineMinOptions" size="small" @change="defineMinChange">
                  <el-checkbox-button v-for="(n,k) in 60" :label="k" :key="k">{{k}}</el-checkbox-button>
                </el-checkbox-group>
              </el-tab-pane>
            </el-tabs>
          </el-tab-pane>
          <el-tab-pane label="小时">
            <el-tabs type="card" @tab-click="cronTimeTabClick">
              <el-tab-pane label="每小时" name="every-hour">每小时执行</el-tab-pane>
              <el-tab-pane label="每隔几小时" name="each-hour">
                <el-slider class="each-slider" v-model="eachHourValue" :min="1" :max="23" @change="eachHourChange" show-input></el-slider>
              </el-tab-pane>
              <el-tab-pane label="自定义小时数" name="define-hour">
                <el-checkbox-group v-model="defineHourOptions" size="small" @change="defineHourChange">
                  <el-checkbox-button v-for="(n,k) in 24" :label="k" :key="k">{{k}}</el-checkbox-button>
                </el-checkbox-group>
              </el-tab-pane>
            </el-tabs>
          </el-tab-pane>
          <el-tab-pane label="天">
            <el-tabs type="card" @tab-click="cronTimeTabClick">
              <el-tab-pane label="每天" name="every-day">每天执行</el-tab-pane>
              <el-tab-pane label="单天" name="define-day">
                <el-checkbox-group v-model="defineDayOptions" size="small" @change="defineDayChange">
                  <el-checkbox-button v-for="(n,k) in 32" :label="k" :key="k">{{k}}</el-checkbox-button>
                </el-checkbox-group>
              </el-tab-pane>
            </el-tabs>
          </el-tab-pane>
          <el-tab-pane label="月">
            <el-tabs type="card" @tab-click="cronTimeTabClick">
              <el-tab-pane label="每月" name="every-month">每月执行</el-tab-pane>
              <el-tab-pane label="单月" name="define-month">
                <el-checkbox-group v-model="defineMonthOptions" size="small" @change="defineMonthChange">
                  <el-checkbox-button v-for="n in 12" :label="n" :key="n">{{n}}月</el-checkbox-button>
                </el-checkbox-group>
              </el-tab-pane>
            </el-tabs>
          </el-tab-pane>
          <el-tab-pane label="周">
            <el-tabs type="card" @tab-click="cronTimeTabClick">
              <el-tab-pane label="每周" name="every-week">每周执行</el-tab-pane>
              <el-tab-pane label="单周" name="define-week">
                <el-checkbox-group v-model="defineWeekOptions" size="small" @change="defineWeekChange">
                  <el-checkbox-button v-for="(n,k) in 7" :label="k" :key="k">{{weekDays[k]}}</el-checkbox-button>
                </el-checkbox-group>
              </el-tab-pane>
            </el-tabs>
          </el-tab-pane>
          <el-tab-pane label="重置">
            <el-button type="success" @click="resetDrawCron">重置</el-button>
          </el-tab-pane>
        </el-tabs>
      </el-form-item>

      <el-form-item>
        <el-button type="primary" @click="submitForm('ruleForm')">{{dialogFormSubmit}}</el-button>
        <!-- <el-button v-if="dialogFormReset" @click="resetForm('ruleForm')">重置</el-button> -->
        <el-button @click="closeDialogForm('ruleForm')">取消</el-button>
      </el-form-item>
      

    </el-form>
  </el-dialog>
  

</div>
</template>

<script>
import Vue from 'vue';
import ElementUI from 'element-ui';
import 'element-ui/lib/theme-default/index.css';
import DataTables from 'vue-data-tables';
import axios from 'axios';

Vue.use(ElementUI);
Vue.use(DataTables);

const ERR_OK = 0;
// const ipOptions = ['123.126.53.49', '111.13.89.26', '221.179.175.156', '10.210.70.85','21.17.175.155','21.179.1.16'];
const weekDays = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'];
const ruleFormDefault = {
          id: '',
          name: '',
          cron_time: '* * * * *',
          cron_cmd: '',
          max_exectime: '',
          select_serverlist: '',
          select_serverlist_isall: 1,
          exec_server: '',
          status: '',
          is_kill: '',
          alter_user: '',
          create_time: '',
          update_time: '',
          server_num: 0,
          lastpro_info: '',
          exec_status: ''
        };
export default {
  name: 'tasklist',
  data () {
    return {
        tableData: [],
        ipOptions: [],
        ipOptionsChecked: [],
        ipOptionsCheckedId: [],
        jobStatusIds: '',
        dialogFormVisible: false,
        dialogFormTitle: '',
        dialogFormSubmit: '',
        status_list: [],
        exec_iptype: 0,
        eachMinValue: 1,
        eachHourValue: 1,
        cronArr: ["*", "*", "*", "*", "*"],
        defineMinOptions: [],
        defineHourOptions: [],
        defineDayOptions: [],
        defineMonthOptions: [],
        defineWeekOptions: [],
        weekDays: weekDays,
        ruleFormDefault : ruleFormDefault,
        ruleForm: {
          id: '',
          name: '',
          cron_time: '',
          cron_cmd: '',
          max_exectime: '',
          select_serverlist: '',
          select_serverlist_isall: '',
          exec_server: '',
          status: '',
          is_kill: '',
          alter_user: '',
          create_time: '',
          update_time: '',
          server_num: '',
          lastpro_info: '',
          exec_status: ''
        },
        rules: {
          name: [
            { required: true, message: '请输入任务名称', trigger: 'blur' },
            { min: 3, max: 30, message: '长度在 3 到 30 个字符', trigger: 'blur' }
          ],
          max_exectime: [
            { required: true, message: '0表示没有执行时间限制', trigger: 'blur'},
          ],
          cron_cmd: [
            { required: true, message: '执行命令必填', trigger: 'blur' }
          ]
        }
    }
  },
  created() {
  // for(var i = 0;i< 100; i++){
  //   this.tableData = this.tableData.concat(data);
  // }
  //this.tableData = data;
  this.getTaskInitData();
  },
  components: {
    DataTables
  },
  methods: {
    //筛选状态
    getCheckFilterDef() {
      let self = this;
      // console.log(self.status_list);
      return {
        width: 8,
        props: 'status',
        def: [{
          'code': '0',
          'name': '未上线'
        }, {
          'code': '1',
          'name': '在线'
        }, {
          'code': '2',
          'name': '下线'
        }]
      }
    },
    //头部的三个按钮选项和事件
    getActionsDef() {
      let self = this;
      return {
        width: 8,
        def: [{
          name: 'new',
          handler() {
            self.dialogFormVisible = true;
            self.dialogFormTitle = '添加新任务';
            self.dialogFormSubmit = '立即创建';
            self.dialogFormReset = false;
            let tmp = {};
            for(let k in ruleFormDefault){
              tmp[k] = ruleFormDefault[k];
            }
            self.ruleForm = tmp;
          },
          icon: 'plus'
        },
        {
          name: '批量上线',
          handler() {
            self.statusOnOffline('online');
          },
          icon: 'caret-top'
        },
        {
          name: '批量下线',
          handler() {
            self.statusOnOffline('offline');
          },
          icon: 'caret-bottom'
        },
        ]
      }
    },
    //搜索框搜索内容
    getSearchDef() {
      return {
       // offset: 12,
        placeholder: 'ID / 任务名',
        props: ['id', 'name'] // can be string or Array
      }
    },
    //单选
    handleSelect(selection, row) {
      let ids = [];
      for (let i in selection) {
        ids.push(selection[i].id);
      }
      this.jobStatusIds = ids.toString();
    },
    //全选
    handleAllSelect(selection) {
      let ids = [];
      for (let i in selection) {
        ids.push(selection[i].id);
      }
      this.jobStatusIds = ids.toString();
    },
    //当前页码
    handleCurrentChange(val){
      console.log(`当前页: ${val}`);
    },
    //批量上下线
    statusOnOffline(type){
      let self = this;
      console.log(self.jobStatusIds);
      if(!self.jobStatusIds){
        self.$message('请选择至少一个任务');
        return false;
      }
      axios.get('/api/updateJobStatus',{
        params:{
            ids:self.jobStatusIds,
            operate_type:type
        }
      })
        .then(function(response){
            let res = response.data;
            if(res.status == 1){
              let msg = type == 'online' ? '上线成功' : '下线成功';
              self.$message(msg);
              self.getTaskInitData();
              self.jobStatusIds = '';
            }else{
              let msg = type == 'online' ? '上线失败' : '下线失败';
              self.$message(msg + '：' + res.message);
            }
        })
        .catch(function(error){
            self.$message(error);
        });
    },
    //点击提交
    submitForm(formName) {
      let self = this;
      this.$refs[formName].validate((valid) => {
        if (valid) {
          //console.log(this.ruleForm);
          //console.log(this.ipOptionsCheckedId);
          let ruleForm = this.ruleForm;
          ruleForm.checkIps = this.ipOptionsCheckedId;
          ruleForm.select_serverlist = ruleForm.checkIps.toString();
          if(ruleForm.select_serverlist_isall){
            ruleForm.select_serverlist = '';
          }
          let qs = require('qs');
          let submitData = qs.stringify(ruleForm);
          axios.post('/api/saveJob',
            submitData
          )
          .then(function(response){
              console.log(response.data);
              let res = response.data;
              if(res.status == 1){
                self.closeDialogForm();
                self.getTaskInitData();
                if(self.dialogFormSubmit == '立即创建'){
                  self.$message('创建成功');
                }else{
                  self.$message('保存成功');
                }
              }else{
                if(self.dialogFormSubmit == '立即创建'){
                  self.$message('创建失败：'+ res.message);
                }else{
                  self.$message('保存失败：'+ res.message);
                }
              }
              
          })
          .catch(function(error){
              self.$message(error);
          });
        } else {
          self.$message('输入错误');
          return false;
        }
      });
    },
    //重置表单
    resetForm(formName) {
      this.$refs[formName].resetFields();
    },
    //关闭弹层
    closeDialogForm(formName) {
      this.dialogFormVisible = false;
    },
    //执行机器
    serverMachineChange(val) {
      this.ruleForm.server_num = val;
    },
    //IP 是否是所有IP
    serverlistIsallChange(val) {
      this.ruleForm.select_serverlist_isall = val;
    },
    //部分IP的情况下 选中的IP
    ipOptionsChange(arr) {
      let ipOptionsCheckedId = [];
      let ipOptions = this.ipOptions;
      for ( let i in ipOptions){
        if (arr.indexOf(ipOptions[i].ip) > -1){
          ipOptionsCheckedId.push(ipOptions[i].id);
        }
      }
      this.ipOptionsCheckedId = ipOptionsCheckedId;
    },
    //cron点击控件
    cronTimeTabClick(tab, event) {
      let cronArr = this.cronArr;
      switch(tab.name){
        case 'every-min':
          cronArr[0] = "*";
        break;
        case 'each-min':
          cronArr[0] = "*/" + this.eachMinValue;
        break;
        case 'define-min':
          let defineMinOptions = this.defineMinOptions;
          cronArr[0] = defineMinOptions.join(',') ? defineMinOptions.join(',') : '*';
        break;
        case 'every-hour':
          cronArr[1] = "*";
        break;
        case 'each-hour':
          cronArr[1] = "*/" + this.eachHourValue;
        break;
        case 'define-hour':
          let defineHourOptions = this.defineHourOptions;
          cronArr[1] = defineHourOptions.join(',') ? defineHourOptions.join(',') : '*';
        break;
        case 'every-day':
          cronArr[2] = "*";
        break;
        case 'define-day':
          let defineDayOptions = this.defineDayOptions;
          cronArr[2] = defineDayOptions.join(',') ? defineDayOptions.join(',') : '*';
        break;
        case 'every-month':
          cronArr[3] = "*";
        break;
        case 'define-month':
          let defineMonthOptions = this.defineMonthOptions;
          cronArr[3] = defineMonthOptions.join(',') ? defineMonthOptions.join(',') : '*';
        break;
        case 'every-week':
          cronArr[4] = "*";
        break;
        case 'define-week':
          let defineWeekOptions = this.defineWeekOptions;
          cronArr[4] = defineWeekOptions.join(',') ? defineWeekOptions.join(',') : '*';
        break;
      }
      this.ruleForm.cron_time = cronArr.join(' ');
    },
    //每分钟
    eachMinChange(val) {
      let cronArr = this.cronArr;
      cronArr[0] = "*/" + this.eachMinValue;
      this.ruleForm.cron_time = cronArr.join(' ');
    },
    //每小时
    eachHourChange(val) {
      let cronArr = this.cronArr;
      cronArr[1] = "*/" + this.eachHourValue;
      this.ruleForm.cron_time = cronArr.join(' ');
    },
    //自定义分钟数
    defineMinChange(event) {
      this.drawCron(0,this.defineMinOptions.join(','));
    },
    //自定义小时数
    defineHourChange(event) {
      this.drawCron(1,this.defineHourOptions.join(','));
    },
    //自定义哪天  单天
    defineDayChange(event) {
      this.drawCron(2,this.defineDayOptions.join(','));
    },
    //自定义哪月 单月
    defineMonthChange(event) {
      this.drawCron(3,this.defineMonthOptions.join(','));
    },
    //自定义周几 单周
    defineWeekChange(event) {
      this.drawCron(4,this.defineWeekOptions.join(','));
    },
    //修改执行时刻文本
    drawCron(index,val) {
      let cronArr = this.cronArr;
      val = val ? val : '*';
      cronArr[index] = val;
      this.ruleForm.cron_time = cronArr.join(' ');
    },
    //重置执行时刻的文本
    resetDrawCron() {
      this.cronArr = ["*", "*", "*", "*", "*"];
      this.ruleForm.cron_time = this.cronArr.join(' ');
    },
    //点击修改编辑
    handleEdit(index, data) {
      this.dialogFormVisible = true;
      this.dialogFormTitle = '任务编辑';
      this.dialogFormSubmit = '保存信息';
      this.dialogFormReset = true;
      let tmp = {};
      for(let k in data){
        tmp[k] = data[k];
      }
      this.ruleForm = tmp;
      let iparr = [];
      let iparrid = [];
      let serverlist = tmp.select_serverlist;
      for(let i in serverlist){
       iparr.push(serverlist[i].ip);
       iparrid.push(serverlist[i].id);
      }
      this.ipOptionsChecked = iparr;
      this.ipOptionsCheckedId = iparrid;
    },
    getTaskInitData(){
      let self = this;
      self.status_list = [];
      self.ipOptions = [];
      axios.get('/api/getjoblist')
      .then(function (response) {
        let res = response.data;
        if(res.status == 1){
          self.tableData = res.data.job_list;
          for(let i in res.data.status_list){
            self.status_list.push(res.data.status_list[i]);
          }
          for(let i in res.data.server_list){
            self.ipOptions.push(res.data.server_list[i]);
          }
        }
      })
      .catch(function (response) {
        self.$message(response);
      });
    }
  }
}


</script>

<style scoped>
.el-checkbox {
  margin-left: 15px;
}
.iptypeItem {
  margin-bottom: 0px;
}
.each-slider {
  width: 94%;
  margin: 0 auto;
}
</style>
