<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/10 0010
 * Time: 0:15
 */

namespace Tiny;


abstract class Func
{

    public static function _class()
    {
        return static::class;
    }

    public static function _namespace()
    {
        return __NAMESPACE__;
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

    /**
     * xss 过滤函数 清洗字符串
     * @param string $val
     * @return string
     */
    public static function xss_clean($val)
    {
        // this prevents some character re-spacing such as <java\0script>
        // note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs
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
    public static function build_url($base_url, array $args = [])
    {
        if (empty($args)) {
            return $base_url;
        }
        $base_url .= substr($base_url, -1, 1) == '/' ? '' : '/';
        $base_url .= stripos($base_url, '?') > 0 ? '' : "?";
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

    private static $_http_info_cache = [];

    /**
     * 获取当前请求的 url
     * @return string
     */
    public static function this_url()
    {
        if (isset(static::$_http_info_cache[__METHOD__])) {
            return static::$_http_info_cache[__METHOD__];
        }
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
        $sys_host = defined('SYSTEM_HOST') ? SYSTEM_HOST : 'http://localhost/';
        $sys_host = static::str_endwith($sys_host, '/') ? $sys_host : "{$sys_host}/";
        $url = $sys_host . substr($uri, 1);

        static::$_http_info_cache[__METHOD__] = $url;
        return $url;
    }


    /**
     * 获取request 头部信息 全部使用小写名字
     * @return array
     */
    public static function request_header()
    {
        if (isset(static::$_http_info_cache[__METHOD__])) {
            return static::$_http_info_cache[__METHOD__];
        }
        /**
         * 补全 apache_request_headers 函数
         * @return array
         */
        if (!function_exists('apache_request_headers')) {
            function apache_request_headers()
            {
                $arh = [];
                $rx_http = '/\AHTTP_/';
                foreach ($_SERVER as $key => $val) {
                    if (preg_match($rx_http, $key)) {
                        $arh_key = preg_replace($rx_http, '', $key);
                        $rx_matches = explode('_', $arh_key);
                        if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
                            foreach ($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
                            $arh_key = implode('-', $rx_matches);
                        }
                        $arh[$arh_key] = $val;
                    }
                }
                return $arh;
            }
        }

        $header = apache_request_headers();
        if (isset($_SERVER['PHP_AUTH_DIGEST'])) {
            $header['AUTHORIZATION'] = $_SERVER['PHP_AUTH_DIGEST'];
        } elseif (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
            $header['AUTHORIZATION'] = base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $_SERVER['PHP_AUTH_PW']);
        }
        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $header['CONTENT-LENGTH'] = $_SERVER['CONTENT_LENGTH'];
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $header['CONTENT-TYPE'] = $_SERVER['CONTENT_TYPE'];
        }
        foreach ($header as $key => $item) {
            $header[strtolower($key)] = $item;
        }

        static::$_http_info_cache[__METHOD__] = $header;
        return $header;
    }

    /**
     * 根据 HTTP_USER_AGENT 获取客户端浏览器信息
     * @return array 浏览器相关信息 ['name', 'version']
     */
    public static function agent_browser()
    {
        if (isset(static::$_http_info_cache[__METHOD__])) {
            return static::$_http_info_cache[__METHOD__];
        }
        $browser = [];
        $sys = $_SERVER['HTTP_USER_AGENT'];  //获取用户代理字符串
        if (stripos($sys, "Firefox/") > 0) {
            preg_match("/Firefox\/([^;)]+)+/i", $sys, $b);
            $browser[0] = "Firefox";
            $browser[1] = $b[1];  //获取火狐浏览器的版本号
        } elseif (stripos($sys, "Maxthon") > 0) {
            preg_match("/Maxthon\/([\d\.]+)/", $sys, $maxthon);
            $browser[0] = "Maxthon";
            $browser[1] = $maxthon[1];
        } elseif (stripos($sys, "MSIE") > 0) {
            preg_match("/MSIE\s+([^;)]+)+/i", $sys, $ie);
            $browser[0] = "IE";
            $browser[1] = $ie[1];  //获取IE的版本号
        } elseif (stripos($sys, "OPR") > 0) {
            preg_match("/OPR\/([\d\.]+)/", $sys, $opera);
            $browser[0] = "Opera";
            $browser[1] = $opera[1];
        } elseif (stripos($sys, "Edge") > 0) {
            //win10 Edge浏览器 添加了chrome内核标记 在判断Chrome之前匹配
            preg_match("/Edge\/([\d\.]+)/", $sys, $Edge);
            $browser[0] = "Edge";
            $browser[1] = $Edge[1];
        } elseif (stripos($sys, "Chrome") > 0) {
            preg_match("/Chrome\/([\d\.]+)/", $sys, $google);
            $browser[0] = "Chrome";
            $browser[1] = $google[1];  //获取google chrome的版本号
        } elseif (stripos($sys, 'rv:') > 0 && stripos($sys, 'Gecko') > 0) {
            preg_match("/rv:([\d\.]+)/", $sys, $IE);
            $browser[0] = "IE";
            $browser[1] = $IE[1];
        } else {
            $browser[0] = "UNKNOWN";
            $browser[1] = "";
        }

        static::$_http_info_cache[__METHOD__] = $browser;
        return $browser;
    }

    public static function is_mobile()
    {
        if (isset(static::$_http_info_cache[__METHOD__])) {
            return static::$_http_info_cache[__METHOD__];
        }

        $mobile_agents = Array('xiaomi', "240x320", "acer", "acoon", "acs-", "abacho", "ahong", "airness", "alcatel", "amoi", "android", "anywhereyougo.com", "applewebkit/525", "applewebkit/532", "asus", "audio", "au-mic", "avantogo", "becker", "benq", "bilbo", "bird", "blackberry", "blazer", "bleu", "cdm-", "compal", "coolpad", "danger", "dbtel", "dopod", "elaine", "eric", "etouch", "fly ", "fly_", "fly-", "go.web", "goodaccess", "gradiente", "grundig", "haier", "hedy", "hitachi", "htc", "huawei", "hutchison", "inno", "ipad", "ipaq", "ipod", "jbrowser", "kddi", "kgt", "kwc", "lenovo", "lg ", "lg2", "lg3", "lg4", "lg5", "lg7", "lg8", "lg9", "lg-", "lge-", "lge9", "longcos", "maemo", "mercator", "meridian", "micromax", "midp", "mini", "mitsu", "mmm", "mmp", "mobi", "mot-", "moto", "nec-", "netfront", "newgen", "nexian", "nf-browser", "nintendo", "nitro", "nokia", "nook", "novarra", "obigo", "palm", "panasonic", "pantech", "philips", "phone", "pg-", "playstation", "pocket", "pt-", "qc-", "qtek", "rover", "sagem", "sama", "samu", "sanyo", "samsung", "sch-", "scooter", "sec-", "sendo", "sgh-", "sharp", "siemens", "sie-", "softbank", "sony", "spice", "sprint", "spv", "symbian", "tablet", "talkabout", "tcl-", "teleca", "telit", "tianyu", "tim-", "toshiba", "tsm", "up.browser", "utec", "utstar", "verykool", "virgin", "vk-", "voda", "voxtel", "vx", "wap", "wellco", "wig browser", "wii", "windows ce", "wireless", "xda", "xde", "zte");

        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        if (empty($user_agent)) {
            return false;
        }
        $is_mobile = false;
        foreach ($mobile_agents as $device) {//这里把值遍历一遍，用于查找是否有上述字符串出现过
            if (stristr($user_agent, $device)) { //stristr 查找访客端信息是否在上述数组中，不存在即为PC端。
                $is_mobile = true;
                break;
            }
        }

        static::$_http_info_cache[__METHOD__] = $is_mobile;
        return $is_mobile;
    }

    public static function preMd5($str, $crypt_key = '')
    {
        return md5("{$crypt_key}{$str}");
    }


}