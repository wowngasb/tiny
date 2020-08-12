<?php
/**
 * Created by PhpStorm.
 * User: kongl
 * Date: 2017/12/15 0015
 * Time: 11:47
 */

namespace Tiny;

use DateTime;
use DateTimeZone;
use Tiny\Abstracts\AbstractClass;

abstract class Util extends AbstractClass
{

    ##########################
    ######## CMD 相关 ########
    ##########################

    public static function cmd_do($cmd, $args, $print)
    {
        switch ($cmd) {
            case 'crontab':
            case 'cron':
                $msg = static::cmd_crontab($args, $print);
                break;
            case 'run':
                $msg = static::cmd_run($args, $print);
                break;
            case 'commands':
                $msg = static::cmd_commands($args, $print);
                break;
            case 'schedules':
                $msg = static::cmd_schedules($args, $print);
                break;
            case 'clear':
                $msg = static::cmd_clear($args, $print);
                break;
            case '-v':
            case '--version':
            case 'ver':
            case 'version':
                $msg = static:: cmd_version($args, $print);
                break;
            case 'test':
                $msg = static::cmd_test($args, $print);
                break;
            case 'h':
            case '-h':
            case 'help':
                $msg = static::cmd_help($args, $print);
                break;
            default:
                $msg = static::cmd_help($args, $print);
                break;
        }
        return $msg;
    }

    public static function cmd_crontab($args, $print)
    {
        false && func_get_args();
        return __METHOD__ . " not NotImplemented";
    }

    public static function cmd_run($args, $print)
    {
        false && func_get_args();
        return __METHOD__ . " not NotImplemented";
    }

    public static function cmd_commands($args, $print)
    {
        false && func_get_args();
        return __METHOD__ . " not NotImplemented";
    }

    public static function cmd_schedules($args, $print)
    {
        false && func_get_args();
        return __METHOD__ . " not NotImplemented";
    }

    public static function cmd_clear($args, $print)
    {
        false && func_get_args();
        return __METHOD__ . " not NotImplemented";
    }

    public static function cmd_version($args, $print)
    {
        false && func_get_args();
        return __METHOD__ . " not NotImplemented";
    }

    public static function cmd_test($args, $print)
    {
        $print && $print('just TEST, args:' . json_encode($args));
    }

    public static function cmd_help($args, $print)
    {
        false && func_get_args();
        return <<<EOT
usage: php cmd.php <command> [<args>]

These are common commands used in various situations:

run crontab (see also: git help crontab)
   crontab  [ts]    Run Crontab in \app\Console\Kernel::runSchedule(ts, onlyCurrent = true)
   
run commands (see also: git help commands)
   run  cmd    Run Commands in \app\Console\Kernel::runScheduleSite(cmd, ts)

clear app runtime cache (see also: git help clear)
   clear        Clear All RunTime Cache

'php cmd.php help -a' and 'php cmd.php help -g' list available subcommands and some
concept guides. See 'php cmd.php help <command>' or 'php cmd.php help <concept>'
to read about a specific subcommand or concept.
EOT;
    }

    ##########################
    ######## 辅助测试 ########
    ##########################

    public static function _class()
    {
        return static::class;
    }

    public static function _namespace()
    {
        return __NAMESPACE__;
    }

    ##########################
    ######## graphql处理 ########
    ##########################

    public static function graphql_argst(array $variables)
    {
        $ret = [];
        foreach ($variables as $var => $t) {
            $ret[] = '$' . "{$var}: {$t}";
        }
        return join(', ', $ret);
    }

    public static function graphql_argsr(array $variables)
    {
        $ret = [];
        foreach ($variables as $var => $t) {
            $ret[] = "{$var}: " . '$' . "{$var}";
        }
        return join(', ', $ret);
    }

    public static function graphql_fragments(array $fragmentsMap, $name)
    {
        if (empty($fragmentsMap[$name]) || empty($fragmentsMap[$name][0])) {
            return '';
        }
        $f_str = trim($fragmentsMap[$name][0]);
        $f_base = !empty($fragmentsMap[$name][1]) ? $fragmentsMap[$name][1] : [];
        $ret = $f_str;
        foreach ($f_base as $base) {
            $ret .= "\n" . static::graphql_fragments($fragmentsMap, $base);
        }
        return $ret;
    }

    ##########################
    ######## 目录处理 ########
    ##########################

    public static function getText($strContent)
    {
        return preg_replace("/<[^>]+>/is", "", $strContent);
    }

    public static function replaceExt($object, $ext)
    {
        $ext = !self::stri_startwith($ext, '.') ? ".{$ext}" : $ext;
        $idx1 = intval(strrpos($object, '/'));
        $idx2 = intval(strrpos($object, "\\"));
        $s_idx = $idx1 > $idx2 ? $idx1 : $idx2;
        $dot_idx = strrpos($object, '.', $s_idx);
        return $dot_idx !== false ? substr($object, 0, $dot_idx) . $ext : $object . $ext;
    }

    const STATIC_EXT = ['.js', '.css', '.ttf', '.woff', '.jpg', '.jpeg', '.gif', '.png', '.bmp'];

    public static function isStatic($object)
    {
        $object = explode('?', $object)[0];
        $idx1 = intval(strrpos($object, '/'));
        $idx2 = intval(strrpos($object, "\\"));
        $s_idx = $idx1 > $idx2 ? $idx1 : $idx2;
        $dot_idx = strrpos($object, '.', $s_idx);
        if ($dot_idx !== false) {
            $ext = substr($object, $dot_idx);
            if (!empty($ext) && in_array($ext, self::STATIC_EXT)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 遍历文件夹 得到目录结构
     * @param string $path
     * @param string $base_path
     * @return array
     */
    public static function treePath($path, $base_path = '')
    {
        if (empty($base_path)) {
            $base_path = $path;
        }
        if (!is_dir($path) || !is_readable($path)) {
            return [];
        }

        $result = [];
        $temp = [];
        $allfiles = scandir($path);  //获取目录下所有文件与文件夹
        foreach ($allfiles as $key => $filename) {  //遍历一遍目录下的文件与文件夹
            if ($filename == '.' || $filename == '..') {
                continue;
            }
            $fullname = $path . '/' . $filename;  //得到完整文件路径
            $file_item = [
                'name' => $filename,
                'fullname' => $fullname,
                'ctime' => filectime($fullname),
                'mtime' => filemtime($fullname),
                'path' => str_replace($base_path, '', $fullname),
            ];
            if (is_dir($fullname)) { //是目录的话继续递归
                $file_item['type'] = 'dir';
                $file_item['sub'] = self::treePath($fullname, $base_path);
                $file_item['size'] = 0;
                foreach ($file_item['sub'] as $k => $v) {
                    $file_item['size'] += $v['size'];
                }
                $result[] = $file_item;
            } else if (is_file($fullname)) {
                $file_item['type'] = 'file';
                $file_item['size'] = filesize($fullname);
                $temp[] = $file_item;
            }
        }

        foreach ($temp as $key => $tmp) {
            $result[] = $tmp; //这样可以让文件夹排前面，文件在后面
        }
        return $result;
    }

    public static function load_git_ver($base_path)
    {
        $git_head = static::path_join($base_path, ['.git', 'HEAD'], false);
        $git_head = is_file($git_head) ? $git_head : static::path_join($base_path, ['..', '.git', 'HEAD'], false);

        $git_ref = is_file($git_head) && is_readable($git_head) ? trim(file_get_contents($git_head)) : '';
        $git_arr = explode(':', $git_ref);
        $ref_type = trim($git_arr[0]);
        $ref_file = trim($git_arr[1]);
        $git_sha = static::path_join($base_path, ['.git', $ref_file], false);
        $git_ver = is_file($git_sha) && is_readable($git_sha) ? trim(file_get_contents($git_sha)) : '';
        return [
            'git_ref' => $git_ref,
            'ref_type' => $ref_type,
            'git_ver' => $git_ver,
        ];
    }

    public static function path_join($base_path, array $paths = [], $add_last = true, $seq = DIRECTORY_SEPARATOR)
    {
        while (static::str_endwith($base_path, $seq)) {
            $base_path = substr($base_path, 0, -strlen($seq));
        }
        $add_path = static::joinNotEmpty($seq, $paths);
        while (static::str_endwith($add_path, $seq)) {
            $add_path = substr($add_path, 0, -strlen($seq));
        }
        $last_seq = $add_last ? $seq : '';
        return empty($add_path) ? "{$base_path}{$last_seq}" : "{$base_path}{$seq}{$add_path}{$last_seq}";
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

    public static function base_name($path)
    {
        $file_name = static::file_name($path);
        $idx = strrpos($file_name, '.');
        if ($idx !== false) {
            $file_name = substr($file_name, 0, $idx);
        }
        return $file_name;
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

    ##########################
    ######## SQL处理 ########
    ##########################

    public static function mkdir_r($dir, $rights = 666)
    {
        if (!is_dir($dir)) {
            static::mkdir_r(dirname($dir), $rights);
            mkdir($dir, $rights);
        }
    }

    ##########################
    ######## 异常处理 ########
    ##########################

    public static function clear_dir($path)
    {
        if (!is_dir($path)) {    //判断是否是目录
            return;
        }

        foreach (scandir($path) as $afile) {
            if ($afile == '.' || $afile == '..') {
                continue;
            }
            $_path = "{$path}/{$afile}";
            if (is_dir($_path)) {
                static::clear_dir($_path);
                rmdir($_path);
            } else {
                unlink($_path);
            }
        }
        rmdir($path);
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

    /**
     * @param $query
     * @param $params
     * @param string $replaceFmt
     * @return mixed
     */
    public static function prepare_query($query, $params, $replaceFmt = '')
    {
        $keys = [];
        $values = [];

        # build a regular expression for each parameter
        foreach ($params as $key => $value) {
            if (!empty($replaceFmt) && is_string($value)) {
                $value = str_replace('%', $replaceFmt, $value);
            }
            if (is_string($key)) {
                $keys[] = '/:' . $key . '/';
            } else {
                $keys[] = '/[?]/';
            }
            if (is_float($value)) {
                $values[] = "{$value}";
            } elseif (is_numeric($value)) {
                $values[] = intval($value);
            } else {
                $value = str_replace('"', '\\"', $value);
                $values[] = '"' . $value . '"';
            }
        }
        $query = preg_replace($keys, $values, $query, 1, $count);
        return $query;
    }

    ##########################
    ######## 打印变量 ########
    ##########################

    /**
     * @param mixed $ex
     * @param string $base_path
     * @return array
     */
    public static function trace_exception($ex, $base_path = '')
    {
        if (empty($ex) || !$ex instanceof \Exception) {
            return [];
        }
        $traces = $ex->getTrace();
        $_file = $ex->getFile();
        $_line = $ex->getLine();

        $ret = [];
        foreach ($traces as $trace) {
            $need = Util::vl($trace, [
                'args' => [], 'class' => '', 'file' => $_file, 'function' => 'unknown_func', 'line' => $_line, 'type' => '::'
            ]);
            list($args, $class, $file, $function, $line, $type) = [
                $need['args'], $need['class'], $need['file'], $need['function'], $need['line'], $need['type']
            ];
            $arg_list = [];
            if (!empty($args)) {
                foreach ($args as $arg) {
                    $arg_list[] = static::dump_val($arg);
                }
            }
            $args_str = join(',', $arg_list);
            $file_str = !empty($base_path) ? str_replace($base_path, '', $file) : $file;
            $class_str = !empty($class) ? "{$class}{$type}" : '';
            $ret[] = [
                'file_str' => $file_str,
                'line' => $line,
                'class_str' => $class_str,
                'function' => $function,
                'args_str' => $args_str,
            ];
        }
        return $ret;
    }

    /**
     * 获取一个数组的  多个指定键值   第一个数组中不存在的键 设置为第二个数组的 默认值  常用于一次获取多个值
     * @param array $val 数据源 数组
     * @param array $keys 建 默认值 数组 格式为 [key => default, ...]
     * @return array  key 的关联数组
     */
    public static function vl(array $val, array $keys)
    {
        if (empty($val) && empty($keys)) {
            return [];
        }

        $ret = [];
        foreach ($keys as $key => $default) {
            $ret[$key] = static::v($val, $key, $default);
        }
        return $ret;
    }

    ##########################
    ######## DSL处理 ########
    ##########################

    /**
     * 获取一个数组的指定键值 未设置则使用 默认值  常用于获取单个值
     * @param array $val
     * @param string $key
     * @param mixed $default 默认值 默认为 null
     * @return mixed
     */
    public static function v($val, $key, $default = null)
    {
        if (empty($val)) {
            return $default;
        }
        if (is_array($val)) {
            return isset($val[$key]) ? $val[$key] : $default;
        } else {
            return isset($val->{$key}) ? $val->{$key} : $default;
        }
    }

    ##########################
    ######## 数组处理 ########
    ##########################

    public static function dump_val($data, $is_short = false, $max_item = 5, $max_str = 50)
    {
        $type = gettype($data);
        switch ($type) {
            case 'NULL':
                return 'null';
            case 'boolean':
                return ($data ? 'true' : 'false');
            case 'integer':
            case 'double':
            case 'float':
                return $data;
            case 'string':
                $var_str = static::utf8_strlen($data) > $max_str ? self::utf8_substr($data, 0, $max_str) . '...' : $data;
                $tmp = json_encode($var_str);
                return "{$tmp}";
            case 'object':
                $class = get_class($data);
                return "{$class}";
            case 'array':
                if ($is_short) {
                    return "<Array>";
                }
                $output_index_count = 0;
                $output_indexed = [];
                $output_associative = [];
                $idx = 0;
                foreach ($data as $key => $value) {
                    if ($idx >= $max_item) {
                        $output_indexed[] = '...';
                        $output_associative[] = '...';
                        break;
                    }
                    $output_indexed[] = static::dump_val($value, true);
                    $output_associative[] = static::dump_val($key, true) . ':' . static::dump_val($value, true);
                    if ($output_index_count !== NULL && $output_index_count++ !== $key) {
                        $output_index_count = NULL;
                    }
                    $idx += 1;
                }
                if ($output_index_count !== NULL) {
                    return '[' . implode(',', $output_indexed) . ']';
                } else {
                    return '{' . implode(',', $output_associative) . '}';
                }
            default:
                return '<object>'; // Not supported
        }
    }

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

    public static function utf8_fix($str, $max_len)
    {
        $str = trim($str);
        $len = static::utf8_strlen($str);
        if ($len > $max_len) {
            return static::utf8_substr($str, 0, $max_len);
        }
        return $str;
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

    public static function msg_fix($msg, array $fix_replace)
    {
        $search = array_keys($fix_replace);
        $replace = array_values($fix_replace);
        return str_replace($search, $replace, $msg);
    }

    public static function trace_fix(array $trace_list, array $fix_replace)
    {
        $search = array_keys($fix_replace);
        $replace = array_values($fix_replace);
        foreach ($trace_list as &$trace) {
            $args_str = !empty($trace['args_str']) ? $trace['args_str'] : '';
            if (!empty($args_str) && !empty($search) && !empty($replace)) {
                $args_str = str_replace($search, $replace, $args_str);
            }
            $trace['args_str'] = $args_str;
        }
        return $trace_list;
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

    public static function fmap_first($val, array $funcMap, $default = null)
    {
        foreach ($funcMap as $key => $func) {
            if ($func($val)) {
                return $key;
            }
        }
        return $default;
    }

    public static function array_eq($arr1, $arr2)
    {
        sort($arr1);
        sort($arr2);
        return $arr1 == $arr2;
    }

    /**
     * 判断 数组 全部符合 特定条件
     * @param array $arr
     * @param callable|null $func
     * @return bool
     */
    public static function allOfArray(array $arr, callable $func = null)
    {
        if (empty($func)) {
            $func = function ($key, $val) {
                false && func_get_args();
                return (bool)$val;
            };
        }
        foreach ($arr as $k => $v) {
            if (!$func($k, $v)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 判断 数组 是否存在某一个 符合 特定条件
     * @param array $arr
     * @param callable|null $func
     * @return bool
     */
    public static function oneOfArray(array $arr, callable $func = null)
    {
        if (empty($func)) {
            $func = function ($key, $val) {
                false && func_get_args();
                return (bool)$val;
            };
        }
        foreach ($arr as $k => $v) {
            if ($func($k, $v)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 计算 数组 符合 特定条件 的 个数
     * @param array $arr
     * @param callable|null $func
     * @return int
     */
    public static function countOfArray(array $arr, callable $func = null)
    {
        if (empty($func)) {
            $func = function ($key, $val) {
                false && func_get_args();
                return (bool)$val;
            };
        }
        $num = 0;
        foreach ($arr as $k => $v) {
            if ($func($k, $v)) {
                $num += 1;
            }
        }
        return $num;
    }

    /**
     * @param mixed $item
     * @return array|mixed
     */
    public static function try2array($item)
    {
        if (empty($item)) {
            return [];
        }

        if (is_array($item)) {
            return $item;
        } elseif (is_object($item)) {
            if (is_callable([$item, 'toArray'])) {
                return call_user_func_array([$item, 'toArray'], []);
            } elseif ($item instanceof \Iterator) {
                $ret = [];
                foreach ($item as $key => $value) {
                    $ret[$key] = $value;
                }
                return $ret;
            } elseif ($item instanceof \JsonSerializable) {
                $json_str = json_encode($item);
                return json_decode($json_str, true);
            } elseif (is_callable([$item, 'toJson'])) {
                $json_str = call_user_func_array([$item, 'toJson'], []);
                return json_decode($json_str, true);
            } else {
                return (array)$item;
            }
        }

        return (array)$item;
    }

    /**
     * 检查 sortOption 是否合法 并尝试修复
     * @param array $sortOption
     * @param array $allowSortField
     * @param string $defaultField
     * @param string $defaultDirection
     * @return array
     */
    public static function check_sort(array $sortOption, array $allowSortField, $defaultField = '', $defaultDirection = 'asc')
    {
        $defaultDirection = static::trimlower($defaultDirection) == 'desc' ? 'desc' : 'asc';

        $field = static::v($sortOption, 'field', $defaultField);
        $direction = static::v($sortOption, 'direction', $defaultDirection);
        $field = static::trimlower($field);
        $direction = static::trimlower($direction);

        if ($defaultDirection = 'asc') {
            $direction = $direction == 'desc' ? 'desc' : 'asc';
        } else {
            $direction = $direction == 'asc' ? 'asc' : 'desc';
        }

        if (!in_array($field, $allowSortField)) {
            $field = $defaultField;
        }
        return [
            'field' => $field,
            'direction' => $direction,
        ];
    }

    public static function trimlower($string)
    {
        return strtolower(trim($string));
    }

    /**
     * 根据页数计算起始偏移 允许设置最大偏移
     * @param int $page
     * @param int $num
     * @param int $total
     * @param int $max_num
     * @return array
     */
    public static function page_offset($page = 1, $num = 20, $total = -1, $max_num = 100)
    {
        $page = intval($page);
        $num = intval($num);
        $max_num = intval($max_num);
        $total = intval($total);
        $page = $page > 1 ? $page : 1;
        $num = $num > 1 ? $num : 1;
        $num = $num <= $max_num ? $num : $max_num;

        $total = $total == -1 ? -1 : ($total >= 0 ? $total : 0);
        return [
            'offset' => ($page - 1) * $num,
            'num' => $num,
            'page' => $page,
            'max_num' => $max_num,
            'total' => $total,
        ];
    }


    ##########################
    ######## 取值处理 ########
    ##########################


    /**
     * 检查 Range 类型 适合合法
     * @param array $range_arr
     * @param bool $as_int
     * @return array
     */
    public static function get_range(array $range_arr, $as_int = false)
    {
        if ($as_int) {
            return [intval(Util::v($range_arr, 'lower', 0)), intval(Util::v($range_arr, 'upper', 0))];
        } else {
            return [Util::v($range_arr, 'lower', ''), Util::v($range_arr, 'upper', '')];
        }
    }

    /**
     * 检查 Range 类型 适合合法
     * @param array $range_arr
     * @param bool $as_int
     * @return bool
     */
    public static function check_range(array $range_arr, $as_int = false)
    {
        if (empty($range_arr['lower']) && empty($range_arr['upper'])) {
            return false;
        }

        if ($as_int) {
            return intval($range_arr['lower']) <= intval($range_arr['upper']);
        } else {
            return $range_arr['lower'] <= $range_arr['upper'];
        }
    }

    /**
     * @param array $arr_list
     * @param callable|string $key_func 参数 $idx, $item 返回数组的 key  或者字符串
     * @param callable $val_func 参数 $idx, $item  默认为 null  返回  $item
     * @return array
     */
    public static function list2map(array $arr_list, $key_func = 'id', callable $val_func = null)
    {
        if (empty($arr_list)) {
            return [];
        }

        $ret_map = [];
        foreach ($arr_list as $idx => $item) {
            $key = '';
            if (!empty($key_func) && is_callable($key_func)) {
                $key = call_user_func_array($key_func, [$idx, $item]);
            } elseif (is_string($key_func)) {
                $key = self::v($item, $key_func, '');
            }

            if ($key == 0 || !empty($key)) {
                $val = !empty($val_func) && is_callable($val_func) ? call_user_func_array($val_func, [$idx, $item]) : $item;
                if (!is_null($val)) {
                    $ret_map[$key] = $val;
                }
            }
        }
        return $ret_map;
    }

    /**
     * @param array $arr_list
     * @param string $key_str key字符串
     * @param string $val_str val字符串
     * @return array
     */
    public static function list2map_by_key(array $arr_list, $key_str, $val_str)
    {
        if (empty($arr_list)) {
            return [];
        }

        $ret_map = [];
        foreach ($arr_list as $idx => $item) {
            $item = self::try2array($item);
            $key = self::v($item, $key_str, '');
            if ($key == 0 || !empty($key)) {
                $val = self::v($item, $val_str, null);
                if (!is_null($val)) {
                    $ret_map[$key] = $val;
                }
            }
        }
        return $ret_map;
    }

    /**
     * 根据一个 数组的 值 构建一个 字典 常用于去重或判断是否存在
     * @param array $key_list 需要值为 string
     * @param bool $trimlower 是否 去除空格并转为小写
     * @param array $exclude
     * @param bool $as_int
     * @return array set list
     */
    public static function build_map_set($key_list, $trimlower = false, $exclude = [], $as_int = false)
    {
        return array_keys(static::build_map($key_list, $trimlower, 1, $exclude, $as_int));
    }

    /**
     * 根据一个 数组的 值 构建一个 字典 常用于去重或判断是否存在
     * @param array $key_list 需要值为 string
     * @param bool $trimlower 是否 去除空格并转为小写
     * @param int $default
     * @param array $exclude
     * @param bool $as_int
     * @return array 字典 hash
     */
    public static function build_map($key_list, $trimlower = false, $default = 1, $exclude = [], $as_int = false)
    {
        $map = [];
        foreach ($key_list as $key) {
            if ($as_int) {
                $key = intval($key);
                if (!empty($exclude) && in_array($key, $exclude)) {
                    continue;
                }
                if ($key != 0) {
                    $map[$key] = $default;
                }
            } else {
                if ($trimlower) {
                    $key = static::trimlower($key);
                }
                if (!empty($exclude) && in_array($key, $exclude)) {
                    continue;
                }
                if ($key !== '') {
                    $map[$key] = $default;
                }
            }

        }
        return $map;
    }

    /**
     * 取出一个数组 中 值不为空的 所有 key
     * @param array $data
     * @return array
     */
    public static function build_set(array $data)
    {
        $ret = [];
        foreach ($data as $key => $item) {
            if (!empty($item)) {
                $ret[] = $key;
            }
        }
        return $ret;
    }

    /**
     * 把数组 key 都转为 小写  后面的 key 可能覆盖前面的
     * @param array $data
     * @return array
     */
    public static function lower_key(array $data)
    {
        $rst = [];
        foreach ($data as $key => $item) {
            $key = static::trimlower($key);
            $rst[$key] = $item;
        }
        return $rst;
    }

    public static function find_config($key, $default = '', array $config = [])
    {
        $tmp_list = explode('.', $key, 2);
        $pre_key = !empty($tmp_list[0]) ? trim($tmp_list[0]) : '';
        $last_key = !empty($tmp_list[1]) ? trim($tmp_list[1]) : '';
        if (!empty($pre_key)) {
            if (empty($last_key)) {
                return isset($config[$pre_key]) ? $config[$pre_key] : $default;
            }
            $config = isset($config[$pre_key]) ? $config[$pre_key] : [];
            if (!is_array($config)) {
                return $config;
            }
        }
        return static::find_config($last_key, $default, $config);
    }

    public static function def_config($key, $val, $last_val)
    {
        $cfg = [];
        $keyArr = explode('.', $key);

        if (count($keyArr) == 1) {
            if (is_array($last_val) && is_array($val)) {
                $cfg[$key] = static::deep_merge($last_val, $val);
            } else {
                $cfg[$key] = $val;
            }
            return $cfg;
        }

        $tstr = '';
        foreach ($keyArr as $k) {
            $tstr .= $k . '.';
            $k = trim($k);
            if (empty($k)) {
                continue;
            }
            if ($tstr !== $key) {
                $cfg[$k] = [];
                continue;
            }
            if (is_array($last_val) && is_array($val)) {
                $cfg[$k] = static::deep_merge($last_val, $val);
            } else {
                $cfg[$k] = $val;
            }
        }
        return $cfg;
    }

    ##########################
    ######## 时间处理 ########
    ##########################

    /**
     * 深度合并两个数组 优先使用第二个的值覆盖第一个
     * @param array $arr1
     * @param array $arr2
     * @return array
     */
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

    /**
     * 判断一个 数组 为 list 还是 hash
     * @param array $var
     * @return bool  list 返回 true
     */
    public static function assoc_array(array $var)
    {
        return empty($var) || array_keys($var) === range(0, sizeof($var) - 1);
    }

    /**
     * 从指定数组中 取出 指定 key 并去重
     * @param array $list
     * @param string $key
     * @return array
     */
    public static function set_from(array $list, $key = 'id')
    {
        if (empty($list)) {
            return [];
        }
        $ret = [];
        foreach ($list as $item) {
            if (!empty($item[$key])) {
                $ret[$item[$key]] = 1;
            }
        }
        return array_keys($ret);
    }

    /**
     * 从指定数组中 取出 指定 key 生成 map
     *
     * @param array $list
     * @param string $key
     * @param string $default
     * @return array
     */
    public static function map_from(array $list, $key = 'id', $default = '')
    {
        if (empty($list)) {
            return [];
        }
        $ret = [];
        foreach ($list as $k => $item) {
            $ret[$k] = static::v($item, $key, $default);
        }
        return $ret;
    }

    /**
     * 尝试对原数组 进行 数据过滤   返回过滤后的数组
     * @param array $item 原始数组
     * @param array $fix_map 格式为  [key => $default | null, ...]   设置为 null 表示保留原值  否是使用 default 进行替换
     * @return array
     */
    public static function fix_merge(array $item, array $fix_map)
    {
        foreach ($fix_map as $key => $fix) {
            if (!is_null($fix)) {
                $item[$key] = $fix;
            }
        }
        return $item;
    }

    /**
     * 从一个数组中提取需要的key  缺失的key设置为默认值 常用于修复一个数组
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
     * 过滤列表的每一个元素  取出需要的key  返回精简后的元素组成的数组 常用于精简列表
     * @param array $list 列表 每行为一个数组
     * @param array $need 需要的 keys 列表
     * @return array  筛选过后的 list
     */
    public static function filter_list(array $list, array $need)
    {
        if (empty($list)) {
            return [];
        }

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

    public static function mod_timestamp($seq, $stime = 0)
    {
        $stime = is_string($stime) ? strtotime($stime) : $stime;
        $stime = !empty($stime) && $stime > 0 ? intval($stime) : time();

        return intval($stime / $seq) * $seq;
    }

    public static function year_timestamp($stime = 0)
    {
        $stime = is_string($stime) ? strtotime($stime) : $stime;
        $stime = !empty($stime) && $stime > 0 ? intval($stime) : time();
        return strtotime(date('Y-01-01 00:00:00', $stime));
    }

    public static function year_end_timestamp($stime = 0)
    {
        $stime = is_string($stime) ? strtotime($stime) : $stime;
        $stime = !empty($stime) && $stime > 0 ? intval($stime) : time();
        return strtotime(date("Y-12-31 23:59:59", $stime));
    }

    public static function month_timestamp($stime = 0)
    {
        $stime = is_string($stime) ? strtotime($stime) : $stime;
        $stime = !empty($stime) && $stime > 0 ? intval($stime) : time();
        return strtotime(date('Y-m-01 00:00:00', $stime));
    }

    public static function month_end_timestamp($stime = 0)
    {
        $stime = is_string($stime) ? strtotime($stime) : $stime;
        $stime = !empty($stime) && $stime > 0 ? intval($stime) : time();
        $max_day = static::max_days(intval(date('Y', $stime)), intval(date('m', $stime)));
        return strtotime(date("Y-m-{$max_day} 23:59:59", $stime));
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

    public static function day_timestamp($stime = 0)
    {
        $stime = is_string($stime) ? strtotime($stime) : $stime;
        $stime = !empty($stime) && $stime > 0 ? intval($stime) : time();
        return strtotime(date('Y-m-d 00:00:00', $stime));
    }

    public static function day_end_timestamp($stime = 0)
    {
        $stime = is_string($stime) ? strtotime($stime) : $stime;
        $stime = !empty($stime) && $stime > 0 ? intval($stime) : time();
        return strtotime(date('Y-m-d 23:59:59', $stime));
    }

    public static function hour_timestamp($stime = 0)
    {
        $stime = is_string($stime) ? strtotime($stime) : $stime;
        $stime = !empty($stime) && $stime > 0 ? intval($stime) : time();
        return strtotime(date('Y-m-d H:00:00', $stime));
    }

    public static function hour_end_timestamp($stime = 0)
    {
        $stime = is_string($stime) ? strtotime($stime) : $stime;
        $stime = !empty($stime) && $stime > 0 ? intval($stime) : time();
        return strtotime(date('Y-m-d H:59:59', $stime));
    }

    public static function minute_timestamp($stime = 0)
    {
        $stime = is_string($stime) ? strtotime($stime) : $stime;
        $stime = !empty($stime) && $stime > 0 ? intval($stime) : time();
        return strtotime(date('Y-m-d H:i:00', $stime));
    }

    public static function minute_end_timestamp($stime = 0)
    {
        $stime = is_string($stime) ? strtotime($stime) : $stime;
        $stime = !empty($stime) && $stime > 0 ? intval($stime) : time();
        return strtotime(date('Y-m-d H:i:59', $stime));
    }

    public static function ymdhis($stime = 0)
    {
        $stime = is_string($stime) ? strtotime($stime) : $stime;
        $stime = !empty($stime) && $stime > 0 ? intval($stime) : time();
        return date('Y-m-d H:i:s', $stime);
    }

    public static function ymd($stime = 0)
    {
        $stime = is_string($stime) ? strtotime($stime) : $stime;
        $stime = !empty($stime) && $stime > 0 ? intval($stime) : time();
        return date('Y-m-d', $stime);
    }

    ##########################
    ######## 字符串生成 ########
    ##########################

    public static function dateUTC($dateTimeUTC = null, $dateFormat = 'Y-m-d\TH:i:s\Z', $timeZone = 'UTC')
    {
        $dateTimeUTC = !empty($dateTimeUTC) ? $dateTimeUTC : date("Y-m-d H:i:s");

        $default_timezone = date_default_timezone_get();
        $date = new DateTime($dateTimeUTC, new DateTimeZone($default_timezone));
        $date->setTimeZone(new DateTimeZone($timeZone));

        return $date->format($dateFormat);
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

    /**
     * 在指定时间 上添加N个月的日期字符串
     * @param string $time_str 时间字符串
     * @param int $add_month 需要增加的月数
     * @return string 返回date('Y-m-d H:i:s') 格式的日期字符串
     */
    public static function add_month($time_str, $add_month)
    {
        $arr = date_parse($time_str);
        while ($add_month < 0) {
            $arr['year'] -= 1;
            $add_month += 12;
        }

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

    ##########################
    ######## 字符串处理 ########
    ##########################

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
    public static function str_time($stime, $etime = 0)
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
     * 计算两个时间戳的差值 返回 字符串
     * @param int $c
     * @return string 时间差 xx小时xx分xx秒
     */
    public static function interval2str($c)
    {
        $c = abs(intval($c));
        $s = $c % 60;
        $c = ($c - $s) / 60;
        $m = $c % 60;
        $h = ($c - $m) / 60;
        $rst = '';
        if ($h >= 24) {
            $rst .= intval($h / 24) . "天";
            $h = $h % 24;
        }
        $rst .= $h > 0 ? "{$h}小时" : '';
        $rst .= $m > 0 ? "{$m}分钟" : '';
        $rst .= $s > 0 ? "{$s}秒" : '';
        return $rst;
    }

    public static function short_md5($input, $length = 8)
    {
        $tmp_str = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($tmp_str) - 1;
        $cmp_int = $max * $max * $length * $length;
        $str = '';
        $base_md5 = substr(md5($input), 0, 16);
        $hash_int = abs(self::byteToInt32WithLittleEndian($base_md5));
        while (strlen($str) < $length) {
            $idx = abs(intval($hash_int) % $max);

            $str .= $tmp_str[$idx];   //rand($min,$max)生成介于min和max两个数之间的一个随机整数
            $hash_int = intval($hash_int / $max);
            if ($hash_int < $cmp_int * strlen($str)) {
                $fix_md5 = substr(md5("{$input}_{$str}"), 0, 16);
                $hash_int += abs(self::byteToInt32WithLittleEndian($fix_md5));
            }
        }
        return $str;
    }

    public static function byteToInt32WithLittleEndian($byte)
    {
        $byte0 = isset($byte[0]) ? ord($byte[0]) : 0;
        $byte1 = isset($byte[1]) ? ord($byte[1]) : 0;
        $byte2 = isset($byte[2]) ? ord($byte[2]) : 0;
        $byte3 = isset($byte[3]) ? ord($byte[3]) : 0;
        return $byte3 * 256 * 256 * 256 + $byte2 * 256 * 256 + $byte1 * 256 + $byte0;
    }

    public static function short_hash($input, $length = 8, $type = 'default')
    {
        // 注意  因为 VM整数位数 及 crc32 实现问题  导致不同的环境计算出来的 hash 可能不一致  所以不能在不同环境使用 short_hash
        $d_str = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $d_map = [
            'default' => $d_str,
            'upper' => 'ABCDEFGHIGKLMNOPQRSTUVWXYZ',
            'lower' => 'abcdefghijklmnopqrstuvwxyz',
            'num' => '0123456789',
            'safe' => 'ABCDEFGHJKLMNPQRT3456789abcdefghjkmnpqrt',
            'upper+num' => 'ABCDEFGHIGKLMNOPQRSTUVWXYZ0123456789',
            'lower+num' => 'abcdefghijklmnopqrstuvwxyz0123456789',
        ];
        $tmp_str = self::v($d_map, $type, $d_str);

        $max = strlen($tmp_str) - 1;
        $cmp_int = $max * $max * $length * $length;
        $str = '';
        $hash_int = abs(crc32($input)) + abs(crc32(md5($input)));
        while (strlen($str) < $length) {
            $idx = abs($hash_int % $max);

            $str .= $tmp_str[$idx];   //rand($min,$max)生成介于min和max两个数之间的一个随机整数
            $hash_int = intval($hash_int / $max);
            if ($hash_int < $cmp_int * strlen($str)) {
                $hash_int += abs(crc32("{$input}_{$str}"));
            }
        }
        return $str;
    }

    /**
     * 把一个字符串 每隔几个字母 插入分隔符
     * 常用于 大额数字显示 如  12345678  处理为   12,345,678
     * @param string $str
     * @param int $skip
     * @param string $seq
     * @return string
     */
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

    public static function stri_startwith($str, $needle)
    {
        $len = strlen($needle);
        if ($len == 0) {
            return true;
        }
        $tmp = substr($str, 0, $len);
        return static::stri_cmp($tmp, $needle);

    }

    ##########################
    ######## 过滤相关 ########
    ##########################

    public static function stri_cmp($str1, $str2)
    {
        return static::str_cmp(strtolower($str1), strtolower($str2));
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
    ######## 中文处理 ########
    ##########################

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

    public static function utf8_str_split($str, $l = 0)
    {
        if ($l > 0) {
            $ret = [];
            $len = mb_strlen($str, "UTF-8");
            for ($i = 0; $i < $len; $i += $l) {
                $ret[] = mb_substr($str, $i, $l, "UTF-8");
            }
            return $ret;
        }
        return preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
    }

    public static function xss_clean_textarea($val)
    {
        $val = strval($val);
        $tmp_list = explode("\n", $val);
        $out_list = [];
        foreach ($tmp_list as $item) {
            $tmp = trim(static::xss_clean($item));
            if ($tmp !== '') {
                $out_list[] = $tmp;
            }
        }
        return join("\n", $out_list);
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

    public static function safe_base64_decode($str)
    {
        $str = strtr(trim($str), '-_', '+/');
        $last_len = strlen($str) % 4;
        $str = $last_len == 2 ? $str . '==' : ($last_len == 3 ? $str . '=' : $str);
        $str = base64_decode($str);
        return $str;
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

    public static function safe_base64_encode($str)
    {
        $str = rtrim(strtr(base64_encode($str), '+/', '-_'), '=');
        return $str;
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


    /**
     * 解密函数 使用 配置 CRYPT_KEY 作为 key  成功返回原字符串  失败或过期 返回 空字符串
     * @param string $string 需解密的 字符串 safe_base64_encode 格式编码
     * @param string $key
     * @param string $salt
     * @param int $rnd_length 动态密匙长度 byte $rnd_length>=0，相同的明文会生成不同密文就是依靠动态密匙
     * @param int $chk_length 校验和长度 byte $rnd_length>=4 && $rnd_length><=16
     * @return string 解密结果
     */
    public static function decode2($string, $key, $salt = 'salt', $rnd_length = 2, $chk_length = 4)
    {
        return static::authcode2(strval($string), 'DECODE', $key, 0, $salt, $rnd_length, $chk_length);
    }

    public static function authcode2($_string, $operation, $_key, $_expiry, $salt, $rnd_length, $chk_length)
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
            $result = static::encodeByXor2($string, $crypt);
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

    public static function encodeByXor2($string, $cryptkey)
    {
        $string_length = self::utf8_strlen($string);
        $key_length = strlen($cryptkey);
        $result_list = [];
        $box = [];
        $rndkey = [];
        for ($i = 0; $i <= 255; $i++) {
            $box[$i] = $i;
        }
        // 产生密匙簿
        for ($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }

        $j = 0;
        for ($i = 0; $i < 256; $i++) {
            $j = ($i + $j + $box[$i] + $box[$j] + $rndkey[$i] + $rndkey[$j]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }

        // 核心加解密部分
        $a = 0;
        $j = 0;
        $utf8_idx = 0;
        for ($i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            // 从密匙簿得出密匙进行异或，再转成字符
            $tmp_idx = ($box[$a] + $box[$j]) % 256;
            $ccc = self::ordutf8($string, $utf8_idx);
            $result_list[] = chr($ccc ^ $box[$tmp_idx]);
        }
        return join('', $result_list);
    }

    public static function ordutf8($string, &$offset)
    {
        $bytesnumber = 1;
        $code = ord(substr($string, $offset, 1));
        if ($code >= 128) {        //otherwise 0xxxxxxx
            if ($code < 224) $bytesnumber = 2;                //110xxxxx
            else if ($code < 240) $bytesnumber = 3;        //1110xxxx
            else if ($code < 248) $bytesnumber = 4;    //11110xxx
            $codetemp = $code - 192 - ($bytesnumber > 2 ? 32 : 0) - ($bytesnumber > 3 ? 16 : 0);
            for ($i = 2; $i <= $bytesnumber; $i++) {
                $offset++;
                $code2 = ord(substr($string, $offset, 1)) - 128;        //10xxxxxx
                $codetemp = $codetemp * 64 + $code2;
            }
            $code = $codetemp;
        }
        $offset += 1;
        if ($offset >= strlen($string)) $offset = -1;
        return $code;
    }

    ##########################
    ######## URL相关 ########
    ##########################

    /**
     * 直接从 url 中读取参数 如 url_query('http://baidu.com?a=b', 'a') = 'b'
     * @param string $url
     * @param string $need
     * @return string
     */
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
        return !empty($rst) ? urldecode($rst) : '';
    }

    public static function build_query(array $args = [], $pre = '')
    {
        if (empty($args)) {
            return '';
        }
        $args_list = [];
        foreach ($args as $key => $val) {
            $key = trim($key);
            if (!empty($key)) {
                $args_list[] = "{$key}=" . urlencode($val);
            }
        }
        return !empty($args_list) ? $pre . join($args_list, '&') : '';
    }

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

    public static function str_startwith($str, $needle)
    {
        $len = strlen($needle);
        if ($len == 0) {
            return true;
        }
        $tmp = substr($str, 0, $len);
        return static::str_cmp($tmp, $needle);

    }

    /**
     * 请求url，并返回 json 结果
     * @param string $query_url
     * @param array $header
     * @param string $type
     * @param array $post_fields
     * @param bool $base_auth
     * @param int $timeout
     * @param bool $is_log
     * @return array
     */
    public static function curlRpc($query_url, $header = [], $type = 'GET', $post_fields = [], $base_auth = false, $timeout = 20, $is_log = false)
    {
        $t1 = microtime(true);

        $ch = curl_init();
        $is_https = Util::stri_startwith($query_url, 'https://');
        $port = static::get_port($query_url, $is_https ? 443 : 80);

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
                if (is_array($post_fields)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
                } else {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
                }
            }
        }
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($type));

        if (!empty($header)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }

        if ($is_https) {
            curl_setopt($ch, CURLOPT_SSLVERSION, 1); //设定SSL版本,1-3切换
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //不检查证书
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
            return !is_null($data) ? $data : ['code' => 400, 'msg' => '接口返回非json', 'resp' => $response];
        } else {
            return ['code' => 500, 'msg' => '调用远程接口失败', 'resp' => $response, 'HttpCode' => $response_code];
        }
    }

    #########################################
    ########### 魔术常量相关函数 ############
    #########################################

    /**
     * 尝试 读取 url 中的端口
     * @param string $url
     * @param int $default_post
     * @return int
     */
    public static function get_port($url, $default_post = 80)
    {
        $s_idx = stripos($url, '://');
        if ($s_idx !== false) {
            $url = substr($url, $s_idx + 3);
        }

        $domain = explode('/', $url)[0];
        $p_idx = strrpos($domain, ':');
        if ($p_idx === false) {
            return $default_post;
        }
        return intval(substr($domain, $p_idx + 1));
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
     * 驼峰转下划线
     * @param string $str
     * @param string $req
     * @return string
     */
    public static function humpToLine($str, $req = '_')
    {
        return strtolower(preg_replace('/((?<=[a-z])(?=[A-Z]))/', $req, $str));
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

    ##########################
    ######## 拼接相关 ########
    ##########################

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

    public static function browser_ver($agent)
    {
        $browser = [];
        if (stripos($agent, "MicroMessenger/") > 0) {
            preg_match("/MicroMessenger\/([\d\.]+)+/i", $agent, $ver);
            $browser[0] = "MicroMessenger";
            $browser[1] = !empty($ver[1]) ? $ver[1] : '0.0.0';
        } elseif (stripos($agent, "QQBrowser/") > 0) {
            preg_match("/QQBrowser\/([\d\.]+)+/i", $agent, $ver);
            $browser[0] = "QQBrowser";
            $browser[1] = !empty($ver[1]) ? $ver[1] : '0.0.0';
        } elseif (stripos($agent, "UCBrowser/") > 0) {
            preg_match("/UCBrowser\/([\d\.]+)+/i", $agent, $ver);
            $browser[0] = "UCBrowser";
            $browser[1] = !empty($ver[1]) ? $ver[1] : '0.0.0';
        } elseif (stripos($agent, "Firefox/") > 0) {
            preg_match("/Firefox\/([^;)]+)+/i", $agent, $ver);
            $browser[0] = "Firefox";
            $browser[1] = !empty($ver[1]) ? $ver[1] : '0.0.0';
        } elseif (stripos($agent, "Maxthon") > 0) {
            preg_match("/Maxthon\/([\d\.]+)/", $agent, $ver);
            $browser[0] = "Maxthon";
            $browser[1] = !empty($ver[1]) ? $ver[1] : '0.0.0';
        } elseif (stripos($agent, "MSIE") > 0) {
            preg_match("/MSIE\s+([^;)]+)+/i", $agent, $ver);
            $browser[0] = "IE";
            $browser[1] = !empty($ver[1]) ? $ver[1] : '0.0.0';
        } elseif (stripos($agent, "OPR") > 0) {
            preg_match("/OPR\/([\d\.]+)/", $agent, $ver);
            $browser[0] = "Opera";
            $browser[1] = !empty($ver[1]) ? $ver[1] : '0.0.0';
        } elseif (stripos($agent, "Edge") > 0) {
            //win10 Edge浏览器 添加了chrome内核标记 在判断Chrome之前匹配
            preg_match("/Edge\/([\d\.]+)/", $agent, $ver);
            $browser[0] = "Edge";
            $browser[1] = !empty($ver[1]) ? $ver[1] : '0.0.0';
        } elseif (stripos($agent, "Chrome") > 0) {
            preg_match("/Chrome\/([\d\.]+)/", $agent, $ver);
            $browser[0] = "Chrome";
            $browser[1] = !empty($ver[1]) ? $ver[1] : '0.0.0';
        } elseif (stripos($agent, "Safari/") > 0) {
            preg_match("/Safari\/([\d\.]+)/", $agent, $ver);
            $browser[0] = "Safari";
            $browser[1] = !empty($ver[1]) ? $ver[1] : '0.0.0';
        } elseif (stripos($agent, 'rv:') > 0 && stripos($agent, 'Gecko') > 0) {
            preg_match("/rv:([\d\.]+)/", $agent, $ver);
            $browser[0] = "IE";
            $browser[1] = !empty($ver[1]) ? $ver[1] : '0.0.0';
        } elseif (stripos($agent, "AppleWebKit/") > 0) {
            preg_match("/AppleWebKit\/([\d\.]+)/", $agent, $ver);
            $browser[0] = "AppleWebKit";
            $browser[1] = !empty($ver[1]) ? $ver[1] : '0.0.0';
        } elseif (stripos($agent, "Wget/") !== false) {
            preg_match("/Wget\/([\d\.]+)/", $agent, $ver);
            $browser[0] = "Wget";
            $browser[1] = !empty($ver[1]) ? $ver[1] : '0.0.0';
        } elseif (stripos($agent, "curl/") !== false) {
            preg_match("/curl\/([\d\.]+)/", $agent, $ver);
            $browser[0] = "curl";
            $browser[1] = !empty($ver[1]) ? $ver[1] : '0.0.0';
        } else {
            $browser[0] = "UNKNOWN";
            $browser[1] = "";
        }
        return $browser;
    }

    public static function getMainDoc($doc_str)
    {
        $doc_arr = explode("\n", $doc_str);
        $ret = '';
        foreach ($doc_arr as $doc) {
            $doc = trim($doc);
            if ($doc == '/**' || $doc == '*/' || $doc == '*' || $doc === '') {
                continue;
            }
            $idx = strpos($doc, '*');
            if ($idx !== false) {
                $tmp = trim(substr($doc, $idx + 1));
                if (!empty($tmp)) {
                    $ret = $tmp;
                    break;
                }
            }
            $ret = $doc;
        }
        return $ret;
    }

    public static function getTextDoc($doc_str)
    {
        $doc_arr = explode("\n", $doc_str);
        $ret = [];
        foreach ($doc_arr as $doc) {
            $doc = trim($doc);
            if ($doc == '/**' || $doc == '*/' || $doc == '*' || $doc === '') {
                continue;
            }
            $idx = strpos($doc, '*');
            if ($idx !== false) {
                $tmp = trim(substr($doc, $idx + 1));
                if (self::str_startwith($tmp, '@')) {
                    break;
                }
                if (!empty($tmp)) {
                    $ret[] = $tmp;
                    continue;
                }
            }
            $ret[] = $doc;
        }
        return join("\n", $ret);
    }

}