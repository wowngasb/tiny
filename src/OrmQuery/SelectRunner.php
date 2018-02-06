<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/10 0010
 * Time: 1:27
 */

namespace Tiny\OrmQuery;


use Tiny\Exception\OrmStartUpError;

class SelectRunner
{
    public $method = '';
    public $key = '';
    public $func = null;
    public $filter = null;
    public $timeCache = 0;
    public $tags = [];

    public function __construct(OrmConfig $orm_config, $key, callable $func = null, callable $filter = null, array $tags = [], $timeCache = null)
    {
        $db_name = $orm_config->getDbName();
        $table_name = $orm_config->getTableName();
        $cache_time = $orm_config->getCacheTime();
        $this->method = "{$db_name}.{$table_name}";

        $this->key = $key;
        $this->func = $func;
        $this->filter = !is_null($filter) ? $filter : function ($data) {
            return !empty($data);  //默认只缓存非空的结果
        };
        $this->timeCache = !is_null($timeCache) ? $timeCache : $cache_time;
        $this->tags = $tags;

        if (empty($this->key)) {
            throw new OrmStartUpError("new Select with empty key");
        }
        if (!(!empty($this->func) && is_callable($this->func))) {
            throw new OrmStartUpError("new Select with empty func");  //timeCache 为负数时 可以允许空的 func
        }

        if (!in_array($key, $this->tags)) {
            $this->tags[] = $key;
        }
    }


}