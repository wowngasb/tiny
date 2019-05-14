const IpSetting = (function () {
    const searchConst = {
        pre_key: ""
    };

    return Vue.component('ip-setting', {
        props:{
            active_tab:{
                type: String,
                default: ""
            }
        },
        data() {
            return {
                searchVariables: types.copy(searchConst),
                searchLoading: false,
                saveLoading: false,
                throttle: {},
                throttle_origin: {}
            };
        },
        computed: {
            throttle_enable:{
                get() {
                    return !!this.throttle.enable;
                },
                set(newValue) {
                    this.throttle.enable = newValue ? 1 : 0;
                }
            },
            throttle_acc_seq_json:{
                get() {
                    return JSON.stringify(this.throttle.acc_seq);
                },
                set(newValue) {
                    var tmp = JSON.parse(newValue);
                    if(tmp){
                        this.throttle.acc_seq = tmp;
                    }
                }
            },
            throttle_skip_pre:{
                get() {
                    return (this.throttle.skip_pre || []).join("\n")
                },
                set(newValue) {
                    this.throttle.skip_pre = newValue.split("\n");
                }
            },
            throttle_whitelist:{
                get() {
                    return (this.throttle.whitelist || []).join("\n")
                },
                set(newValue) {
                    this.throttle.whitelist = newValue.split("\n");
                }
            },
            throttle_blacklist:{
                get() {
                    return (this.throttle.blacklist || []).join("\n")
                },
                set(newValue) {
                    this.throttle.blacklist = newValue.split("\n");
                }
            }
        },
        mounted() {
            this.init && this.init();
        },
        methods: {
            formDoSearch_(_before, _catch, _finally) {
                _before && _before();
                types.ThrottleApi.apiIpSetting({
                    pre_key: this.searchVariables.pre_key || this.default_pre_key
                })
                    .then(resp => {
                        this.throttle = resp.throttle;
                        this.throttle_origin = resp.throttle_origin;
                    })
                    .catch(resp => {
                        _catch && _catch(resp);
                    })
                    .finally(() => {
                        _finally && _finally();
                    });
            },
            formDoSearch() {
                this.formDoSearch_(() => {
                    this.searchLoading = true;
                }, resp => {
                    this._error(resp);
                }, () => {
                    this.searchLoading = false;
                });
            },
            formDoClear() {
                this.searchLoading = false;
                this.searchVariables = types.copy(searchConst);
            },
            formDoReset() {
                this.throttle = this.throttle_origin;
            },
            formDoSave() {
                this.formDoSave_(() => {
                    this.saveLoading = true;
                }, resp => {
                    this._error(resp);
                }, () => {
                    this.saveLoading = false;
                });
            },
            formDoSave_(_before, _catch, _finally) {
                _before && _before();
                types.ThrottleApi.apiSaveIpSetting({
                    pre_key: this.searchVariables.pre_key || this.default_pre_key,
                    throttle: {
                        ...this.throttle,
                        acc_seq_json: this.throttle_acc_seq_json
                    }
                })
                    .then(resp => {
                        this._success(resp);
                    })
                    .catch(resp => {
                        _catch && _catch(resp);
                    })
                    .finally(() => {
                        _finally && _finally();
                    });
            },
            init(){
                this.searchVariables.pre_key = this.searchVariables.pre_key || this.default_pre_key;
                this.searchLoading = false;
                this.formDoSearch();
            },
        },
        template: `<div >
            <Row >
              
              <Form :model="searchVariables" :label-width="60">
                <Row type="flex" justify="start">

                  <Col :xs="10" :sm="8" :md="6" :lg="4">
                      <FormItem label="前缀：">
                        <Input v-model="searchVariables.pre_key" placeholder="" clearable></Input>
                      </FormItem>
                  </Col>

                  <Col :xs="10" :sm="8" :md="6" :lg="4">
                  <FormItem>
                    <Button :loading="searchLoading" type="primary" @click="formDoSearch">搜索</Button>
                    <Button type="ghost" style="margin-left: 8px" @click="formDoClear">清除</Button>
                  </FormItem>
                  </Col>
                </Row>

              </Form>
            </Row>
            
            <Row type="flex" justify="center" >
                <Col :xs="22" :sm="18" :md="14" :lg="10">
                    <Spin size="large" fix v-if="searchLoading"></Spin>
                    <Card >
                        <Form v-show="throttle.pre_key" :model="throttle" label-position="right" :label-width="100">
                            <FormItem label="前缀：">
                                <Input v-model="throttle.pre_key" disabled readonly></Input>
                            </FormItem>
                            <FormItem label="是否开启：">
                                <i-switch v-model="throttle_enable" ></i-switch>
                            </FormItem>
                            <FormItem label="抽样检查间隔：">
                                <Input v-model="throttle.alive_sec" number></Input>
                            </FormItem>
                            <FormItem label="默认屏蔽时间：">
                                <Input v-model="throttle.refuse_sec" number></Input>
                            </FormItem>
                            <FormItem label="计数限制配置：">
                                <Input v-model="throttle_acc_seq_json" ></Input>
                            </FormItem>
                            <FormItem v-show="develop" label="跳过url前缀：">
                                <Input v-model="throttle_skip_pre" type="textarea" :autosize="{minRows: 5,maxRows: 8}"></Input>
                            </FormItem>
                            <FormItem label="ip白名单：">
                                <Input v-model="throttle_whitelist" type="textarea" :autosize="{minRows: 5,maxRows: 8}"></Input>
                            </FormItem>
                            <FormItem label="ip黑名单：">
                                <Input v-model="throttle_blacklist" type="textarea" :autosize="{minRows: 5,maxRows: 8}"></Input>
                            </FormItem>
                            <FormItem>
                                <Button type="ghost" style="margin-left: 8px" @click="formDoReset">恢复默认</Button>
                                <Button style="margin-left: 20px;" :loading="saveLoading" type="primary" @click="formDoSave">保存</Button>
                            </FormItem>
                        </Form>
                    </Card>
                </Col>
            </Row>
        </div>`
    });
})();


