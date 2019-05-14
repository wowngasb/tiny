<?php
/** @var string $default_pre_key */
/** @var int $record_index */
/** @var array $acc_seq */

?><!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>IP 过滤工具</title>
    <link rel="stylesheet" type="text/css" href="<?= \Tiny\Application::url($request, ['', 'assets', 'iview.css']) ?>">
    <script src="<?= \Tiny\Application::url($request, ['', 'assets', 'jquery-1.8.1.min.js']) ?>"></script>
    <script type="text/javascript" src="<?= \Tiny\Application::url($request, ['', 'assets', 'base-polyfill.js']) ?>"></script>
    <script type="text/javascript" src="<?= \Tiny\Application::url($request, ['', 'assets', 'bluebird.js']) ?>"></script>

    <script type="text/javascript" src="<?= \Tiny\Application::url($request, ['', 'assets', 'klutil.js']) ?>"></script>
    <script type="text/javascript" src="<?= \Tiny\Application::url($request, ['', 'assets', 'mod.js']) ?>"></script>
    <script type="text/javascript" src="<?= \Tiny\Application::url($request, ['', 'assets', 'ThrottleApi.js']) ?>"></script>

    <script type="text/javascript" src="<?= \Tiny\Application::url($request, ['', 'assets', 'vue.js']) ?>"></script>
    <script type="text/javascript" src="<?= \Tiny\Application::url($request, ['', 'assets', 'vuex.js']) ?>"></script>
    <script type="text/javascript" src="<?= \Tiny\Application::url($request, ['', 'assets', 'vue-router.js']) ?>"></script>
    <script type="text/javascript" src="<?= \Tiny\Application::url($request, ['', 'assets', 'iview.min.js']) ?>"></script>
    <style>
        .layout{
            border: 1px solid #d7dde4;
            background: #f5f7f9;
            position: relative;
            border-radius: 4px;
            overflow: hidden;
        }
        .layout-logo{
            width: 100px;
            height: 30px;
            background: #5b6270;
            border-radius: 3px;
            float: left;
            position: relative;
            top: 15px;
            left: 20px;
        }
        .layout-nav{
            width: 420px;
            margin: 0 auto;
            margin-right: 20px;
        }
        .layout-footer-center{
            text-align: center;
        }
    </style>
</head>
<body>
<div id="app">

</div>
<!-- script start -->
<script type="text/javascript" src="<?= \Tiny\Application::url($request, ['', 'assets', 'base-mixin.js']) ?>"></script>
<script type="text/javascript" src="<?= \Tiny\Application::url($request, ['', 'assets', 'main.js']) ?>"></script>
<script type="text/javascript" src="<?= \Tiny\Application::url($request, ['', 'assets', 'ip-tabs.js']) ?>"></script>
<script type="text/javascript" src="<?= \Tiny\Application::url($request, ['', 'assets', 'ip-content.js']) ?>"></script>
<script type="text/javascript" src="<?= \Tiny\Application::url($request, ['', 'assets', 'ip-setting.js']) ?>"></script>

<script type="text/javascript">
    var store = {
        state: {
            develop: 0,
            default_pre_key: '<?= $default_pre_key ?>',
            record_index: parseInt('<?= $record_index ?>'),
            acc_seq_list: <?= !empty($acc_seq_list) ? json_encode($acc_seq_list) : '[]' ?>
        },
        mutations: {
        },
        getters:{
        }
    };

    var app = new Vue({
        el: '#app',
        store: new Vuex.Store(store),
        render: h => h(App)
    })
</script>
<!-- script end -->
</body>
</html>