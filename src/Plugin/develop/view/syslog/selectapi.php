<?php
/** @var string $json_api_list */
/** @var string $tool_title */
/** @var \Tiny\Interfaces\RequestInterface $request */
/** @var string $tool_title */

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?= $tool_title ?></title>
    <link href="http://g.alicdn.com/bui/bui/1.1.21/css/bs3/dpl.css" rel="stylesheet">
    <link href="http://g.alicdn.com/bui/bui/1.1.21/css/bs3/bui.css" rel="stylesheet">
    <link href="http://g.tbcdn.cn/fi/bui/css/layout-min.css" rel="stylesheet">
    <style type="text/css">

        pre {
            background:#fff;
            border:#d8d8d8 1px solid;
            padding:.5em;
        }
        .hljs-comment,.hljs-title {
            color:#8e908c
        }
        .hljs-variable,.hljs-attribute,.hljs-tag,.hljs-regexp,.ruby .hljs-constant,.xml .hljs-tag .hljs-title,.xml .hljs-pi,.xml .hljs-doctype,.html .hljs-doctype,.css .hljs-id,.css .hljs-class,.css .hljs-pseudo {
            color:#c82829
        }
        .hljs-number,.hljs-preprocessor,.hljs-pragma,.hljs-built_in,.hljs-literal,.hljs-params,.hljs-constant {
            color:#f5871f
        }
        .ruby .hljs-class .hljs-title,.css .hljs-rules .hljs-attribute {
            color:#eab700
        }
        .hljs-string,.hljs-value,.hljs-inheritance,.hljs-header,.ruby .hljs-symbol,.xml .hljs-cdata {
            color:#718c00
        }
        .css .hljs-hexcolor {
            color:#3e999f
        }
        .hljs-function,.python .hljs-decorator,.python .hljs-title,.ruby .hljs-function .hljs-title,.ruby .hljs-title .hljs-keyword,.perl .hljs-sub,.javascript .hljs-title,.coffeescript .hljs-title {
            color:#4271ae
        }
        .hljs-keyword,.javascript .hljs-function {
            color:#8959a8
        }
        .hljs {
            display:block;
            background:white;
            color:#4d4d4c;
        }
        .coffeescript .javascript,.javascript .xml,.tex .hljs-formula,.xml .javascript,.xml .vbscript,.xml .css,.xml .hljs-cdata {
            opacity:.5
        }

        .demo-content{
            margin-left: 10px;
            margin-top: 10px;
        }
    </style>
    <script src="http://g.tbcdn.cn/fi/bui/jquery-1.8.1.min.js"></script>
    <script src="http://g.alicdn.com/bui/seajs/2.3.0/sea.js"></script>
    <script src="http://g.alicdn.com/bui/bui/1.1.21/config.js"></script>
    <script src="https://cdn.bootcss.com/highlight.js/9.12.0/highlight.min.js"></script>
    <script src="http://cdn.bootcss.com/json2/20150503/json2.min.js"></script>

</head>
<body>
<div class="demo-content">
    <h2 class="tip-title">常用脚本：</h2>
    <div class="control-group">
        <a class="button button-success array_btn" target="new"
           href="<?= \Tiny\Application::url($request, ['', 'deploy', 'buildApiModJs'], ['dev_debug' => 1,]) ?>">编译API</a>
        <a class="button button-success array_btn" target="new"
           href="<?= \Tiny\Application::url($request, ['', 'deploy', 'phpinfo']) ?>">phpInfo</a>
        <a class="button button-success array_btn" target="new" href="<?=  $request->host()  ?>">首页</a>
    </div>
</div>
<div class="demo-content">
    <h2 class="tip-title">API调试工具</h2>
    <form id="J_Form" action="" class="form-horizontal">
        <div class="control-group">
            <label class="control-label">hook：</label>
            <div class="controls bui-form-group-input" data-type="custom1">
                <input type="text" id="hook_id">（以此hook_id身份执行API）
            </div>
        </div>
        <div class="control-group">
            <label class="control-label">选择API：</label>
            <div class="controls bui-form-group-select" data-type="custom1">
                <select id="api_class" name="g" class="input-normal" style="min-width: 210px;">
                    <option>请选择</option>
                </select>&nbsp;&nbsp;
                <select id="api_method" name="h" class="input-normal" style="min-width: 350px;">
                    <option>请选择</option>
                </select>
            </div>
        </div>
    </form>
    <form id="API_Form" action="" class="form-horizontal">
        <h2 class="tip-title">参数列表</h2>
        <div class="row">
            <div class="actions-bar span10 api-div1">
                <div class="form-actions" id="api_ajax_form">
                </div>
                <div class="form-actions offset3">
                    <button id="api_ajax_btn" type="button" class="button button-primary">提交</button>
                    &nbsp;&nbsp;&nbsp;&nbsp;
                    <button type="reset" class="button">重置</button>
                </div>
            </div>
            <div class="actions-bar span14 api-div2">
                <pre id="api-pre"></pre>
            </div>
        </div>
    </form>
    <div class="log well" id="status_log"></div>
</div>
<!-- script start -->
<script type="text/javascript">

    function trim(str) { //删除左右两端的空格
        return str.replace(/(^\s*)|(\s*$)/g, "");
    }
    
    BUI.use('bui/form', function (Form) {
        //定义级联下拉框的类型
        BUI.Form.Group.Select.addType('custom1', {
            url: "<?=\Tiny\Application::url($request, ['', '', 'getmethodlist'])?>",
            root: {
                id: '0',
                children: <?= $json_api_list?>
            }
        });
        new Form.Form({
            srcNode: '#J_Form'
        }).render();

        $('#api_method').change(function () {
            var cls = $('#api_class').val(),
                method = $('#api_method').val();
            var api_url = "<?=\Tiny\Application::url($request, ['', '', 'getparamlist'])?>";
            $.ajax({
                type: "GET",
                url: api_url,
                data: {cls: cls, method: method},
                dataType: "json",
                success: function (data) {
                    $('#api_ajax_form').html('');
                    $('#api-pre').html('');
                    if (data.error && data.errno != 0) {
                        console.log(data.error);
                        return;
                    }
                    $('#api-pre').html(data.Note);
                    for (var idx = 0; idx < data.Args.length; idx++) {
                        var item = data.Args[idx];
                        var must_tag = item.is_optional ? '' : '<s>*</s>';
                        var array_style = item.is_array ? '' : 'style="display:none;"';
                        item.name = item.is_array ? item.name + '[ ]' : item.name;
                        var html = '<div class="control-group"><label class="control-label">' + must_tag + item.name + '：</label><div class="controls"><input name="' + item.name + '" type="text" value="' + item.optional + '" class="input-normal" data-rules="{required : true}"><span ' + array_style + ' class="args_btn"><span class="x-icon x-icon-success array_btn">+</span><span class="x-icon x-icon-error array_btn">×</span></span></div></div>';
                        $('#api_ajax_form').append(html);
                    }
                    setTimeout(array_btn_set, 200);
                }
            });
        });

        $('#api_ajax_btn').on('click', function () {
            var cls = $('#api_class').val(),
                method = $('#api_method').val();
            var json_data = $("#API_Form").serializeJson();
            var hook_id = $('#hook_id').val();

            var api = cls + '.' + method;

            $('#status_log').html('');

            _api(api, json_data, hook_id, function (data) {
                var json_code = "<pre><code>" + JSON.stringify(data, null, 4) + "</code></pre>";
                $('#status_log').html(json_code);
                setTimeout(hljs_code, 200);
            });
        });
    });

    function api_log(api, tag, use_time, args, data) {
        delete args.csrf;
        var _log_func_dict = (typeof console !== "undefined" && typeof console.info === "function" && typeof console.warn === "function") ? {
            INFO: console.info.bind(console),
            ERROR: console.warn.bind(console)
        } : {};

        var f = _log_func_dict[tag];
        f && f(formatDateNow(), '[' + tag + '] ' + api + '(' + use_time + 'ms)', 'args:', args, 'data:', data);
    }

    function formatDateNow() {
        var now = new Date(new Date().getTime());
        var year = now.getFullYear();
        var month = now.getMonth() + 1;
        var date = now.getDate();
        var hour = now.getHours();
        var minute = now.getMinutes();
        if (minute < 10) {
            minute = '0' + minute.toString();
        }
        var seconds = now.getSeconds();
        if (seconds < 10) {
            seconds = '0' + seconds.toString();
        }
        return year + "-" + month + "-" + date + " " + hour + ":" + minute + ":" + seconds;
    }

    function hljs_code() {
        $('pre code').each(function (i, block) {
            hljs.highlightBlock(block);
        });
    }

    function array_btn_set() {
        $('.x-icon-success').on('click', function () {
            var item = $(this).closest('.control-group');
            item.after(item.clone(true));
        });

        $('.x-icon-error').on('click', function () {
            var item = $(this).closest('.control-group');
            item.remove();
        });
    }

    function _api(api, json_data, hook_id, callback) {
        api = api || '';
        json_data = json_data || {};
        hook_id = hook_id || 0;
        callback = callback || function (data) {
            console.warn('test api:', api, ', args:', json_data, ', ret:', data);
        };

        if (hook_id) {
            json_data.hook_id = hook_id;
        }

        var api_url = '/api/' + api.replace('.', '/');

        var json_data_ = JSON.parse(JSON.stringify(json_data));
        for (var key in json_data_) {
            if (json_data_.hasOwnProperty(key)) {
                var item = json_data_[key];
                if($.isArray(item)){
                    var tmp = item.map(function (i) {
                        return $.isArray(i) ? i[0] : i;
                    });
                    delete json_data[key];

                    var key_ = key.replace('[ ]', '');
                    json_data[key_] = tmp;
                }
                if (item === 'null') {
                    delete json_data[key];
                }
            }
        }

        var start_time = new Date().getTime();
        if (typeof CSRF_TOKEN !== "undefined" && CSRF_TOKEN) {
            json_data.csrf = CSRF_TOKEN;
        }

        $.ajax({
            type: "POST",
            url: api_url,
            data: json_data,
            dataType: "json",
            success: function (data) {
                var use_time = Math.round((new Date().getTime() - start_time));
                if (data.code === 0 || !data.error) {
                    api_log(api, 'INFO', use_time, json_data, data);
                } else {
                    api_log(api, 'ERROR', use_time, json_data, data);
                }
                typeof callback == "function" && callback(data);
            }
        });
    }

    (function ($) {
        $.fn.serializeJson = function () {
            var serializeObj = {};
            var array = this.serializeArray();
            $(array).each(function () {
                if (serializeObj[this.name]) {
                    if ($.isArray(serializeObj[this.name])) {
                        serializeObj[this.name].push(this.value);
                    } else {
                        serializeObj[this.name] = [serializeObj[this.name], this.value];
                    }
                } else {
                    serializeObj[this.name] = this.value;
                }
            });
            return serializeObj;
        };
    })(jQuery);
</script>
<!-- script end -->
</body>
</html>