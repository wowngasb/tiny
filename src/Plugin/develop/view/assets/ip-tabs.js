const IpTabs = (function () {

    return Vue.component('ip-tabs', {
        data() {
            return {
                activeTab: 'ip-content'
            };
        },
        template: `<div style="min-height: 200px;">
            <Tabs type="card" v-model="activeTab">
                <TabPane  label="状态" name="ip-content">
                    <ip-content v-if=" activeTab == 'ip-content' " :active_tab="activeTab" ></ip-content>
                </TabPane>
                <TabPane  label="配置" name="ip-setting">
                    <ip-setting v-if=" activeTab == 'ip-setting' " :active_tab="activeTab" ></ip-setting>
                </TabPane>
            </Tabs>
        </div>`
    });
})();


