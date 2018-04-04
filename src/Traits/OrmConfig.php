<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/10 0010
 * Time: 1:44
 */

namespace Tiny\Traits;


use Tiny\Abstracts\AbstractClass;
use Tiny\Event\OrmEvent;

class OrmConfig extends AbstractClass
{

    const ACTION_INSERT = 'INSERT';
    const ACTION_UPDATE = 'UPDATE';
    const ACTION_DELETE = 'DELETE';


    private $_method = '';

    private $_db_name = '';       //数据库名
    private $_table_name = '';     //数据表名
    private $_primary_key = '';   //数据表主键

    private $_max_select = 5000;  //最多获取 5000 条记录 防止数据库拉取条目过多
    private $_cache_time = 0;     //数据缓存时间

    private $_debug = false;   //是否开启 debug

    /**
     * OrmConfig constructor.
     * @param string $db_name 数据库名称
     * @param string $table_name 数据表名称
     * @param string $primary_key 数据表主键  不可为空
     * @param int $cache_time 数据 缓存时间  设置为0 表示 不使用缓存
     * @param int $max_select 最大选取条目数量 影响 select 语句 最大行数
     * @param bool $debug 是否开启 debug
     */
    public function __construct($db_name, $table_name, $primary_key = 'id', $cache_time = 0, $max_select = 5000, $debug = false)
    {
        $this->_db_name = $db_name;
        $this->_table_name = $table_name;
        $this->_primary_key = $primary_key;
        $this->_max_select = $max_select;
        $this->_cache_time = $cache_time;
        $this->_debug = $debug;

        $this->_method = "{$db_name}.{$table_name}";
    }

    public function buildSelectTag($args)
    {
        if (empty($args)) {
            return $this->_method;
        }
        $args_list = [];
        foreach ($args as $key => $val) {
            $key = trim($key);
            $args_list[] = "{$key}=" . urlencode($val);
        }
        return "{$this->_method}?" . join($args_list, '&');
    }

    public function doneSql($sql_str, array $param, $time, $_tag)
    {
        static::fire(new OrmEvent('runSql', $this, $sql_str, $param, $time, $_tag));
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->_table_name;
    }

    /**
     * @return string
     */
    public function getPrimaryKey()
    {
        return $this->_primary_key;
    }

    /**
     * @return string
     */
    public function getDbName()
    {
        return $this->_db_name;
    }

    /**
     * @return int
     */
    public function getMaxSelect()
    {
        return $this->_max_select;
    }

    /**
     * @param int $max_select
     */
    public function setMaxSelect($max_select)
    {
        $this->_max_select = $max_select;
    }

    /**
     * @return int
     */
    public function getCacheTime()
    {
        return $this->_cache_time;
    }

    /**
     * @param int $cache_time
     */
    public function setCacheTime($cache_time)
    {
        $this->_cache_time = $cache_time;
    }

    /**
     * @return bool
     */
    public function getDebug()
    {
        return $this->_debug;
    }

    /**
     * @param bool $debug
     */
    public function setDebug($debug)
    {
        $this->_debug = $debug;
    }


    ###############################################################
    ############## 重写 EventTrait::isAllowedEvent ################
    ###############################################################

    /**
     *  注册回调函数  回调参数为 callback(\Tiny\Event\OrmEvent $event)
     *  1、runSql    执行sql之后触发
     * @param string $type
     * @return bool
     */
    public static function isAllowedEvent($type)
    {
        static $allow_map = [
            'runSql' => 1,
        ];
        return !empty($allow_map[$type]);
    }
}