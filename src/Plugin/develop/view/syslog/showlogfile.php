<?php
/** @var string $json_dir */
/** @var string $tool_title */
/** @var \Tiny\Interfaces\RequestInterface $request */
/** @var array $routeInfo */
/** @var string $color_type */
/** @var string $file_str */
/** @var string $scroll_to */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?= $tool_title ?></title>
    <script src="http://cdn.bootcss.com/jquery/1.11.1/jquery.min.js"></script>
    <style type="text/css">
        .log_pre {
            white-space: pre-wrap;
            word-wrap: break-word;
            margin-left: 20px;
            margin-right: 10px;
            line-height: 20px;
        }

        .log_idx {
            padding: 3px 3px 3px 3px;
            color: white;
            background: #887ddd;
            border-radius: 4px;
        }

        .log_green {
            padding: 3px;
            color: white;
            background: #8cc540;
            border-radius: 4px;
        }

        .log_blue {
            padding: 3px;
            color: white;
            background: #157FCC;
            border-radius: 4px;
        }

        .log_yellow {
            padding: 3px;
            color: white;
            background: #ff8345;
            border-radius: 4px;
        }

        .log_red {
            padding: 3px;
            color: white;
            background: red;
            border-radius: 4px;
        }

        .log_black {
            padding: 3px;
            color: white;
            background: black;
            border-radius: 4px;
        }
    </style>
</head>
<body>
<pre class="log_pre" id="log_code"><?= $color_type == 'default' ? $file_str : '' ?></pre>
<script type="text/javascript">
    var START_TIME = new Date().getTime();
    var SCROLL_TO = '<?=$scroll_to?>';
    var FILE_STR = <?=$color_type == 'default' ? json_encode('') : json_encode(htmlspecialchars($file_str)) ?>;

    function trim(str) { //删除左右两端的空格
        return str.replace(/(^\s*)|(\s*$)/g, "");
    }

    $(function () {
        console.info('before split:', Math.round((new Date().getTime() - START_TIME)), 'ms');
        var str_array = FILE_STR.split("\n");
        console.info('after split:', Math.round((new Date().getTime() - START_TIME)), 'ms');
        var idx = 0;
        var tmp_str = '';
        var tag_map = {
            'DEBUG': 'log_green',
            'INFO': 'log_blue',
            'WARN': 'log_yellow',
            'ERROR': 'log_red',
            'FATAL': 'log_black'
        };
        var has_tag = false;
        for (var i = 0; i < str_array.length; i++) {
            idx = i + 1;
            tmp_str = str_array[i];
            has_tag = false;
            for (var tag in tag_map) {
                if (tag_map.hasOwnProperty(tag) && tmp_str.indexOf('[' + tag + ']') >= 0) {
                    var span_html = '<span class="log-' + tag.toLowerCase() + '">';
                    str_array[i] = (i > 0 ? '</span>' + span_html : span_html) + '<b class="log_idx">' + idx + '</b>&nbsp;' + tmp_str.replace('[' + tag + ']', '[<b class="' + tag_map[tag] + '">' + tag + '</b>]');
                    has_tag = true;
                    break;
                }
            }
            if (!has_tag && trim(tmp_str)) {
                str_array[i] = tmp_str;
            }
        }
        console.info('after for:', Math.round((new Date().getTime() - START_TIME)), 'ms');
        $('#log_code').html(str_array.join("\n"));
        console.info('add html:', Math.round((new Date().getTime() - START_TIME)), 'ms');
        if (SCROLL_TO === 'end') {
            var h = $(document).height() - $(window).height();
            $(document).scrollTop(h);
        }
    });
</script>
</body>
</html>