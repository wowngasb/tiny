<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/9/25 0025
 * Time: 15:05
 */

namespace Tiny\Plugin\develop\controller;

use http\Exception\RuntimeException;
use Tiny\Anodoc\Collection\TagGroup;
use Tiny\Anodoc\Parser;
use Tiny\HyperDown\Parser as HyperDownParser;
use Sunra\PhpSimple\HtmlDomParser;
use Tiny\Anodoc\Tags\ParamTag;
use Tiny\Anodoc\Tags\Tag;
use Tiny\AnodocBase;
use Tiny\Application;
use Tiny\Exception\Error;
use Tiny\Plugin\ApiHelper;
use Tiny\Plugin\develop\DevelopController;
use Tiny\Util;


/**
 * 项目部署控制器，进行一些脚本
 * Class Deploy
 * @package app\controllers
 */
class deploy extends DevelopController
{

    ################################################################
    ###########################  build helper  ##########################
    ################################################################

    public static function _getAllApiMethodList($path = 'api', array $class_need = [])
    {
        $appname = Application::appname();

        $api_path = Application::path([$appname, $path]);
        $api_list = ApiHelper::getApiFileList($api_path);
        $info = [];

        foreach ($api_list as $key => $val) {
            $class = str_replace('.php', '', $val['name']);
            $skips = [];
            if (!empty($class_need)) {
                if (!empty($class_need[$class]['skip_all'])) {
                    continue;
                }
                $skips = !empty($class_need[$class]['skip']) ? $class_need[$class]['skip'] : [];
            }

            $class_name = "\\{$appname}\\{$path}\\{$class}";
            $method_list = ApiHelper::getApiMethodList($class_name);
            $method_list = array_filter($method_list, function ($v) use ($skips) {
                $name = $v['name'];
                return !Util::stri_startwith($name, 'test') && !in_array($name, $skips);
            });

            if (empty($method_list)) {
                continue;
            }

            $reflection = new \ReflectionClass ($class_name);
//通过反射获取类的注释
            $doc = $reflection->getDocComment();
            $main_doc = Util::getMainDoc($doc);

            $info[$class_name] = array_merge($val, [
                'doc' => $doc,
                'main_doc' => $main_doc,
                'class' => $class,
                'class_name' => $class_name,
                'method_list' => $method_list
            ]);
        }
        return $info;
    }

    ################################################################
    ###########################  Auto build ##########################
    ################################################################

    private static function _trySaveFile($file_name, $file_str)
    {
        if (is_file($file_name)) {
            $tmp = file_get_contents($file_name);
            if (md5(trim($tmp)) == md5(trim($file_str))) {
                return;
            }
        }
        file_put_contents($file_name, $file_str, LOCK_EX);
    }

    private static function _writeDocJs($out_file, array $file_contents_all)
    {
        $doc_str = '';
        foreach ($file_contents_all as $file => $item) {
            $doc_str .= $item . "\n";
        }
        $doc_str_json = json_encode($doc_str);
        $buffer = <<<EOT
var DOC_CONTENT = {$doc_str_json};
EOT;
        self::_trySaveFile($out_file, $buffer);
        return strlen($buffer);
    }

    private static function _writeDocTpl($tpl_file, $out_file, array $assign_args = [])
    {
        ob_start();
        extract($assign_args, EXTR_OVERWRITE);  //释放参数变量
        require($tpl_file);
        $tpl_buffer = ob_get_contents();
        ob_end_clean();
        self::_trySaveFile($out_file, $tpl_buffer);
        return strlen($tpl_buffer);
    }

    private static $_anodoc = null;

    public static function _parseClassDoc($class_name)
    {
        if (empty(self::$_anodoc)) {
            self::$_anodoc = new AnodocBase(new Parser());
            self::$_anodoc->registerTag('param', ParamTag::class);
        }
        return self::$_anodoc->getDoc($class_name);
    }

    private static function _md($str)
    {
        $str = !empty($str) ? strval($str) : "";
        $str = trim($str);
        return str_replace("\n", "\n\n", $str);
    }

    private static function _buildApiThrowsDoc(TagGroup $throws_doc = null)
    {
        $exception_map = self::_loadExceptionMap();
        $throws = !empty($throws_doc) ? $throws_doc->getStore() : [];
        if (empty($throws)) {
            return <<<EOT
### 异常

`无异常`
EOT;
        }
        $md_str = <<<EOT
### 异常

| 错误码   |  类型    | 描述                                           |
| -------- | -------- | --------------------------------------------- |
EOT;
        $codeStrList = [];
        foreach ($throws as $throw) {
            /** @var Tag $throw */
            $type_str = $throw->getValue();
            if (empty($type_str)) {
                continue;
            }
            $ex = !empty($exception_map[$type_str]) ? $exception_map[$type_str] : [];
            $doc_str = !empty($ex['main_doc']) ? $ex['main_doc'] : "";
            $code = !empty($ex['code']) ? $ex['code'] : 500;

            $tmp_str = <<<EOT

|`{$code}` | [`{$type_str}`](#{$type_str})  | {$doc_str} |
EOT;
            $codeStrList[] = [
                'code' => $code,
                'str' => $tmp_str
            ];
        }
        uasort($codeStrList, function ($a, $b) {
            return $a['code'] - $b['code'];
        });
        return $md_str . join('', array_map(function ($i) {
                return $i['str'];
            }, $codeStrList));
    }

    private static function _buildApiReturnDoc(TagGroup $return_doc, $class_self, array $class_types = [], array $defMap = [])
    {
        $returnTag = !empty($return_doc) ? $return_doc->get(0) : null;
        $return_str = !empty($returnTag) ? $returnTag->getValue() : '';
        $idx = stripos($return_str, '{');
        $ret_str = $idx === false ? "" : trim(substr($return_str, $idx));
        if (empty($return_str) || $idx === false || empty($ret_str)) {
            return <<<EOT
{
    "code": 0,
    "msg": "操作成功"
}
EOT;
        }

        $deps = self::_tryGetDefineDeps($ret_str);
        foreach ($deps as $dep => $replaceList) {
            if (!isset($defMap[$dep])) {
                throw new \RuntimeException("unsolved dep {$dep} in return_doc => {$return_str}");
            }
            $val = "{$defMap[$dep]}";
            $ret_str = self::_replaceDefineCode($ret_str, $dep, $replaceList, $val);
        }

        $ret_str = self::_addTabForRet($ret_str, $class_self, $class_types);
        return $ret_str;
    }

    private static function _strpos_min($str, array $seqs, $offset = 0)
    {
        $min_idx = -1;
        $min_seq = null;
        foreach ($seqs as $seq) {
            $idx = strpos($str, $seq, $offset);
            if ($idx === false) {
                $idx = -1;
            }

            if (($idx < $min_idx || $min_idx < 0) && $idx >= 0) {
                $min_idx = $idx;
                $min_seq = $seq;
            }
        }
        return [$min_idx, $min_seq];
    }

    private static function _strpos($str, $seq, $uc = null, $offset = 0)
    {
        $idx = strpos($str, $seq, $offset);
        if ($idx === false) {
            return -1;
        }
        if ($idx >= 1 && !empty($uc)) {
            $uc_l = strlen($uc);
            $seq_l = strlen($seq);
            $c = substr($str, $idx - $uc_l, $uc_l);
            if ($c === $uc) {
                return $idx == strlen($str) - $seq_l ? -1 : self::_strpos($str, $seq, $uc, $idx + $seq_l);
            }
        }
        return $idx;
    }

    public static function _replaceQuoteString($str, $seq = "\"", $uc = "\\", array $strMap = [])
    {
        $strMap = !empty($strMap) ? $strMap : [];
        $seq_l = strlen($seq);
        $idx = self::_strpos($str, $seq, $uc);
        while ($idx >= 0) {
            $_idx = self::_strpos($str, $seq, $uc, $idx + $seq_l);
            if ($_idx < $idx) {
                break;
            }
            $s_id = "@@@" . uniqid();
            $t_l = $_idx - $idx + $seq_l;
            $s_str = substr($str, $idx, $t_l);
            $strMap[$s_id] = $s_str;
            $str = substr_replace($str, $s_id, $idx, $t_l);
            $_idx -= strlen($s_str) - strlen($s_id);
            $idx = self::_strpos($str, $seq, $uc, $_idx + $seq_l);
        }
        return [$str, $strMap];
    }

    public static function _replaceCommentString($str, $seqs = ["//", "#"], array $strMap = [])
    {
        list($idx, $min_seq) = self::_strpos_min($str, $seqs);
        while ($idx >= 0) {
            $seq_l = strlen($min_seq);
            list($_idx,) = self::_strpos_min($str, ["\n", "\r"], $idx + $seq_l);
            if ($_idx < $idx) {
                break;
            }
            $s_id = "@@@" . uniqid();
            $t_l = $_idx - $idx;
            $s_str = substr($str, $idx, $t_l);
            $strMap[$s_id] = trim($s_str);
            $str = substr_replace($str, $s_id, $idx, $t_l);
            $_idx -= strlen($s_str) - strlen($s_id);
            list($idx, $min_seq) = self::_strpos_min($str, $seqs, $_idx);
        }
        return [$str, $strMap];
    }

    public static function _replaceMutiCommentString($str, $s_seq = "/*", $e_seq = "*/", array $strMap = [])
    {
        $strMap = !empty($strMap) ? $strMap : [];
        $s_seq_l = strlen($s_seq);
        $e_seq_l = strlen($e_seq);

        $idx = self::_strpos($str, $s_seq, null);
        while ($idx >= 0) {
            $_idx = self::_strpos($str, $e_seq, null, $idx + $s_seq_l);
            if ($_idx < $idx) {
                break;
            }
            $s_id = "@@@" . uniqid();
            $t_l = $_idx - $idx + $e_seq_l;
            $s_str = substr($str, $idx, $t_l);
            $strMap[$s_id] = $s_str;
            $str = substr_replace($str, $s_id, $idx, $t_l);
            $_idx -= strlen($s_str) - strlen($s_id);
            $idx = self::_strpos($str, $s_seq, null, $_idx + $e_seq_l);
        }
        return [$str, $strMap];
    }

    const MD_AUTO_LINK_TYPES = ['Model', 'ApiModel', 'Exception', 'Message'];

    public static function _addLinkForRet($_comment, $class_self, array $class_types = [])
    {
        $comment = $_comment;
        // [UserBase#Model]  =>  [Model/UserBase](#UserBase)   处理注释 中的自定义超链接
        $rn = preg_match_all("/\[\s*([A-Za-z0-9_]+)\s*#\s*([A-Za-z0-9_]*)\s*\]/", $comment, $match);
        if ($rn) {
            list($strArr, $arr_1, $arr_2) = $match;
            foreach (range(0, $rn) as $idx) {
                $class_type = !empty($arr_2[$idx]) ? $arr_2[$idx] : $class_self;
                if (in_array($class_type, self::MD_AUTO_LINK_TYPES) || in_array($class_type, $class_types)) {
                    $txt_str = !empty($arr_2[$idx]) && $class_type != $class_self ? "{$arr_2[$idx]}/{$arr_1[$idx]}" : "{$arr_1[$idx]}";
                    $comment = str_replace("{$strArr[$idx]}", "<a href='#{$arr_1[$idx]}'>{$txt_str}</a>", $comment);
                }
            }
        }
        return $comment;
    }

    public static function _addTabForRet($_ret_str, $class_self, array $class_types, $seq = "    ")
    {
        $strMap = [];
        list($ret_str, $strMap) = self::_replaceQuoteString($_ret_str, "\"", "\\", $strMap);
        list($ret_str, $strMap) = self::_replaceQuoteString($ret_str, '\'', "\\", $strMap);
        $commentMap = [];
        list($ret_str, $commentMap) = self::_replaceMutiCommentString($ret_str, '/*', "*/", $commentMap);
        list($ret_str, $commentMap) = self::_replaceCommentString($ret_str, ["//", "#"], $commentMap);

        $retArr = explode("\n", $ret_str);
        $_retArr = explode("\n", $_ret_str);
        $acc = 0;
        $ret = [];
        foreach ($retArr as $idx => $line) {
            $_acc = $acc;

            $d_r = substr_count($line, '}');
            $d_l = substr_count($line, '{');
            $z_r = substr_count($line, ']');
            $z_l = substr_count($line, '[');

            $acc += -$d_r;
            $acc += -$z_r;  // TODO 暂不支持多行注释
            if ($d_r == $d_l && $z_r == $z_l) {
                $ret[] = ($_acc > 0 ? str_repeat($seq, $_acc) : '') . trim($_retArr[$idx]);
            } else {
                $ret[] = ($acc > 0 ? str_repeat($seq, $acc) : '') . trim($_retArr[$idx]);
            }
            $acc += $d_l;
            $acc += $z_l;
        }
        $ret_str = join("\n", $ret);
        foreach ($commentMap as $id => $comment) {  // $commentMap 内部优先替换 合并子串
            foreach ($commentMap as $_id => $_comment) {
                $commentMap[$id] = str_replace($_id, $_comment, $commentMap[$id], $count);
                if ($count > 0) {
                    $commentMap[$_id] = '';
                }
            }
        }
        foreach ($commentMap as $_id => $_comment) {
            if (!empty($_comment)) {
                $comment = self::_addLinkForRet($_comment, $class_self, $class_types);
                if ($comment != $_comment) {
                    $ret_str = str_replace($_comment, $comment, $ret_str);
                }
            }
        }
        return $ret_str;
    }

    private static function _buildApiClassMarkdown($path, $class_name, $class, $main_doc, $doc, $method_list, array $class_types = [], array $defMap = [])
    {
        $docInfo = self::_parseClassDoc($class_name);
        $classDoc = $docInfo->getMainDoc();
        $package = !empty($classDoc) ? $classDoc->getTag('package') : null;
        $class_str = !empty($classDoc) ? self::_md($classDoc->getDescription()) : Util::getTextDoc($doc);
        $package_str = !empty($package) ? "`模块`: `{$package->getValue()}`" : "";
        $package_str = str_replace("\\", "/", $package_str);

        $md_str = <<<EOT
# {$main_doc} {$class}

{$package_str}

{$class_str}

EOT;

        foreach ($method_list as $v) {
            $api_params = ApiHelper::getExampleArgsByParameters($v['param']);
            $params_str = !empty($api_params) ? json_encode($api_params) : '{}';
            $methodDoc = $docInfo->getMethodDoc($v['name']);
            $method_str = !empty($methodDoc) ? $methodDoc->getDescription() : $v['main_doc'];
            $package_str = "`路径`: `{$path}/{$class}/{$v['name']}`";
            $param_doc = !empty($methodDoc) ? $methodDoc->getTags('param') : null;
            $return_doc = !empty($methodDoc) ? $methodDoc->getTags('return') : null;
            $throws_doc = !empty($methodDoc) ? $methodDoc->getTags('throws') : null;

            $throws_table_str = self::_buildApiThrowsDoc($throws_doc);
            $params_table_str = self::_buildApiParamsDoc($v['param'], $param_doc);
            $return_str = self::_buildApiReturnDoc($return_doc, $class, $class_types, $defMap);

            $md_str .= <<<EOT

## {$v['main_doc']}（{$v['name']}）

{$package_str}

{$method_str}

```shell
curl -X "POST" "https://{{API_HOST}}/{$path}/{$class}/{$v['name']}" \
     -H "Authorization: devtoken {{API_KEY}} \n Content-type: application/x-www-form-urlencoded; charset=UTF-8" \
     -d '{$params_str}'
```

```python
import requests

result = requests.post('https://{{API_HOST}}/{$path}/{$class}/{$v['name']}',
  headers={"Authorization": "devtoken {{API_KEY}}", "Content-type": "application/x-www-form-urlencoded; charset=UTF-8"},
  data='{$params_str}')

print(result.json())
```

> 返回结果如下:

```json
{$return_str}
```

{$params_table_str}

{$throws_table_str}

EOT;
        }


        return $md_str;
    }


    private static function _buildApiParamsDoc($param_list, TagGroup $param_doc = null)
    {
        if (empty($param_list)) {
            return <<<EOT
### 参数

`无参数`
EOT;
        }

        $md_str = <<<EOT
### 参数

| 字段     |  类型    |  默认值  | 描述                                          |
| -------- | -------- | -------- | --------------------------------------------- |
EOT;
        foreach ($param_list as $param) {
            // {"name":"tag","isArray":false,"isOptional":true,"defaultValue":"avatar"}
            /** @var ParamTag $paramDoc */
            $paramDoc = !empty($param_doc) ? $param_doc->get($param['name']) : null;
            $paramVal = !empty($paramDoc) ? $paramDoc->getValue() : [];
            $type_str = !empty($param['isArray']) ? 'List' : (!empty($paramVal) ? $paramVal['type'] : 'mixed');
            $doc_str = !empty($paramVal['description']) ? trim($paramVal['description']) : "参数 `{$param['name']}`";
            $optional_str = !empty($param['isOptional']) ? '*`可选`*' : '**`必选`**';
            $default_str = !empty($param['isOptional']) ? ($param['defaultValue'] === "" ? '""' : ($param['defaultValue'] === null ? "null" : "{$param['defaultValue']}")) : '-';
            $name_str = !empty($param['isOptional']) ? "*{$param['name']}*" : "**{$param['name']}**";
            $md_str .= <<<EOT

|{$name_str} | `{$type_str}`  | `{$default_str}`  | {$optional_str} {$doc_str} |
EOT;
        }
        return $md_str;
    }

    private static $_exception_map = [];

    private static function _loadExceptionMap($path = 'Exception')
    {
        $appname = Application::appname();
        if (empty(self::$_exception_map)) {
            $exArr = Util::getfiles(Application::path([$appname, $path]));
            foreach ($exArr as $afile) {
                $class = str_replace('.php', '', $afile);

                $class_name = "\\{$appname}\\{$path}\\{$class}";
                $reflection = new \ReflectionClass ($class_name);
//通过反射获取类的注释
                if ($reflection->isAbstract()) {
                    continue;
                }

                $doc = $reflection->getDocComment();
                $main_doc = Util::getMainDoc($doc);
                /** @var Error $obj */
                try {
                    $obj = new $class_name("错误信息", null);
                } catch (\Exception $ex) {
                    $obj = null;
                }
                self::$_exception_map[$class] = [
                    'class' => $class,
                    'class_name' => $class_name,
                    'doc' => $doc,
                    'main_doc' => $main_doc,
                    'code' => !empty($obj) ? $obj->getErrno() : 500,
                ];
            }
            uasort(self::$_exception_map, function ($a, $b) {
                return $a['code'] - $b['code'];
            });
        }
        return self::$_exception_map;
    }

    public static function _allDocPageContents($path = 'api', array $class_need = [], array $preDocs = [], array $lastDocs = [], array $defMap = [])
    {
        $info = [];
        $info = array_merge($info, $preDocs);

        $apiList = self::_getAllApiMethodList($path, $class_need);
        $out_path = Application::path(['public', 'doc', $path]);
        if (!is_dir($out_path)) {
            mkdir($out_path, 0777, true);
        }
        $class_types = [];
        foreach ($apiList as $item) {
            $class_types[] = $item['class'];
        }

        foreach ($apiList as $class_name => $item) {
            $file_str = self::_buildApiClassMarkdown($path, $class_name, $item['class'], $item['main_doc'], $item['doc'], $item['method_list'], $class_types, $defMap);
            // [UserBase#Model]  =>  [Model/UserBase](#UserBase)   处理文档中的自定义超链接
            $rn = preg_match_all("/\[\s*([A-Za-z0-9_]+)\s*#\s*([A-Za-z0-9_]*)\s*\]/", $file_str, $match);
            if ($rn) {
                list($strArr, $arr_1, $arr_2) = $match;
                foreach (range(0, $rn) as $idx) {
                    $class_type = !empty($arr_2[$idx]) ? $arr_2[$idx] : $item['class'];
                    if (in_array($class_type, self::MD_AUTO_LINK_TYPES) || in_array($class_type, $class_types)) {
                        $txt_str = !empty($arr_2[$idx]) && $class_type != $item['class'] ? "{$arr_2[$idx]}/{$arr_1[$idx]}" : "{$arr_1[$idx]}";
                        $file_str = str_replace("{$strArr[$idx]}", "[{$txt_str}](#{$arr_1[$idx]})", $file_str);
                    }
                }
            }
            $out_file = Application::path(['public', 'doc', $path, "{$item['main_doc']}({$item['class']}).md"], false);
            $out_file = iconv('UTF-8', 'GBK', $out_file);
            self::_trySaveFile($out_file, $file_str);
            $info[$item['class']] = $file_str;
        }

        $file_str = self::_buildModelDoc();
        $info['Model'] = $file_str;
        $out_file = Application::path(['public', 'doc', $path, "模型(Model).md"], false);
        $out_file = iconv('UTF-8', 'GBK', $out_file);
        self::_trySaveFile($out_file, $file_str);

        $file_str = self::_buildExceptionDoc();
        $info['Exception'] = $file_str;
        $out_file = Application::path(['public', 'doc', $path, "异常(Exception).md"], false);
        $out_file = iconv('UTF-8', 'GBK', $out_file);
        self::_trySaveFile($out_file, $file_str);

        $info = array_merge($info, $lastDocs);
        return $info;
    }

    private static function _buildJavaModelDemo($class_name, $model)
    {
        if (empty($class_name)) {
            return '';
        }
        $md_str = <<<EOT
public class {$class_name} {

EOT;

        foreach ($model['columns'] as $column) {
            $md_str .= <<<EOT

    public {$column['type']} {$column['name']};   // {$column['doc']}
EOT;
        }
        $md_str .= <<<EOT


}
EOT;
        return $md_str;
    }

    private static function _buildModelColumnsDoc($columns)
    {
        if (empty($columns)) {
            return '';
        }

        $md_str = <<<EOT
### 异常

| 名称   |  类型  |  默认值 |  索引 |  标记 | 描述                                           |
| ------ | ------ |------ |------ |------ | --------------------------------------------- |
EOT;
        foreach ($columns as $column) {
            $type_str = !empty($column['nullable']) ? "{$column['type']}" : $column['type'];
            $index_str = !empty($column['index']) ? "index" : '-';
            $index_str = !empty($column['unique']) ? "unique" : $index_str;
            $index_str = !empty($column['primary_key']) ? "primary" : $index_str;

            $default_val = !empty($column['server_default']) ? "{$column['server_default']}" : '-';
            $default_val = ($default_val == 'CURRENT_TIMESTAMP' || $default_val == 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP') ? 'auto' : $default_val;
            $arr = [];
            if (!empty($column['info']['HiddenField'])) {
                $arr[] = 'H';
            }
            if (!empty($column['info']['InitializeField'])) {
                $arr[] = 'I';
            }
            if (!empty($column['info']['EditableField'])) {
                $arr[] = 'E';
            }
            if (!empty($column['info']['SortableField'])) {
                $arr[] = 'S';
            }
            $mask_str = join("", $arr);
            $md_str .= <<<EOT

|`{$column['name']}` | `{$type_str}`| `{$default_val}`  | `{$index_str}`| `{$mask_str}`| {$column['doc']} |
EOT;
        }
        return $md_str;
    }

    private static function _buildModelDoc()
    {
        $md_str = <<<EOT

# 数据模型 Model

数据库对应数据表设计 导出基本类型

EOT;

        $modelFile = Application::path(['database', 'generation', 'model.json'], false);
        $modelMap = is_file($modelFile) ? json_decode(file_get_contents($modelFile), true) : [];
        if (empty($modelMap)) {
            return $md_str;
        }
        uasort($modelMap, function ($a, $b) {
            return $a['table_name'] == $b['table_name'] ? 0 : ($a['table_name'] > $b['table_name'] ? 1 : -1);
        });
        foreach ($modelMap as $table_name => $item) {
            $demo_str = self::_buildJavaModelDemo("{$item['class_name']}Model", $item);
            $columns_table_str = self::_buildModelColumnsDoc($item['columns']);

            $md_str .= <<<EOT

## {$item['class_doc']}({$item['class_name']})

{$item['class_doc']}

> Class 如下:

```java
{$demo_str}
```

{$columns_table_str}

EOT;
        }
        return $md_str;
    }

    private static function _buildExceptionDoc()
    {
        $md_str = <<<EOT

# 异常 Exception

触发错误时 接口抛出的异常会自动转为 `json` 

API 响应的 `code` 为异常类对应的错误码，`msg` 为抛出异常时附带的消息

EOT;
        $exception_map = self::_loadExceptionMap();

        if (empty($exception_map)) {
            return $md_str;
        }

        $md_str .= <<<EOT

常见异常列表

| 错误码   |  类型    | 描述                                           |
| -------- | -------- | --------------------------------------------- |
EOT;
        foreach ($exception_map as $type_str => $ex) {
            $doc_str = !empty($ex['main_doc']) ? $ex['main_doc'] : "";
            $code_str = !empty($ex['code']) ? $ex['code'] : 500;

            $md_str .= <<<EOT

|`{$code_str}` | [`{$type_str}`](#{$type_str})  | {$doc_str} |
EOT;
        }

        foreach ($exception_map as $class => $item) {
            $docInfo = self::_parseClassDoc($item['class_name']);
            $classDoc = $docInfo->getMainDoc();
            $package = !empty($classDoc) ? $classDoc->getTag('package') : null;
            $class_str = !empty($classDoc) ? self::_md($classDoc->getDescription()) : Util::getTextDoc($item['doc']);
            $package_str = !empty($package) ? "`路径`: `{$package->getValue()}`" : "";
            $package_str = str_replace("\\", "/", $package_str);

            $code_str = "`错误码`: `{$item['code']}`";
            $md_str .= <<<EOT

## {$item['main_doc']}({$class})

{$package_str}

{$code_str}

{$class_str}

EOT;
        }
        return $md_str;
    }


    ################################################################
    ########################### static build  ##########################
    ################################################################

    public static function _buildDefineDoc($pre, $file_content, array $defMap = [], array $class_types = [])
    {
        $file_content = trim($file_content);
        $file_content = str_replace("\r\n", "\n", $file_content);

        if (empty($file_content)) {
            return ['', $defMap];
        }

        $class_types[] = $pre;
        $class_types = Util::build_map_set($class_types);

        $parser = new HyperDownParser();
        $html = $parser->makeHtml($file_content);

        $dom = HtmlDomParser::str_get_html($html, true, true, 'UTF-8', false);
        $l = $dom->find('h2+pre');
        $h = $dom->find('h2');
        $nodes = $dom->nodes;
        $strMap = [];
        foreach ($l as $p) {
            $h2 = self::_tryFindPre($nodes, $h, $p);
            $key = !empty($h2) ? $h2->text() : '';
            $key = trim($key);
            $key = str_replace(" ", "", $key);
            $key = str_replace("（", "(", $key);
            $key = str_replace("）", ")", $key);
            $codes = $p->firstChild()->nodes;
            $code = $codes[0];
            $text = $code->text();

            $test = preg_match('/\S+\((\w+)\)/i', $key, $matches);
            if (!empty($key) && !empty($text) && $test) {
                $key = trim($matches[1]);
                $_key = "{$pre}.{$key}";
                $strMap[$_key] = $text;
            }
        }

        list($defMap, $repKeys) = self::_buildDefineMap($strMap, $defMap);
        foreach ($strMap as $key => $str) {
            $str = str_replace("&quot;", "\"", $str);
            $rep = in_array($key, $repKeys) ? $defMap[$key] : $str;
            $rep = self::_addTabForRet($rep, $pre, $class_types);
            $file_content = str_replace($str, $rep, $file_content);
        }

        return [$file_content, $defMap];
    }

    public static function _buildDefineMap($strMap, array $defMap = [])
    {
        $repKeys = [];
        $defDep = [];

        if (empty($strMap)) {
            return [$defMap, $repKeys];
        }

        foreach ($strMap as $key => $code) {
            $deps = self::_tryGetDefineDeps($code);
            if (!empty($deps)) {
                $repKeys[] = $key;
            }

            $_deps = [];
            foreach ($deps as $dep => $replaceList) {
                if (isset($defMap[$dep])) {  //  发现依赖 尝试处理
                    $val = "{$defMap[$dep]}";
                    $code = self::_replaceDefineCode($code, $dep, $replaceList, $val);
                } else {
                    $_deps[$dep] = $replaceList;
                }
            }

            if (empty($_deps)) {  // 无依赖 直接完成
                $defMap[$key] = $code;
            } else {
                $defDep[$key] = [
                    'done' => false,
                    'deps' => $_deps,
                    'code' => $code,
                ];
            }
        }

        foreach ($defMap as $k => $c) {
            $defDep[$k] = [
                'done' => true,
                'deps' => [],
                'code' => $c,
            ];
        }

        while (!self::_checkDefineDone($defDep)) {
            list($defDep, $defMap) = self::_tryResolerDefineDeps($defDep, $defMap);
        }

        return [$defMap, $repKeys];
    }

    private static function _replaceDefineCode($content, $dep, $replaceList, $val)
    {
        $depArr = explode('.', $dep, 2);
        $valArr = explode("\n", $val, 2);
        $_val = "{$valArr[0]}   /* By [{$depArr[1]}#{$depArr[0]}] */  \n{$valArr[1]}";
        foreach ($replaceList as $replace) {
            $content = str_replace($replace, $_val, $content);
        }
        return $content;
    }

    private static function _checkDefineDone($defDep)
    {
        if (empty($defDep)) {
            return true;
        }
        foreach ($defDep as $key => $item) {
            if (empty($item['done'])) {
                return false;
            }
        }
        return true;
    }

    private static function _tryResolerDefineDeps($defDep, $defMap)
    {
        foreach ($defDep as $key => $item) {
            if (!empty($item['done'])) {
                continue;
            }

            $deps = array_merge([], $item['deps']);
            foreach ($item['deps'] as $k => $r) {  // 针对k 做处理
                if (empty($defDep[$k])) {
                    throw new \RuntimeException("unsolved dep {$k} in block {$key}");
                }
                $sub = $defDep[$k];
                foreach ($sub['deps'] as $_k => $_r) {  // 合并 子级 和 上级 的依赖
                    $deps[$_k] = !empty($deps[$_k]) ? Util::build_map_set($deps[$_k] + $_r) : $_r;
                }

                if (!empty($sub['done'])) {
                    $val = "{$sub['code']}";
                    $defDep[$key]['code'] = self::_replaceDefineCode($defDep[$key]['code'], $k, $deps[$k], $val);
                    unset($deps[$k]);
                }
            }
            $defDep[$key]['deps'] = $deps;
            if (empty($defDep[$key]['deps'])) {
                $defDep[$key]['done'] = true;
                $defMap[$key] = $defDep[$key]['code'];
            }
            if (isset($defDep[$key]['deps'][$key])) {
                throw new \RuntimeException("cyclic dependency in block {$key} with " . join(' -> ', array_keys($defDep[$key]['deps'])));
            }
        }
        return [$defDep, $defMap];
    }

    private static function _tryGetDefineDeps($code)
    {
        $lines = explode("\n", $code);
        $tmp = [];
        foreach ($lines as $line) {
            $line = trim($line);
            $test = preg_match('/{{\s*(\w+)\s*#\s*(\w+)\s*}}/i', $line, $matches);
            if (!empty($line) && $test) {
                $str = trim($matches[0]);
                $key = trim($matches[2]) . "." . trim($matches[1]);
                $tmp[$key] = !empty($tmp[$key]) ? ($tmp[$key] + [$str]) : [$str];
            }
        }
        $replaceMap = [];
        foreach ($tmp as $key => $item) {
            $replaceMap[$key] = Util::build_map_set($item);
        }
        return $replaceMap;
    }

    private static function _tryFindPre($nodes, $h, $code)
    {
        if (empty($h) || empty($code) || empty($nodes)) {
            return null;
        }
        $ret = null;
        foreach ($nodes as $node) {
            foreach ($h as $h2) {
                if ($h2 === $node) {
                    $ret = $h2;
                    break;
                }
            }
            if ($node === $code) {
                break;
            }
        }
        return $ret;
    }

    public static function _buildApiDoc($path = 'api', array $class_need = [], array $preDocs = [], array $lastDocs = [], array $defMap = [])
    {
        $out_file = Application::path(['public', 'doc', 'doc.js'], false);
        $file_contents_all = self::_allDocPageContents($path, $class_need, $preDocs, $lastDocs, $defMap);

        $out_file_l = self::_writeDocJs($out_file, $file_contents_all);

        $index_tpl = Application::path(['public', 'doc', 'index.tpl'], false);
        $index_file = Application::path(['public', 'doc', 'index.html'], false);
        $index_tpl_l = self::_writeDocTpl($index_tpl, $index_file);
        return [
            'index_tpl_l' => $index_tpl_l,
            'out_file_l' => $out_file_l
        ];
    }

    public static function _buildApiModJs($dev_debug = 0, $path = 'api')
    {
        $appname = Application::appname();
        $ret = [];

        $api_path = Application::path([$appname, $path]);
        $api_list = ApiHelper::getApiFileList($api_path);
        foreach ($api_list as $key => $val) {
            $class = str_replace('.php', '', $val['name']);
            $out_file = $class . '.js';
            $class_name = "\\{$appname}\\{$path}\\{$class}";
            $method_list = ApiHelper::getApiMethodList($class_name);
            $js_str = ApiHelper::model2js($class, $method_list, $dev_debug);
            $out_path = Application::path(['public', 'static', $path]);
            if (!is_dir($out_path)) {
                mkdir($out_path, 0777, true);
            }
            file_put_contents($out_path . $out_file, $js_str, LOCK_EX);
            $js_len = strlen($js_str);
            $ret[] = "build:{$out_file} ({$js_len})";
        }
        return $ret;
    }

    public static function _buildApiModJsForYc($dev_debug = 0, $path = 'api')
    {
        $appname = Application::appname();
        $ret = [];

        $api_path = Application::path([$appname, $path]);
        $api_list = ApiHelper::getApiFileList($api_path);
        foreach ($api_list as $key => $val) {
            $class = str_replace('.php', '', $val['name']);
            $out_file = $class . '.js';
            $class_name = "\\{$appname}\\{$path}\\{$class}";
            $method_list = ApiHelper::getApiMethodList($class_name);
            $js_str = ApiHelper::model2js($class, $method_list, $dev_debug);
            $out_path = Application::path(['public', 'static', 'yc-' . $path]);
            if (!is_dir($out_path)) {
                mkdir($out_path, 0777, true);
            }
            $js_str = <<<EOT
typeof ycmod === 'object' && (function (define, require, ycmod) {

{$js_str}

})(ycmod.define, ycmod.require, ycmod);
EOT;

            file_put_contents($out_path . $out_file, $js_str, LOCK_EX);
            $js_len = strlen($js_str);
            $ret[] = "build:{$out_file} ({$js_len})";
        }
        return $ret;
    }

    ################################################################
    ########################### beforeAction  ##########################
    ################################################################

    public function beforeAction(array $params)
    {
        $params = parent::beforeAction($params);

        if (!self::authDevelopKey($this->getRequest())) {  //认证 不通过
            Application::redirect($this->getResponse(), Application::url($this->getRequest(), ['', 'index', 'index']));
        }

        return $params;
    }

    public function phpInfo()
    {
        phpinfo();
    }

    public function initSuperAdmin()
    {
        $admin_id = 0;  // TODO 判断当前是否存在管理员
        if ($admin_id > 0) {
            return $this->getResponse()->appendBody('pass');
        }
        $username = $this->_post('username', '');
        $password = $this->_post('password', '');
        if (empty($password) || empty($username)) {
            $html_str = <<<EOT
<form action="" method="POST">
    登陆账号：<input type="text" value="" placeholder="登录名" name="username">
    登录密码：<input type="password" placeholder="密码" name="password">
    <button type="submit">创建超级管理员</button>
</form>
EOT;
            return $this->getResponse()->appendBody($html_str);
        }
        $admin_id = 0;  // TODO 创建管理员帐号
        if ($admin_id > 0) {
            $html_str = "init {$username} at {$admin_id} ok";
            return $this->getResponse()->appendBody($html_str);
        }
        return $this->getResponse()->appendBody('create admin error');
    }

    public function syncEnvConfig()
    {
        $html_str = 'Sync Config Surceased';  // TODO 获取基本配置
        $this->getResponse()->appendBody($html_str);
    }


    /**
     * 编译根目录api下所有 API 类 生成 js  放到 static/apiMod 下面
     */
    public function buildApiModJs()
    {
        $dev_debug = $this->_get('dev_debug', 0) == 1;
        $js_list = self::_buildApiModJs($dev_debug);
        $html_str = join($js_list, '<br />');
        $this->getResponse()->appendBody($html_str);
    }

    public function actionGetModelJs()
    {
        $dev_debug = $this->_get('dev_debug', Application::dev());
        $dev_debug = !empty($dev_debug);
        $method_list = [];
        $cls = $this->_get('cls', '');
        if (!empty($cls)) {
            $class_name = '\\api\\' . $cls;
            $method_list = ApiHelper::getApiMethodList($class_name);
        }
        $html_str = ApiHelper::model2js($cls, $method_list, $dev_debug);
        $this->getResponse()->appendBody($html_str);
    }

    public function cleanCache()
    {
        $mCache = self::_getCacheInstance();
        if (!empty($mCache)) {
            $mCache->clear();
        }
        $html_str = "clear cache";
        $this->getResponse()->appendBody($html_str);
    }

}
