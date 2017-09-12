<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/10 0010
 * Time: 1:44
 */

namespace Tiny\OrmQuery;


use Tiny\Traits\EventTrait;

class OrmConfig
{
    use EventTrait;

    public $method = '';

    public $table_name = '';     //数据表名
    public $primary_key = '';   //数据表主键
    public $max_select = 0;  //最多获取 5000 条记录 防止数据库拉取条目过多
    public $db_name = '';       //数据库名
    public $cache_time = 0;     //数据缓存时间
    public $debug = false;

    /**
     * OrmConfig constructor.
     * @param string $db_name 数据库名称
     * @param string $table_name 数据表名称
     * @param string $primary_key 数据表主键  不可为空
     * @param int $cache_time 数据 缓存时间  设置为0 表示 不使用缓存
     * @param int $max_select 最大选取条目数量 影响 select 语句 最大行数
     */
    public function __construct($db_name, $table_name, $primary_key = 'id', $cache_time = 0, $max_select = 5000)
    {
        $this->db_name = $db_name;
        $this->table_name = $table_name;
        $this->primary_key = $primary_key;
        $this->max_select = $max_select;
        $this->cache_time = $cache_time;

        $this->method = "{$db_name}.{$table_name}";
    }

    public function buildSelectTag($args)
    {
        if (empty($args)) {
            return $this->method;
        }
        $args_list = [];
        foreach ($args as $key => $val) {
            $key = trim($key);
            $args_list[] = "{$key}=" . urlencode($val);
        }
        return "{$this->method}?" . join($args_list, '&');
    }

    public function doneSql($sql_str, $time, $_tag)
    {
        static::fire('runSql', [$this, $sql_str, $time, $_tag]);
    }

    /**
     *  注册回调函数  回调参数为 callback($this, $sql_str, $time, $_tag)
     *  1、runSql    执行sql之后触发
     * @param string $event
     * @return bool
     */
    public static function isAllowedEvent($event)
    {
        static $allow_event = ['runSql',];
        return in_array($event, $allow_event);
    }

}