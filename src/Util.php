<?php
/**
 * Created by PhpStorm.
 * User: kongl
 * Date: 2017/12/15 0015
 * Time: 11:47
 */

namespace Tiny;


abstract class Util
{

    public static function assoc_array(array $var)
    {
        return empty ($var) || array_keys($var) === range(0, sizeof($var) - 1);
    }

    public static function deep_merge(array $arr1, array $arr2)
    {
        if (static::assoc_array($arr1) || static::assoc_array($arr2)) {
            return array_merge($arr1, $arr2);
        }
        foreach ($arr1 as $key => $item) {
            if (isset($arr2[$key])) {
                if (is_array($item) && is_array($arr2[$key])) {
                    $arr1[$key] = static::deep_merge($item, $arr2[$key]);
                } else {
                    $arr1[$key] = $arr2[$key];
                }
            }
        }
        foreach ($arr2 as $key => $item) {
            if (!isset($arr1[$key])) {
                $arr1[$key] = $item;
            }
        }
        return $arr1;
    }

    public static function file_name($path)
    {
        $path = trim($path);
        if (empty($path)) {
            return '';
        }
        $idx = strpos($path, '?');
        $path = $idx > 0 ? substr($path, 0, $idx) : $path;
        $idx = strpos($path, '#');
        $path = $idx > 0 ? substr($path, 0, $idx) : $path;
        $idx = strrpos($path, '/');
        if ($idx !== false) {
            $path = substr($path, $idx + 1);
        }
        $idx = strrpos($path, '\\');
        if ($idx !== false) {
            $path = substr($path, $idx + 1);
        }
        return $path;
    }


    public static function dsl($str, $split = '#', $kv = '=')
    {
        list($str, $split, $kv) = [trim($str), trim($split), trim($kv)];
        if (empty($str)) {
            return [
                'base' => $str,
                'args' => [],
            ];
        }

        $matchs = [];
        $reg = "/{$split}([A-Za-z0-9_]+){$kv}([A-Za-z0-9_]*)/";
        preg_match_all($reg, $str, $matchs);
        $args = [];
        foreach ($matchs[0] as $item) {
            $str = str_replace($item, '', $str);
        }

        foreach ($matchs[1] as $idx => $key) {
            $val = $matchs[2][$idx];
            $args[$key] = is_numeric($val) ? ($val + 0) : $val;
        }
        return [
            'base' => $str,
            'args' => $args,
        ];
    }

    public static function _class()
    {
        return static::class;
    }

    public static function _namespace()
    {
        return __NAMESPACE__;
    }

    public static function split_seq($str, $skip = 3, $seq = ',')
    {
        $str = strval($str);
        $str_len = static::utf8_strlen($str);
        if ($str_len <= $skip) {
            return $str;
        }

        $char_list = [];
        for ($idx = 0; $idx < $str_len; $idx++) {
            $char_list[] = static::utf8_substr($str, $idx, 1);
        }
        $char_list = array_reverse($char_list);
        $out_list = [];
        foreach ($char_list as $idx => $char) {
            $out_list[] = $idx > 0 && $idx % $skip == 0 ? "{$char}{$seq}" : $char;
        }
        return join('', array_reverse($out_list));
    }

    public static function utf8_substr($str, $start, $length = null, $suffix = "")
    {
        if (is_null($length)) {
            $length = static::utf8_strlen($str) - $start;
        }
        if (function_exists("mb_substr")) {
            $slice = mb_substr($str, $start, $length, "utf-8");
        } elseif (function_exists('iconv_substr')) {
            $slice = iconv_substr($str, $start, $length, "utf-8");
        } else {
            $re = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
            preg_match_all($re, $str, $match);
            $slice = join("", array_slice($match[0], $start, $length));
        }
        return $slice . (static::utf8_strlen($slice) < static::utf8_strlen($str) ? $suffix : '');
    }

    /**
     * 获取某年某月最大天数
     * @param int $year 年
     * @param int $month 月
     * @return int 最大天数
     */
    public static function max_days($year, $month)
    {
        return $month == 2 ? ($year % 4 != 0 ? 28 : ($year % 100 != 0 ? 29 : ($year % 400 != 0 ? 28 : 29))) : (($month - 1) % 7 % 2 != 0 ? 30 : 31);
    }

    /**
     * 20120304 日期转为时间戳
     * @param int $per_day
     * @return false|int
     */
    public static function intday2time($per_day)
    {
        $per_day = intval($per_day);
        $month = floor($per_day / 100) % 100;
        $day = $per_day % 100;
        $year = floor($per_day / 10000);
        return mktime(0, 0, 0, $month, $day, $year);
    }

    public static function url_query($url, $need)
    {
        $tmp = "{$need}=";
        $idx = strpos($url, $tmp);
        if (empty($idx)) {
            return '';
        }
        $idx += strlen($tmp);
        $end = strpos($url, '&', $idx);
        $len = ($end > $idx) ? $end - $idx : strlen($url) - $idx;
        $rst = substr($url, $idx, $len);
        return urldecode($rst);
    }

    public static function mime_content_type($filename)
    {

        $mime_types = [

            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',

            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',

            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',

            // audio/video
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',

            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',

            // ms office
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',

            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        ];
        $tmp = explode('.', $filename);
        $ext = strtolower(array_pop($tmp));
        if (array_key_exists($ext, $mime_types)) {
            return $mime_types[$ext];
        } elseif (function_exists('finfo_open')) {
            $info = finfo_open(FILEINFO_MIME);
            $mime = finfo_file($info, $filename);
            finfo_close($info);
            return $mime;
        } else {
            return 'application/octet-stream';
        }
    }

    public static function jsonEncode($var)
    {
        if (function_exists('json_encode')) {
            return json_encode($var);
        } else {
            switch (gettype($var)) {
                case 'boolean':
                    return $var ? 'true' : 'false';
                case 'integer':
                case 'double':
                    return $var;
                case 'resource':
                case 'string':
                    return '"' . str_replace(["\r", "\n", "<", ">", "&"],
                            ['\r', '\n', '\x3c', '\x3e', '\x26'],
                            addslashes($var)) . '"';
                case 'array':
                    if (empty ($var) || array_keys($var) === range(0, sizeof($var) - 1)) {
                        $output = [];
                        foreach ($var as $v) {
                            $output[] = static::jsonEncode($v);
                        }
                        return '[ ' . implode(', ', $output) . ' ]';
                    } else {
                        $output = [];
                        foreach ($var as $k => $v) {
                            $output[] = static::jsonEncode(strval($k)) . ': ' . static::jsonEncode($v);
                        }
                        return '{ ' . implode(', ', $output) . ' }';
                    }
                case 'object':
                    $output = [];
                    foreach ($var as $k => $v) {
                        $output[] = static::jsonEncode(strval($k)) . ': ' . static::jsonEncode($v);
                    }
                    return '{ ' . implode(', ', $output) . ' }';
                default:
                    return 'null';
            }
        }
    }

    public static function mkdir_r($dir, $rights = 666)
    {
        if (!is_dir($dir)) {
            static::mkdir_r(dirname($dir), $rights);
            mkdir($dir, $rights);
        }
    }

    public static function getfiles($path, array $last = [])
    {
        foreach (scandir($path) as $afile) {
            if ($afile == '.' || $afile == '..') {
                continue;
            }
            $_path = "{$path}/{$afile}";
            if (is_dir($_path)) {
                $last = array_merge($last, static::getfiles($_path, $last));
            } else if (is_file($_path)) {
                $last[$_path] = $afile;
            }
        }
        return $last;
    }

    ##########################
    ######## 数组处理 ########
    ##########################

    /**
     * 从一个数组中提取需要的key  缺失的key设置为空字符串
     * @param array $arr 原数组
     * @param array $need 需要的key 列表
     * @param string $default 默认值
     * @return array 需要的key val数组
     */
    public static function filter_keys(array $arr, array $need, $default = '')
    {
        $rst = [];
        foreach ($need as $val) {
            $rst[$val] = isset($arr[$val]) ? $arr[$val] : $default;
        }
        return $rst;
    }

    /**
     * 过滤列表的每一个元素  取出需要的key
     * @param array $list 列表 每行为一个数组
     * @param array $need 需要的 keys 列表
     * @return array
     */
    public static function filter_list(array $list, array $need)
    {
        $need_map = [];
        foreach ($need as $n) {
            $need_map[$n] = 1;
        }
        $ret = [];
        foreach ($list as $item) {
            $tmp = [];
            foreach ($item as $k => $v) {
                if (isset($need_map[$k])) {
                    $tmp[$k] = $v;
                }
            }
            $ret[] = $tmp;
        }
        return $ret;
    }


    /**
     * 获取一个数组的指定键值 未设置则使用 默认值
     * @param array $val
     * @param string $key
     * @param mixed $default 默认值 默认为 null
     * @return mixed
     */
    public static function v(array $val, $key, $default = null)
    {
        return isset($val[$key]) ? $val[$key] : $default;
    }

    public static function vl(array $val, array $keys)
    {
        $ret = [];
        foreach ($keys as $key => $default) {
            $ret[] = static::v($val, $key, $default);
        }
        return $ret;
    }

    ##########################
    ######## 时间处理 ########
    ##########################

    /**
     * 在指定时间 上添加N个月的日期字符串
     * @param string $time_str 时间字符串
     * @param int $add_month 需要增加的月数
     * @return string 返回date('Y-m-d H:i:s') 格式的日期字符串
     */
    public static function add_month($time_str, $add_month)
    {
        if ($add_month <= 0) {
            return $time_str;
        }

        $arr = date_parse($time_str);
        $tmp = $arr['month'] + $add_month;
        $arr['month'] = $tmp > 12 ? ($tmp % 12) : $tmp;
        $arr['year'] = $tmp > 12 ? $arr['year'] + intval($tmp / 12) : $arr['year'];
        if ($arr['month'] == 0) {
            $arr['month'] = 12;
            $arr['year'] -= 1;
        }
        $max_days = $arr['month'] == 2 ? ($arr['year'] % 4 != 0 ? 28 : ($arr['year'] % 100 != 0 ? 29 : ($arr['year'] % 400 != 0 ? 28 : 29))) : (($arr['month'] - 1) % 7 % 2 != 0 ? 30 : 31);
        $arr['day'] = $arr['day'] > $max_days ? $max_days : $arr['day'];
        //fucking the Y2K38 bug
        $hour = !empty($arr['hour']) ? $arr['hour'] : 0;
        $minute = !empty($arr['minute']) ? $arr['minute'] : 0;
        $second = !empty($arr['second']) ? $arr['second'] : 0;
        return sprintf('%d-%02d-%02d %02d:%02d:%02d', $arr['year'], $arr['month'], $arr['day'], $hour, $minute, $second);
    }

    /**
     * 计算两个时间戳的差值
     * @param int $stime 开始时间戳
     * @param int $etime 结束时间错
     * @return array  时间差 ["day" => $days, "hour" => $hours, "min" => $mins, "sec" => $secs]
     */
    public static function diff_time($stime, $etime)
    {
        $sub_sec = abs(intval($etime - $stime));
        $days = intval($sub_sec / 86400);
        $remain = $sub_sec % 86400;
        $hours = intval($remain / 3600);
        $remain = $remain % 3600;
        $mins = intval($remain / 60);
        $secs = $remain % 60;
        return ["day" => $days, "hour" => $hours, "min" => $mins, "sec" => $secs];
    }

    /**
     * 计算两个时间戳的差值 字符串
     * @param int $stime 开始时间戳
     * @param int $etime 结束时间错
     * @return string  时间差 xx小时xx分xx秒
     */
    public static function str_time($stime, $etime)
    {
        $c = abs(intval($etime - $stime));
        $s = $c % 60;
        $c = ($c - $s) / 60;
        $m = $c % 60;
        $h = ($c - $m) / 60;
        $rst = $h > 0 ? "{$h}小时" : '';
        $rst .= $m > 0 ? "{$m}分" : '';
        $rst .= $s > 0 ? "{$s}秒" : '';
        return $rst;
    }

    /**
     * 计算两个时间戳的差值 字符串
     * @param $c
     * @return string 时间差 xx小时xx分xx秒
     */
    public static function interval2str($c)
    {
        $c = abs(intval($c));
        $s = $c % 60;
        $c = ($c - $s) / 60;
        $m = $c % 60;
        $h = ($c - $m) / 60;
        $rst = $h > 0 ? "{$h}小时" : '';
        $rst .= $m > 0 ? "{$m}分" : '';
        $rst .= $s > 0 ? "{$s}秒" : '';
        return $rst;
    }

    ##########################
    ######## 字符串处理 ########
    ##########################

    /**
     * 检查字符串是否包含指定关键词
     * @param string $str 需检查的字符串
     * @param string $filter_str 关键词字符串 使用 $split_str 分隔
     * @param string $split_str 分割字符串
     * @return bool 是否允许通过 true 不含关键词  false 含有关键词
     */
    public static function pass_filter($str, $filter_str, $split_str = '|')
    {
        $filter = explode($split_str, $filter_str);
        foreach ($filter as $val) {
            $val = trim($val);
            if ($val != '') {
                $test = stripos($str, $val);
                if ($test !== false) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Byte 数据大小  格式化 为 字符串
     * @param int $num 大小
     * @param string $in_tag 输入单位
     * @param string $out_tag 输出单位  为空表示自动尝试 最适合的单位
     * @param int $dot 小数位数 默认为2
     * @return string
     */
    public static function byte2size($num, $in_tag = '', $out_tag = '', $dot = 2)
    {
        $num = $num * 1.0;
        $out_tag = strtoupper($out_tag);
        $in_tag = strtoupper($in_tag);
        $dot = $dot > 0 ? intval($dot) : 0;
        $tag_map = ['K' => 1024, 'M' => 1024 * 1024, 'G' => 1024 * 1024 * 1024, 'T' => 1024 * 1024 * 1024 * 1024];
        if (!empty($in_tag) && isset($tag_map[$in_tag])) {
            $num = $num * $tag_map[$in_tag];  //正确转换输入数据 去掉单位
        }
        $zero_list = [];
        for ($i = 0; $i < $dot; $i++) {
            $zero_list[] = '0';
        }
        $zero_str = '.' . join($zero_list, '');  // 构建字符串 .00 用于替换 1.00G 为 1G
        if ($num < 1024) {
            return str_replace($zero_str, '', sprintf("%.{$dot}f", $num));
        } else if (!empty($out_tag) && isset($tag_map[$out_tag])) {
            $tmp = round($num / $tag_map[$out_tag], $dot);
            return str_replace($zero_str, '', sprintf("%.{$dot}f", $tmp)) . $out_tag;  //使用设置的单位输出
        } else {
            foreach ($tag_map as $key => $val) {  //尝试找到一个合适的单位
                $tmp = round($num / $val, $dot);
                if ($tmp >= 1 && $tmp < 1024) {
                    return str_replace($zero_str, '', sprintf("%.{$dot}f", $tmp)) . $key;
                }
            }
            //未找到合适的单位  使用最大 tag T 进行输出
            return static::byte2size($num, '', 'T', $dot);
        }
    }

    public static function anonymous_telephone($telephone, $start_num = 3, $end_num = 4)
    {
        if (empty($telephone)) {
            return '';
        }
        $len = strlen($telephone);
        $min_len = $start_num + $end_num;
        if ($len <= $min_len) {
            return $telephone;
        }
        return substr($telephone, 0, $start_num) . str_repeat('*', $len - $min_len) . substr($telephone, -$end_num);
    }

    public static function anonymous_email($email, $start_num = 3)
    {
        if (empty($email)) {
            return '';
        }
        $idx = strpos($email, '@');
        if ($idx <= $start_num) {
            return $email;
        }
        return substr($email, 0, $start_num) . str_repeat('*', $idx - $start_num) . substr($email, $idx);
    }

    public static function str_cmp($str1, $str2)
    {
        list($str1, $str2) = [strval($str1), strval($str2)];
        if (!function_exists('hash_equals')) {
            if (strlen($str1) != strlen($str2)) {
                return false;
            } else {
                $res = $str1 ^ $str2;
                $ret = 0;
                for ($i = strlen($res) - 1; $i >= 0; $i--) {
                    $ret |= ord($res[$i]);
                }
                return !$ret;
            }
        } else {
            return hash_equals($str1, $str2);
        }
    }

    public static function stri_cmp($str1, $str2)
    {
        return static::str_cmp(strtolower($str1), strtolower($str2));
    }

    public static function str_startwith($str, $needle)
    {
        $len = strlen($needle);
        if ($len == 0) {
            return true;
        }
        $tmp = substr($str, 0, $len);
        return static::str_cmp($tmp, $needle);

    }

    public static function str_endwith($haystack, $needle)
    {
        $len = strlen($needle);
        if ($len == 0) {
            return true;
        }
        $tmp = substr($haystack, -$len);
        return static::str_cmp($tmp, $needle);
    }

    public static function stri_startwith($str, $needle)
    {
        $len = strlen($needle);
        if ($len == 0) {
            return true;
        }
        $tmp = substr($str, 0, $len);
        return static::stri_cmp($tmp, $needle);

    }

    public static function stri_endwith($haystack, $needle)
    {
        $len = strlen($needle);
        if ($len == 0) {
            return true;
        }
        $tmp = substr($haystack, -$len);
        return static::stri_cmp($tmp, $needle);
    }

    public static function trimlower($string)
    {
        return strtolower(trim($string));
    }

    ##########################
    ######## 中文处理 ########
    ##########################

    /**
     * 计算utf8字符串长度
     * @param string $content 原字符串
     * @return int utf8字符串 长度
     */
    public static function utf8_strlen($content)
    {
        if (empty($content)) {
            return 0;
        }
        preg_match_all("/./us", $content, $match);
        return count($match[0]);
    }

    /**
     * 把utf8字符串中  gbk不支持的字符过滤掉
     * @param string $content 原字符串
     * @return string  过滤后的字符串
     */
    public static function utf8_gbk_able($content)
    {
        if (empty($content)) {
            return '';
        }
        $content = iconv("UTF-8", "GBK//TRANSLIT", $content);
        $content = iconv("GBK", "UTF-8", $content);
        return $content;
    }

    /**
     * 转换编码，将Unicode编码转换成可以浏览的utf-8编码
     * @param string $ustr 原字符串
     * @return string  转换后的字符串
     */
    public static function unicode_decode($ustr)  //
    {
        $pattern = '/(\\\u([\w]{4}))/i';
        preg_match_all($pattern, $ustr, $matches);
        $utf8_map = [];
        if (!empty($matches)) {
            foreach ($matches[0] as $uchr) {
                if (!isset($utf8_map[$uchr])) {
                    $utf8_map[$uchr] = static::unicode_decode_char($uchr);
                }
            }
        }
        $utf8_map['\/'] = '/';
        if (!empty($utf8_map)) {
            $ustr = str_replace(array_keys($utf8_map), array_values($utf8_map), $ustr);
        }
        return $ustr;
    }

    /**
     * 把 \uXXXX 格式编码的字符 转换为utf-8字符
     * @param string $uchar 原字符
     * @return string  转换后的字符
     */
    public static function unicode_decode_char($uchar)
    {
        $code = base_convert(substr($uchar, 2, 2), 16, 10);
        $code2 = base_convert(substr($uchar, 4), 16, 10);
        $char = chr($code) . chr($code2);
        $char = iconv('UCS-2', 'UTF-8', $char);
        return $char;
    }

    ##########################
    ######## 编码相关 ########
    ##########################

    public static function safe_base64_encode($str)
    {
        $str = rtrim(strtr(base64_encode($str), '+/', '-_'), '=');
        return $str;
    }

    public static function safe_base64_decode($str)
    {
        $str = strtr(trim($str), '-_', '+/');
        $last_len = strlen($str) % 4;
        $str = $last_len == 2 ? $str . '==' : ($last_len == 3 ? $str . '=' : $str);
        $str = base64_decode($str);
        return $str;
    }

    /**
     * @param int $length
     * @return string
     */
    public static function rand_str($length)
    {
        if ($length <= 0) {
            return '';
        }
        $str = '';
        $tmp_str = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($tmp_str) - 1;
        for ($i = 0; $i < $length; $i++) {
            $str .= $tmp_str[rand(0, $max)];   //rand($min,$max)生成介于min和max两个数之间的一个随机整数
        }
        return $str;
    }

    /**
     * 加密函数
     * @param string $string 需要加密的字符串
     * @param string $key
     * @param int $expiry 加密生成的数据 的 有效期 为0表示永久有效， 单位 秒
     * @param string $salt
     * @param int $rnd_length 动态密匙长度 byte $rnd_length>=0，相同的明文会生成不同密文就是依靠动态密匙
     * @param int $chk_length 校验和长度 byte $rnd_length>=4 && $rnd_length><=16
     * @return string 加密结果 使用了 safe_base64_encode
     */
    public static function encode($string, $key, $expiry = 0, $salt = 'salt', $rnd_length = 2, $chk_length = 4)
    {
        return static::authcode(strval($string), 'ENCODE', $key, $expiry, $salt, $rnd_length, $chk_length);
    }

    /**
     * 解密函数 使用 配置 CRYPT_KEY 作为 key  成功返回原字符串  失败或过期 返回 空字符串
     * @param string $string 需解密的 字符串 safe_base64_encode 格式编码
     * @param string $key
     * @param string $salt
     * @param int $rnd_length 动态密匙长度 byte $rnd_length>=0，相同的明文会生成不同密文就是依靠动态密匙
     * @param int $chk_length 校验和长度 byte $rnd_length>=4 && $rnd_length><=16
     * @return string 解密结果
     */
    public static function decode($string, $key, $salt = 'salt', $rnd_length = 2, $chk_length = 4)
    {
        return static::authcode(strval($string), 'DECODE', $key, 0, $salt, $rnd_length, $chk_length);
    }

    public static function int32ToByteWithLittleEndian($int32)
    {
        $int32 = abs(intval($int32));
        $byte0 = $int32 % 256;
        $int32 = ($int32 - $byte0) / 256;
        $byte1 = $int32 % 256;
        $int32 = ($int32 - $byte1) / 256;
        $byte2 = $int32 % 256;
        $int32 = ($int32 - $byte2) / 256;
        $byte3 = $int32 % 256;
        return chr($byte0) . chr($byte1) . chr($byte2) . chr($byte3);
    }

    public static function byteToInt32WithLittleEndian($byte)
    {
        $byte0 = isset($byte[0]) ? ord($byte[0]) : 0;
        $byte1 = isset($byte[1]) ? ord($byte[1]) : 0;
        $byte2 = isset($byte[2]) ? ord($byte[2]) : 0;
        $byte3 = isset($byte[3]) ? ord($byte[3]) : 0;
        return $byte3 * 256 * 256 * 256 + $byte2 * 256 * 256 + $byte1 * 256 + $byte0;
    }

    /**
     * @param string $_string
     * @param string $operation
     * @param string $_key
     * @param int $_expiry
     * @param string $salt
     * @param int $rnd_length 动态密匙长度 byte $rnd_length>=0，相同的明文会生成不同密文就是依靠动态密匙
     * @param int $chk_length 校验和长度 byte $rnd_length>=4 && $rnd_length><=16
     * @return string
     */
    public static function authcode($_string, $operation, $_key, $_expiry, $salt, $rnd_length, $chk_length)
    {
        $rnd_length = $rnd_length > 0 ? intval($rnd_length) : 0;
        $_expiry = $_expiry > 0 ? intval($_expiry) : 0;
        $chk_length = $chk_length > 4 ? ($chk_length < 16 ? intval($chk_length) : 16) : 4;
        $key = md5($salt . $_key . 'origin key');// 密匙
        $keya = md5($salt . substr($key, 0, 16) . 'key a for crypt');// 密匙a会参与加解密
        $keyb = md5($salt . substr($key, 16, 16) . 'key b for check sum');// 密匙b会用来做数据完整性验证

        if ($operation == 'DECODE') {
            $keyc = $rnd_length > 0 ? substr($_string, 0, $rnd_length) : '';// 密匙c用于变化生成的密文
            $crypt = $keya . md5($salt . $keya . $keyc . 'merge key a and key c');// 参与运算的密匙
            // 解码，会从第 $keyc_length Byte开始，因为密文前 $keyc_length Byte保存 动态密匙
            $string = static::safe_base64_decode(substr($_string, $rnd_length));
            $result = static::encodeByXor($string, $crypt);
            // 验证数据有效性
            $result_len_ = strlen($result);
            $expiry_at_ = $result_len_ >= 4 ? static::byteToInt32WithLittleEndian(substr($result, 0, 4)) : 0;
            $pre_len = 4 + $chk_length;
            $checksum_ = $result_len_ >= $pre_len ? bin2hex(substr($result, 4, $chk_length)) : 0;
            $string_ = $result_len_ >= $pre_len ? substr($result, $pre_len) : '';
            $tmp_sum = substr(md5($salt . $string_ . $keyb), 0, 2 * $chk_length);
            $test_pass = ($expiry_at_ == 0 || $expiry_at_ > time()) && $checksum_ == $tmp_sum;
            return $test_pass ? $string_ : '';
        } else {
            $keyc = $rnd_length > 0 ? static::rand_str($rnd_length) : '';// 密匙c用于变化生成的密文
            $checksum = substr(md5($salt . $_string . $keyb), 0, 2 * $chk_length);
            $expiry_at = $_expiry > 0 ? $_expiry + time() : 0;
            $crypt = $keya . md5($salt . $keya . $keyc . 'merge key a and key c');// 参与运算的密匙
            // 加密，原数据补充附加信息，共 8byte  前 4 Byte 用来保存时间戳，后 4 Byte 用来保存 $checksum 解密时验证数据完整性
            $string = static::int32ToByteWithLittleEndian($expiry_at) . hex2bin($checksum) . $_string;
            $result = static::encodeByXor($string, $crypt);
            return $keyc . static::safe_base64_encode($result);
        }
    }

    public static function encodeByXor($string, $crypt)
    {
        $string_length = strlen($string);
        $key_length = strlen($crypt);
        $result_list = [];
        $box = range(0, 255);
        $rndkey = [];
        // 产生密匙簿
        for ($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($crypt[$i % $key_length]);
        }

        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($i + $j + $box[$i] + $box[$j] + $rndkey[$i] + $rndkey[$j]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }

        // 核心加解密部分
        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            // 从密匙簿得出密匙进行异或，再转成字符
            $tmp_idx = ($box[$a] + $box[$j]) % 256;
            $result_list[] = chr(ord($string[$i]) ^ $box[$tmp_idx]);
        }

        $result = join('', $result_list);
        return $result;
    }

    /**
     * xss 清洗数组 尝试对数组中特定字段进行处理
     * @param array $data
     * @param array $keys
     * @return array 清洗后的数组
     */
    public static function xss_filter(array $data, array $keys)
    {
        foreach ($keys as $key) {
            if (!empty($data[$key]) && is_string($data[$key])) {
                $data[$key] = static::xss_clean($data[$key]);
            }
        }
        return $data;
    }

    public static function safe_str($str)
    {
        $safe_chars = str_split('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890-_');
        $safe_map = self::build_map($safe_chars);
        $chars = self::utf8_str_split($str);
        $ret_list = [];
        foreach ($chars as $char) {
            if (!empty($safe_map[$char])) {
                $ret_list[] = $char;
            }
        }
        return join('', $ret_list);
    }

    public static function build_map(array $list)
    {
        $map = [];
        foreach ($list as $item) {
            $map[$item] = 1;
        }
        return $map;
    }

    public static function utf8_str_split($str, $l = 0)
    {
        if ($l > 0) {
            $ret = array();
            $len = mb_strlen($str, "UTF-8");
            for ($i = 0; $i < $len; $i += $l) {
                $ret[] = mb_substr($str, $i, $l, "UTF-8");
            }
            return $ret;
        }
        return preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * xss 过滤函数 清洗字符串
     * @param string $val
     * @return string
     */
    public static function xss_clean($val)
    {
        $val = preg_replace('/([\x00-\x09,\x0a-\x0c,\x0e-\x19])/', '', $val);
        $search = <<<EOT
abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890!@#$%^&*()~`";:?+/={}[]-_|'\<>
EOT;

        for ($i = 0; $i < strlen($search); $i++) {
            // @ @ search for the hex values
            $val = preg_replace('/(&#[xX]0{0,8}' . dechex(ord($search[$i])) . ';?)/i', $search[$i], $val); // with a ;
            // @ @ 0{0,7} matches '0' zero to seven times
            $val = preg_replace('/(&#0{0,8}' . ord($search[$i]) . ';?)/', $search[$i], $val); // with a ;
        }
        $val = preg_replace('/([<,>,",\'])/', '', $val);
        return $val;
    }

    ##########################
    ######## URL相关 ########
    ##########################

    /**
     * 拼接 url get 地址
     * @param string $base_url 基本url地址
     * @param array $args 附加参数
     * @return string  拼接出的网址
     */
    public static function build_get($base_url, array $args = [])
    {
        if (empty($args)) {
            return $base_url;
        }
        $base_url = trim($base_url);
        if (stripos($base_url, '?') > 0) {

        } else {
            $base_url .= substr($base_url, -1, 1) == '/' ? '' : '/';
            $base_url .= stripos($base_url, '?') > 0 ? '' : "?";
        }
        $base_url = (substr($base_url, -1) == '?' || substr($base_url, -1) == '&') ? $base_url : "{$base_url}&";
        $args_list = [];
        foreach ($args as $key => $val) {
            $key = trim($key);
            $args_list[] = "{$key}=" . urlencode($val);
        }
        return !empty($args_list) ? $base_url . join($args_list, '&') : $base_url;
    }

    #########################################
    ########### 魔术常量相关函数 ############
    #########################################

    /**
     * 根据魔术常量获取获取 类名
     * @param string $str
     * @return string
     */
    public static function class2name($str)
    {
        $idx = strripos($str, '::');
        $str = $idx > 0 ? substr($str, 0, $idx) : $str;
        $idx = strripos($str, '\\');
        $str = $idx > 0 ? substr($str, $idx + 1) : $str;
        return $str;
    }

    /**
     * 根据魔术常量获取获取 函数名
     * @param string $str
     * @return string
     */
    public static function method2name($str)
    {
        $idx = strripos($str, '::');
        $str = $idx > 0 ? substr($str, $idx + 2) : $str;
        return $str;
    }

    /**
     * 根据魔术常量获取获取 函数名 并转换为 小写字母加下划线格式 的 字段名
     * @param string $str
     * @return string
     */
    public static function method2field($str)
    {
        $str = static::method2name($str);
        return static::humpToLine($str);
    }

    /**
     * 根据魔术常量获取获取 类名 并转换为 小写字母加下划线格式 的 数据表名
     * @param string $str
     * @return string
     */
    public static function class2table($str)
    {
        $str = static::class2name($str);
        return static::humpToLine($str);
    }

    /**
     * 下划线转驼峰
     * @param string $str
     * @return string
     */
    public static function convertUnderline($str)
    {
        $str = preg_replace_callback('/([-_]+([a-z]{1}))/i', function ($matches) {
            return strtoupper($matches[2]);
        }, $str);
        return $str;
    }

    /**
     * 驼峰转下划线
     * @param string $str
     * @return string
     */
    public static function humpToLine($str)
    {
        return strtolower(preg_replace('/((?<=[a-z])(?=[A-Z]))/', '_', $str));
    }

    /**
     * 使用 seq 把 list 数组中的非空字符串连接起来  _join('_', [1,2,3]) = '1_2_3'
     * @param string $seq
     * @param array $list
     * @return string
     */
    public static function joinNotEmpty($seq, array $list)
    {
        $tmp_list = [];
        foreach ($list as $item) {
            $item = trim(strval($item));
            if ($item !== '') {
                $tmp_list[] = strval($item);
            }
        }
        return join($seq, $tmp_list);
    }

    public static function splitNotEmpty($seq, $str)
    {
        $ret_list = [];
        foreach (explode($seq, $str) as $item) {
            $tmp = trim($item);
            if (!empty($tmp)) {
                $ret_list[] = $tmp;
            }
        }
        return $ret_list;
    }

    /**
     * 合并两个数组 复制 $arr2 中 非空的值到  $arr1 对应的 key
     * @param array $arr1
     * @param array $arr2
     * @return array
     */
    public static function mergeNotEmpty(array $arr1, array $arr2)
    {
        foreach ($arr2 as $key => $val) {
            $val = trim($val);
            if (!empty($val)) {
                $arr1[$key] = $val;
            }
        }
        return $arr1;
    }

    /**
     * 获取当前请求的 url
     * @param string $sys_host
     * @param string $request_uri
     * @return string
     */
    public static function build_url($sys_host, $request_uri = '/')
    {
        $uri = !empty($request_uri) ? $request_uri : '/';
        $uri = static::str_startwith($uri, '/') ? substr($uri, 1) : $uri;
        $sys_host = static::str_endwith($sys_host, '/') ? $sys_host : "{$sys_host}/";
        $url = "{$sys_host}{$uri}";
        return $url;
    }

    public static function lower_key(array $data)
    {
        $rst = [];
        foreach ($data as $key => $item) {
            $key = static::trimlower($key);
            $rst[$key] = $item;
        }
        return $rst;
    }


    public static function get_port($url, $default_post = 80)
    {
        $s_idx = stripos($url, '://');
        if ($s_idx === false) {
            return $default_post;
        }
        $url = substr($url, $s_idx + 3);
        $domain = explode('/', $url)[0];
        $p_idx = strrpos($domain, ':');
        if ($p_idx === false) {
            return $default_post;
        }
        return intval(substr($domain, $p_idx + 1));
    }


    /**
     * post请求url，并返回结果
     * @param string $query_url
     * @param array $header
     * @param string $type
     * @param array $post_fields
     * @param int $base_auth
     * @param int $timeout
     * @param bool $is_log
     * @return array
     */
    public static function curlRpc($query_url, $header = [], $type = 'GET', $post_fields = [], $base_auth = 0, $timeout = 20, $is_log = true)
    {
        $t1 = microtime(true);

        $ch = curl_init();
        $port = static::get_port($query_url, 80);
        curl_setopt($ch, CURLOPT_URL, $query_url);
        curl_setopt($ch, CURLOPT_PORT, $port);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_NOSIGNAL, true);
        if ($base_auth) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        }
        if ($type == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($post_fields)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
            }
        }
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($type));

        if (!empty($header)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }

        //execute post
        $response = curl_exec($ch);
        //get response code
        $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        //close connection
        $http_ok = $response_code == 200 || $response_code == 201 || $response_code == 204;
        $use_time = round(microtime(true) - $t1, 3) * 1000 . 'ms';

        $log_msg = " use:{$use_time}, query_url:{$query_url}, response_code:{$response_code}";
        $total = strlen($response);
        $log_msg .= $total > 500 ? ', rst:' . substr($response, 0, 500) . "...total<{$total}>chars..." : ", rst:{$response}";
        if (!$http_ok) {
            $log_msg .= ', curl_error:' . curl_error($ch);
            $log_msg .= ', curl_errno:' . curl_errno($ch);
            error_log("{$log_msg}");
        } else {
            $is_log && error_log("{$log_msg}");;
        }
        curl_close($ch);
        //return result
        if ($http_ok) {
            $data = json_decode(trim($response), true);
            return !is_null($data) ? $data : ['code' => 0, 'msg' => '接口返回非json', 'resp' => $response];
        } else {
            return ['code' => 500, 'msg' => '调用远程接口失败', 'resp' => $response, 'HttpCode' => $response_code];
        }
    }

}