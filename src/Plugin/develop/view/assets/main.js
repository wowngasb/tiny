const App = Vue.component('app', {
    data(){
        return {
            pagePath: ['开发工具', '安全工具', 'IP过滤']
        }
    },
    template: `<div class="layout">
        <Layout>
            <Header>
                <Menu mode="horizontal" theme="dark" active-name="1">
                    <div class="layout-logo"></div>
                    <div class="layout-nav">
                    </div>
                </Menu>
            </Header>
            <Content :style="{padding: '0 50px'}">
                <Breadcrumb :style="{margin: '20px 0'}">
                    <BreadcrumbItem v-for="tag in pagePath" :key="tag">{{tag}}</BreadcrumbItem>
                </Breadcrumb>
                <Card>
                    <ip-tabs></ip-tabs>
                </Card>
            </Content>
            <Footer class="layout-footer-center">2015-2019 &copy; xdysoft</Footer>
        </Layout>
    </div>`
});