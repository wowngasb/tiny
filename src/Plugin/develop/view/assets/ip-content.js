const IpContent = (function () {
    const searchConst = {
        pre_key: "",
        ip: ""
    };
    const timeList = [1, 2, 3, 5, 10, 15, 20, 30, 60].map(t => {
        return {
            value: t,
            title: t + '秒'
        };
    });

    const defaultPageInfo = types.defaultPageInfo('num', 'DESC', 20, 1);

    var refreshTimer = null;
    var refreshCount = 0;
    return Vue.component('ip-content', {
        props:{
            active_tab:{
                type: String,
                default: ""
            }
        },
        data() {
            return {
                ipList: [],
                pageInfo: types.copy(defaultPageInfo),
                searchShow: false,
                autoRefresh: false,
                searchLoading: false,
                searchVariables: types.copy(searchConst),
                timeRefresh: 5,
                timeList: timeList,
            };
        },
        computed: {
            tableColumns() {
                var base = [
                    {
                        title: 'IP',
                        key: 'ip',
                        width: 160,
                        sortable: true,
                        render(h, {row}) {
                            return h('a', {
                                attrs:{
                                    href: 'https://www.ipip.net/ip/' + row.ip + '.html',
                                    target: '_blank'
                                }
                            }, row.ip)
                        }
                    },
                    {
                        title: 'Score',
                        key: 'score',
                        width: 90,
                        sortType: 'desc',
                        sortable: true
                    },
                    {
                        title: 'Refuse',
                        key: 'refuse',
                        width: 160,
                        sortable: true,
                        render(h, {row}) {
                            var now = util.time();
                            return row.refuse > now ? h('span', {
                                style: {
                                    color: '#ed3f14'
                                }
                            }, util.interval2str(row.refuse - now)) : h('span', '-');
                        }
                    }
                ];
                var ext = (this.acc_seq_list || []).map(i => {
                    var num_key = 'num_' + i.seq;
                    var limit_key = 'limit_' + i.seq;
                    return {
                        title: '计数 (' + i.seq + 's)',
                        key: num_key,
                        sortable: true,
                        render(h, {row}) {
                            var num = row[num_key];
                            var limit = row[limit_key];
                            return h('span', {
                                style: {
                                    color: num >= limit ? '#ed3f14' : '#495060'
                                }
                            }, num);
                        }
                    };
                });
                return base.concat(ext);
            }
        },
        mounted() {
            this.init && this.init();
        },
        methods: {
            init() {
                this.searchVariables.pre_key = this.searchVariables.pre_key || this.default_pre_key;
                this.searchLoading = false;
                this.formDoSearch();

                refreshTimer && clearInterval(refreshTimer);
                refreshTimer = setInterval(() => {
                    refreshCount += 1;
                    if (this.autoRefresh && refreshCount % this.timeRefresh == 0) {
                        this.formDoSearch_();
                    }
                }, 1000);
            },
            formDoSearch_(_before, _catch, _finally) {
                _before && _before();
                types.ThrottleApi.apiIpList({
                    pre_key: this.searchVariables.pre_key || this.default_pre_key,
                    ip: this.searchVariables.ip || '',
                    page: this.pageInfo.page,
                    num: this.pageInfo.num
                })
                    .then(resp => {
                        this.ipList = types.copy(resp.ipList || []).map(i => {
                            (i.info || []).map(t => {
                                i['num_' + t.seq] = t.num || 0;
                                i['limit_' + t.seq] = t.limit || 0;
                            });
                            return i;
                        });
                        this.pageInfo = resp.pageInfo || types.copy(defaultPageInfo);
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
            pageOnPageSizeChange(num) {
                this.pageInfo.num = num;
                this.init && this.init();
            },
            pageOnChange(page) {
                this.pageInfo.page = page;
                this.init && this.init();
            },
            formDoClear() {
                this.searchLoading = false;
                this.searchVariables = types.copy(searchConst);
            },
        },
        template: `<div >
            <Row style="margin-bottom: 20px;">
              <Button @click=" searchShow = !searchShow " type="primary">高级检索
                <Icon v-show=" !searchShow " type="chevron-down"></Icon>
                <Icon v-show=" searchShow " type="chevron-up"></Icon>
              </Button>
              <Checkbox v-model="autoRefresh" style="margin-left: 30px;">自动刷新</Checkbox>
                <Select v-model="timeRefresh" style="width:80px;">
                    <Option v-for="item in timeList" :value="item.value" :key="item.value">{{ item.title }}</Option>
                </Select>
            </Row>

            <Row v-show=" searchShow " >
              
              <Form :model="searchVariables" :label-width="60">
                <Row type="flex" justify="start">

                  <Col :xs="10" :sm="8" :md="6" :lg="4">
                      <FormItem label="前缀：">
                        <Input v-model="searchVariables.pre_key" placeholder="" clearable></Input>
                      </FormItem>
                  </Col>

                  <Col :xs="10" :sm="8" :md="6" :lg="4">
                      <FormItem label="IP：">
                        <Input v-model="searchVariables.ip" placeholder="" clearable></Input>
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
            
            <Row>
                <Table stripe border :loading="searchLoading" :columns="tableColumns" :data="ipList"></Table>
            </Row>
            
            <Row style="margin-top: 20px;margin-bottom: 10px;">
              <Page :placement=" 'top' " :current="pageInfo.page" :page-size="pageInfo.num" :total="pageInfo.total" 
                :page-size-opts="[10, 20, 50, 100, 150, 200]"
                @on-change="pageOnChange" @on-page-size-change="pageOnPageSizeChange" show-total show-sizer show-elevator></Page>
            </Row>
            
        </div>`
    });
})();


