<?php
/** @var string $json_dir */
/** @var string $tool_title */
/** @var \Tiny\Interfaces\RequestInterface $request */
/** @var array $routeInfo */

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?= $tool_title ?></title>
    <link href="http://g.alicdn.com/bui/bui/1.1.21/css/bs3/dpl.css" rel="stylesheet">
    <link href="http://g.alicdn.com/bui/bui/1.1.21/css/bs3/bui.css" rel="stylesheet">
    <link href="http://g.tbcdn.cn/fi/bui/css/layout-min.css" rel="stylesheet">
    <script src="http://g.tbcdn.cn/fi/bui/jquery-1.8.1.min.js"></script>
    <script src="http://g.alicdn.com/bui/seajs/2.3.0/sea.js"></script>
    <script src="http://g.alicdn.com/bui/bui/1.1.21/config.js"></script>
    <style type="text/css">
        .opt {
        }

        .opt p {
            margin-top: 5px;
            margin-left: 5px;
        }

        .opt a {
            margin-left: 10px;
            display: inline-block;
            padding: 6px 12px;
            text-align: center;
            color: #fff;
            background-color: #337ab7;
            border-color: #2e6da4;
            border-top-width: 1px;
            border-top-style: solid;
            border-top-color: transparent;
            border-right-width: 1px;
            border-right-style: solid;
            border-right-color: transparent;
            border-left-width: 1px;
            border-left-style: solid;
            border-left-color: transparent;
            border-bottom-width: 1px;
            border-bottom-style: solid;
            border-bottom-color: transparent;
            border-radius: 4px;
        }

        .opt input {
        }
    </style>
</head>
<body>
<div class="content"></div>
<!-- script start -->
<script type="text/javascript">
    var LOG_TAB;
    var DIR_TREE;
    var DIR_NODES = <?=$json_dir?>;

    function trim(str) { //删除左右两端的空格
        return str.replace(/(^\s*)|(\s*$)/g, "");
    }

    BUI.use(['bui/layout', 'bui/tab', 'bui/data', 'bui/tree'], function (Layout, Tab, Data, Tree) {
        var control = new Layout.Viewport({
            width: 600,
            height: 500,
            elCls: 'ext-border-layout',
            children: [{
                layout: {
                    title: '说明',
                    region: 'north',
                    height: 70
                },
                xclass: 'controller',
                content: '<h2>日志管理系统，仅供内部人员使用。 安全工具：<a href="<?=\Tiny\Application::url($request, ['', 'throttle', 'showState'], [])?>" target="view_window">IP过滤</a> ， 开发工具：<a href="<?=\Tiny\Application::url($request, ['', '', 'selectapi'], [])?>" target="view_window">调试API</a></h2>'
            }, {
                xclass: 'controller',
                layout: {
                    region: 'south',
                    title: '信息: <span id="file_info"></span>',
                    fit: 'height',
                    height: 20
                },
                width: 250,
                content: ''
            }, {
                xclass: 'controller',
                layout: {
                    region: 'east',
                    fit: 'both',
                    //collapsable : true,
                    //collapsed : true,
                    title: '操作',
                    width: 150
                },
                elCls: 'red',
                content: "<div class='opt'>" +
                "<p><input type='text' name='filter_input'></p>" +
                "<p><a id='btn_filter'>过滤</a><a id='btn_reload'>刷新</a></p>" +
                "<p><a id='btn_home'>最前</a><a id='btn_end'>最后</a></p>" +
                "<p><a id='btn_download'>下载</a><a id='btn_clear'>清空</a></p>" +
                "<p><label class='checkbox'> DEBUG <input class='input_checkbox' type='checkbox' checked></label></p>"+
                "<p><label class='checkbox'> INFO <input class='input_checkbox' type='checkbox' checked></label></p>"+
                "<p><label class='checkbox'> WARN <input class='input_checkbox' type='checkbox' checked></label></p>"+
                "<p><label class='checkbox'> ERROR <input class='input_checkbox' type='checkbox' checked></label></p>"+
                "<p><label class='checkbox'> FATAL <input class='input_checkbox' type='checkbox' checked></label></p></div>"
            }, {
                layout: {
                    region: 'west',
                    fit: 'both', //height,width,both,none
                    title: '目录',
                    collapsable: true,
                    width: 250
                },
                xclass: 'tree-list',//生成tree
                id: 'mytree',
                nodes: DIR_NODES

            }, {
                xclass: 'nav-tab', //Grid
                layout: {
                    region: 'center',
                    title: '内容',
                    fit: 'both'
                },
                id: 'tab'
            }],
            plugins: [Layout.Border]
        });

        control.render();

        LOG_TAB = control.getChild('tab');//通过id获取
        DIR_TREE = control.getChild('mytree', true);  //级联查找树节点

        DIR_TREE.on('itemclick', function (ev) {
            var node = ev.item,
                text = node.text,
                href = node.href;

            $('#file_info').text(node.file_info);
            if (href && node.parent.id) {
                var api_name = node.parent.id.split('_').slice(-1)[0] ? node.parent.id.split('_').slice(-1)[0] : node.parent.id.split('_').slice(-2)[0];
                var day_str = text.split('-').slice(-1)[0].split('.')[0];
                LOG_TAB.addTab({
                    title: api_name + '-' + day_str,
                    href: href,
                    id: href
                });
            }
        });
        $('#btn_filter').on('click', function () {
            var tmp = LOG_TAB.getActivedItem();
            if (!tmp) {
                return;
            }
            tmp.reload();  // TODO
        });
        $('#btn_reload').on('click', function () {
            var tmp = LOG_TAB.getActivedItem();
            if (!tmp) {
                return;
            }
            tmp.reload();
        });
        $('#btn_home').on('click', function () {
            var tmp = LOG_TAB.getActivedItem();
            if (!tmp) {
                return;
            }
            var file_url = tmp.getAttrVals().href;
            file_url = file_url.replace('scroll_to=end', 'scroll_to=home');
            tmp.set('href', file_url);
            tmp.reload();
        });
        $('#btn_end').on('click', function () {
            var tmp = LOG_TAB.getActivedItem();
            if (!tmp) {
                return;
            }
            var file_url = tmp.getAttrVals().href;
            file_url = file_url.replace('scroll_to=home', 'scroll_to=end');
            tmp.set('href', file_url);
            tmp.reload();
        });
        $('#btn_download').on('click', function () {
            var tmp = LOG_TAB.getActivedItem();
            if (!tmp) {
                return;
            }
            var file_url = tmp.getAttrVals().href;
            file_url = file_url.replace('showlogfile', 'downloadlogfile');
            window.open(file_url);
        });
        $('.checkbox').on('change', function() {
            var tag = trim( $(this).text() ).toLowerCase();
            var is_show = $(this).find('input[type=checkbox]').is(':checked');
            $('iframe').each(function(idx, obj){
                is_show ? $(obj.contentDocument).find('.log-' + tag).show() : $(obj.contentDocument).find('.log-' + tag).hide();
            });
        });
        $('#btn_clear').on('click', function () {
            var tmp = LOG_TAB.getActivedItem();
            if (!tmp) {
                return;
            }
            var file_url = tmp.getAttrVals().href;
            file_url = file_url.replace('showlogfile', 'ajaxclearlogfile');
            $.ajax({
                type: "GET",
                url: file_url,
                dataType: "json",
                success: function (data) {
                    if (data.error) {
                        console.error(data.error);
                        alert(data.msg);
                    } else {
                        tmp.reload();
                    }
                }
            });
        });
    });
</script>
</body>
</html>
