<?php

namespace Tiny\Plugin;

class FisResource
{

    const CSS_LINKS_HOOK = '<!--[FIS_CSS_LINKS_HOOK]-->';
    const JS_SCRIPT_HOOK = '<!--[FIS_JS_SCRIPT_HOOK]-->';
    const FRAMEWORK_HOOK = '<!--[FIS_FRAMEWORK_HOOK]-->';

    private static $arrMap = [];
    private static $arrLoaded = [];
    private static $arrAsyncDeleted = [];

    private static $arrStaticCollection = [];
    //收集require.async组件
    private static $arrRequireAsyncCollection = [];
    private static $arrScriptPool = [];

    public static $framework = null;

    //记录{%script%}, {%style%}的id属性
    public static $cp = null;

    //内嵌styles
    public static $styleArray = [];


    public static $config = [];

    public static function reset()
    {
        self::$arrMap = [];
        self::$arrLoaded = [];
        self::$arrAsyncDeleted = [];
        self::$arrStaticCollection = [];
        self::$arrScriptPool = [];
        self::$framework = null;
    }

    public static function setConfig($config)
    {
        self::$config = $config;
    }

    public static function addStatic($src)
    {
        preg_match('/\.(\w+)(?:\?[\s\S]+)?$/', $src, $m);
        if (!$m) {
            return;
        }
        $typ = $m[1];
        if (!is_array(self::$arrStaticCollection[$typ])) {
            self::$arrStaticCollection[$typ] = [];
        }
        if (!in_array($src, self::$arrStaticCollection[$typ])) {
            self::$arrStaticCollection[$typ][] = $src;
        }
    }

    public static function placeHolder($mode)
    {
        $placeHolder = '';
        switch ($mode) {
            /*case 'mod':
            case 'framework':
                $placeHolder = self::FRAMEWORK_HOOK;
                break;*/
            case 'js':
                $placeHolder = self::FRAMEWORK_HOOK . PHP_EOL . self::JS_SCRIPT_HOOK;
                break;
            case 'css':
                $placeHolder = self::CSS_LINKS_HOOK;
                break;
            default:
                break;
        }
        return $placeHolder;
    }

    //输出模板的最后，替换css hook为css标签集合,替换js hook为js代码
    public static function renderResponse($strContent)
    {
        $cssIntPos = strpos($strContent, self::CSS_LINKS_HOOK);
        if ($cssIntPos !== false) {
            $strContent = substr_replace($strContent, self::render('css'), $cssIntPos, strlen(self::CSS_LINKS_HOOK));
        }
        $frameworkIntPos = strpos($strContent, self::FRAMEWORK_HOOK);
        if ($frameworkIntPos !== false) {
            $strContent = substr_replace($strContent, self::render('framework'), $frameworkIntPos, strlen(self::FRAMEWORK_HOOK));
        }
        $jsIntPos = strpos($strContent, self::JS_SCRIPT_HOOK);
        if ($jsIntPos !== false) {
            $jsContent = ($frameworkIntPos !== false) ? '' : self::getModJsHtml();
            $jsContent .= self::render('js') . self::renderScriptPool();
            $strContent = substr_replace($strContent, $jsContent, $jsIntPos, strlen(self::JS_SCRIPT_HOOK));
        }
        self::reset();
        return $strContent;
    }

    //设置framewok mod.js
    public static function setFramework($strFramework)
    {
        self::$framework = $strFramework;
    }

    //返回静态资源uri，有包的时候，返回包的uri
    public static function getUri($strName)
    {
        $intPos = strpos($strName, ':');
        if ($intPos === false) {
            $strNamespace = '__global__';
        } else {
            $strNamespace = substr($strName, 0, $intPos);
        }
        if (isset(self::$arrMap[$strNamespace]) || self::register($strNamespace)) {
            $arrMap = &self::$arrMap[$strNamespace];
            if (isset($arrMap['res'][$strName])) {
                $arrRes = &$arrMap['res'][$strName];
                if ($arrRes['type'] == 'php') {
                    return self::$config['template_dir'] . $arrRes['uri'];
                } else {
                    if (!array_key_exists('fis_debug', $_GET) && isset($arrRes['pkg'])) {
                        $arrPkg = &$arrMap['pkg'][$arrRes['pkg']];
                        return $arrPkg['uri'];
                    } else {
                        return $arrRes['uri'];
                    }
                }

            }
        }
        return '';
    }

    public static function addStylePool($style)
    {
        self::$styleArray[] = $style;
    }

    private static function getModJsHtml()
    {
        $html = '';
        $resourceMap = self::getResourceMap();
        $loadModJs = (self::$framework && (isset(self::$arrStaticCollection['js']) || $resourceMap));
        //require.resourceMap要在mod.js加载以后执行
        if ($loadModJs) {
            $html .= '<script type="text/javascript" src="' . self::$framework . '"></script>' . PHP_EOL;
        }
        if ($resourceMap) {
            $html .= '<script type="text/javascript">';
            $html .= 'require.resourceMap(' . $resourceMap . ');';
            $html .= '</script>';
        }
        return $html;
    }

    //渲染资源，将收集到的js css，变为html标签，异步js资源变为resorce map。
    public static function render($type)
    {
        $html = '';
        if ($type === 'js') {
            if (isset(self::$arrStaticCollection['js'])) {
                $arrURIs = &self::$arrStaticCollection['js'];
                foreach ($arrURIs as $uri) {
                    if ($uri === self::$framework) {
                        continue;
                    }
                    $html .= '    <script type="text/javascript" src="' . $uri . '"></script>' . PHP_EOL;
                }
            }
        } else if ($type === 'css') {
            if (isset(self::$arrStaticCollection['css'])) {
                $arrURIs = &self::$arrStaticCollection['css'];
                $html = '<link rel="stylesheet" type="text/css" href="' . implode('"/>' . PHP_EOL . '    <link rel="stylesheet" type="text/css" href="', $arrURIs) . '"/>';
            }
            if (self::$styleArray) {
                $html .= PHP_EOL . '    <style type="text/css">';
                $html .= PHP_EOL . implode("\n", self::$styleArray);
                $html .= '</style>';
            }
        } else if ($type === 'framework') {
            $html .= self::getModJsHtml();
        }

        return $html;
    }

    public static function addScriptPool($str, $priority = 0)
    {
        $priority = intval($priority);
        if (!isset(self::$arrScriptPool[$priority])) {
            self::$arrScriptPool[$priority] = [];
        }
        self::$arrScriptPool[$priority][] = $str;
    }

    //输出js，将页面的js源代码集合到pool，一起输出
    public static function renderScriptPool($auto_closure = false)
    {
        $html = '';
        $script_start = '<script type="text/javascript">';
        $script_end = '</script>';
        if (!empty(self::$arrScriptPool)) {
            $priorities = array_keys(self::$arrScriptPool);
            rsort($priorities);
            foreach ($priorities as $priority) {
                if ($auto_closure) {
                    $script = implode("}();\n!function(){", self::$arrScriptPool[$priority]);
                    $html .= <<<EOT
{$script_start}
!function(){
{$script}
}();
{$script_end}
EOT;
                } else {
                    $script = implode("\n", self::$arrScriptPool[$priority]);
                    $html .= <<<EOT
{$script_start}
{$script}
{$script_end}

EOT;
                }
            }
        }
        return $html;
    }

    //获取异步js资源集合，变为json格式的resourcemap
    public static function getResourceMap()
    {
        $ret = '';
        $arrResourceMap = [];
        $needPkg = !array_key_exists('fis_debug', $_GET);
        if (isset(self::$arrRequireAsyncCollection['res'])) {
            foreach (self::$arrRequireAsyncCollection['res'] as $id => $arrRes) {
                $deps = [];
                if (!empty($arrRes['deps'])) {
                    foreach ($arrRes['deps'] as $strName) {
                        if (preg_match('/\.js$/i', $strName)) {
                            $deps[] = $strName;
                        }
                    }
                }

                $arrResourceMap['res'][$id] = [
                    'url' => $arrRes['uri'],
                ];

                if (!empty($arrRes['pkg']) && $needPkg) {
                    $arrResourceMap['res'][$id]['pkg'] = $arrRes['pkg'];
                }

                if (!empty($deps)) {
                    $arrResourceMap['res'][$id]['deps'] = $deps;
                }
            }
        }
        if (isset(self::$arrRequireAsyncCollection['pkg']) && $needPkg) {
            foreach (self::$arrRequireAsyncCollection['pkg'] as $id => $arrRes) {
                $arrResourceMap['pkg'][$id] = [
                    'url' => $arrRes['uri']
                ];
            }
        }
        if (!empty($arrResourceMap)) {
            $ret = str_replace('\\/', '/', json_encode($arrResourceMap));
        }
        return $ret;
    }

    //获取命名空间的map.json
    public static function register($strNamespace)
    {
        if ($strNamespace === '__global__') {
            $strMapName = 'map.json';
        } else {
            $strMapName = $strNamespace . '-map.json';
        }
        $arrConfigDir = self::$config['config_dir'];//配置目录
        $strPath = preg_replace('/[\\/\\\\]+/', '/', $arrConfigDir . '/' . $strMapName);
        if (is_file($strPath)) {
            self::$arrMap[$strNamespace] = json_decode(file_get_contents($strPath), true);
            return true;
        }
        return false;
    }

    /**
     * 分析组件依赖
     * @param array $arrRes 组件信息
     * @param bool $async 是否异步
     */
    private static function loadDeps($arrRes, $async)
    {
        //require.async
        if (isset($arrRes['extras']) && isset($arrRes['extras']['async'])) {
            foreach ($arrRes['extras']['async'] as $uri) {
                self::load($uri, true);
            }
        }
        if (isset($arrRes['deps'])) {
            foreach ($arrRes['deps'] as $strDep) {
                self::load($strDep, $async);
            }
        }
    }

    /**
     * 已经分析到的组件在后续被同步使用时在异步组里删除。
     * @param string $strName
     * @param bool $onlyDeps
     * @return bool
     */
    private static function delAsyncDeps($strName, $onlyDeps = false)
    {
        if (isset(self::$arrAsyncDeleted[$strName])) {
            return true;
        } else {
            self::$arrAsyncDeleted[$strName] = true;

            $arrRes = self::$arrRequireAsyncCollection['res'][$strName];

            //first deps
            if (isset($arrRes['deps'])) {
                foreach ($arrRes['deps'] as $strDep) {
                    if (isset(self::$arrRequireAsyncCollection['res'][$strDep])) {
                        self::delAsyncDeps($strDep);
                    }
                }
            }

            if ($onlyDeps) {
                return true;
            }

            //second self
            if (isset($arrRes['pkg'])) {
                $arrPkg = self::$arrRequireAsyncCollection['pkg'][$arrRes['pkg']];
                $syncJs = isset(self::$arrStaticCollection['js']) ? self::$arrStaticCollection['js'] : [];
                if ($arrPkg && !in_array($arrPkg['uri'], $syncJs)) {
                    //@TODO
                    //unset(self::$arrRequireAsyncCollection['pkg'][$arrRes['pkg']]);
                    foreach ($arrPkg['has'] as $strHas) {
                        if (isset(self::$arrRequireAsyncCollection['res'][$strHas])) {
                            self::$arrLoaded[$strName] = $arrPkg['uri'];
                            self::delAsyncDeps($strHas, true);
                        }
                    }
                    self::$arrStaticCollection['js'][] = $arrPkg['uri'];
                } /* else {
                    //@TODO
                    //unset(self::$arrRequireAsyncCollection['res'][$strName]);
                } */
            } else {
                //已经分析过的并且在其他文件里同步加载的组件，重新收集在同步输出组
                self::$arrStaticCollection['js'][] = $arrRes['uri'];
                self::$arrLoaded[$strName] = $arrRes['uri'];
                //@TODO
                //unset(self::$arrRequireAsyncCollection['res'][$strName]);
            }
        }
        return false;
    }

    /**
     * 加载组件以及组件依赖
     * @param string $strName id
     * @param bool $async 是否为异步组件（only JS）
     * @return mixed
     */
    public static function load($strName, $async = false)
    {
        if (isset(self::$arrLoaded[$strName])) {
            //同步组件优先级比异步组件高
            if (!$async && isset(self::$arrRequireAsyncCollection['res'][$strName])) {
                self::delAsyncDeps($strName);
            }
            return self::$arrLoaded[$strName];
        } else {
            $intPos = strpos($strName, ':');
            if ($intPos === false) {
                $strNamespace = '__global__';
            } else {
                $strNamespace = substr($strName, 0, $intPos);
            }
            if (isset(self::$arrMap[$strNamespace]) || self::register($strNamespace)) {
                $arrMap = &self::$arrMap[$strNamespace];
                $arrPkg = null;
                $arrPkgHas = [];
                if (isset($arrMap['res'][$strName])) {
                    $arrRes = &$arrMap['res'][$strName];
                    if (!array_key_exists('fis_debug', $_GET) && isset($arrRes['pkg'])) {
                        $arrPkg = &$arrMap['pkg'][$arrRes['pkg']];
                        $strURI = $arrPkg['uri'];

                        foreach ($arrPkg['has'] as $strResId) {
                            self::$arrLoaded[$strResId] = $strURI;
                        }

                        foreach ($arrPkg['has'] as $strResId) {
                            $arrHasRes = &$arrMap['res'][$strResId];
                            $arrPkgHas[$strResId] = $arrHasRes;
                            self::loadDeps($arrHasRes, $async);

                        }
                    } else {
                        $strURI = $arrRes['uri'];
                        self::$arrLoaded[$strName] = $strURI;
                        self::loadDeps($arrRes, $async);
                    }

                    if ($async && $arrRes['type'] === 'js') {
                        if ($arrPkg) {
                            self::$arrRequireAsyncCollection['pkg'][$arrRes['pkg']] = $arrPkg;
                            self::$arrRequireAsyncCollection['res'] = array_merge((array)self::$arrRequireAsyncCollection['res'], $arrPkgHas);
                        } else {
                            self::$arrRequireAsyncCollection['res'][$strName] = $arrRes;
                        }
                    } else {
                        self::$arrStaticCollection[$arrRes['type']][] = $strURI;
                    }
                    return $strURI;
                } else {
                    self::triggerError($strName, 'undefined resource "' . $strName . '"', E_USER_NOTICE);
                }
            } else {
                self::triggerError($strName, 'missing map file of "' . $strNamespace . '"', E_USER_NOTICE);
            }
        }
        self::triggerError($strName, 'unknown resource "' . $strName . '" load error', E_USER_NOTICE);
        return '';
    }

    /**
     * 用户代码自定义js组件，其没有对应的文件
     * 只有有后缀的组件找不到时进行报错
     * @param string $strName 组件ID
     * @param string $strMessage 错误信息
     * @param string $errorLevel 错误level
     */
    private static function triggerError($strName, $strMessage, $errorLevel)
    {
        $arrExt = [
            'js',
            'css',
            'tpl',
            'html',
            'xhtml',
        ];
        if (preg_match('/\.(' . implode('|', $arrExt) . ')$/', $strName)) {
            trigger_error(date('Y-m-d H:i:s') . '   ' . $strName . ' ' . $strMessage, $errorLevel);
        }
    }
}

