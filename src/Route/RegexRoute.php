<?php
/**
 * Created by PhpStorm.
 * User: a
 * Date: 2017/8/14
 * Time: 10:49
 */

namespace Tiny\Route;


use Tiny\Exception\AppStartUpError;
use Tiny\Interfaces\RequestInterface;
use Tiny\Interfaces\RouteInterface;
use Tiny\Util;


/**
 * Class RouteRegex
 * 到目前为止,我们之前的路由协议都很好的完成了基本的路由操作,我们常用的也是他们,然而它们会有一些限制,这就是我们为什么要引进正则路由(Yaf_Route_Regex)的原因. 正则路由给予我们preg正则的全部力量,但同时也使得我们的路由协议变得更加复杂了一些.即使是他们有点复杂,我还是希望你能好好掌握它,因为它比其他路由协议要灵活一点点. 一开始,我们先对之前的产品案例改用使用正则路由：
 *  $route = new RouteRegex('product/([a-zA-Z-_0-9]+)', ['module', 'products', 'view'], [1 => 'ident']);   //完成数字到字符变量的映射
 *  这样,我们就简单的将变量1映射到了ident 变量名,这样就设置了ident 变量,同时你也可以在控制器里面获取到它的值.
 *  $params = $_REQUEST;
 * @package Tiny
 */
class RegexRoute implements RouteInterface
{

    private $_regex = '';
    private $_routeInfo = [];
    private $_reg_map = [];
    private $_default_route_info = ['index', 'index', 'index'];
    private $_buildUrl = null;

    public function __construct($regex, array $routeInfo, array $reg_map, array $default_route_info = [], callable $buildUrl = null)
    {
        $this->_buildUrl = $buildUrl;
        $regex = trim($regex);
        $regex = Util::str_startwith($regex, "/") ? substr($regex, 1) : $regex;

        $this->_regex = $regex;
        $this->_routeInfo = $routeInfo;
        $this->_reg_map = $reg_map;
        $this->_default_route_info = Util::mergeNotEmpty($this->_default_route_info, $default_route_info);
    }


    /**
     * 根据请求的 $_method $_request_uri $_language 得出 路由信息 及 参数
     * 匹配成功后 获得 路由信息 及 参数
     * @param RequestInterface $request 请求对象
     * @return array 匹配成功 [ [$module, $controller, $action], $params ]  失败 [null, null]
     */
    public function route(RequestInterface $request)
    {
        $uri = $request->fixRequestPath();
        $reg_str = Util::str_startwith($this->_regex, "^\/") ? $this->_regex : "^\/{$this->_regex}";
        $matches = [];
        preg_match("/{$reg_str}/i", $uri, $matches);

        if (!empty($matches)) {
            $routeInfo = $this->_routeInfo;
            $params = $request->all_request();
            foreach ($this->_reg_map as $idx => $key) {
                $params[$key] = $matches[$idx];
            }
            return [$routeInfo, $params];
        } else {
            return [null, null];
        }
    }

    /**
     * 获取路由 默认参数 用于url参数不齐全时 补全
     * @return array  $routeInfo [$module, $controller, $action]
     */
    public function defaultRoute()
    {
        return $this->_default_route_info;  // 默认 $routeInfo
    }

    /**
     * 根据 路由信息 及 参数 生成反路由 得到 url
     * @param string $schema uri 协议
     * @param string $host domain
     * @param array $routeInfo 路由信息数组  [$module, $controller, $action]
     * @param array $params 参数数组
     * @return string
     * @throws AppStartUpError
     */
    public function buildUrl($schema, $host, array $routeInfo, array $params = [])
    {
        if (is_null($this->_buildUrl)) {
            throw new AppStartUpError("no callable buildUrl for " . __CLASS__);
        }
        return call_user_func_array($this->_buildUrl, [$schema, $host, $routeInfo, $params]);
    }

}