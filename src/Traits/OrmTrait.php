<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/14 0014
 * Time: 18:20
 */

namespace Tiny\Traits;

use Tiny\Exception\OrmStartUpError;
use Tiny\OrmQuery\AbstractQuery;
use Tiny\OrmQuery\SelectRunner;
use Tiny\Plugin\DbHelper;
use Tiny\Util;

/**
 * Class BaseOrmModel
 * array $where  检索条件数组 格式为 dict 每个元素都表示一个检索条件  条件之间为 and 关系
 * ① [  `filed` => `value`, ]
 *    key不为数值，value不是数组   表示 某个字段为某值的 where = 检索
 *    例如 ['votes' => 100, ]  对应 ->where('votes', '=', 100)
 * ② [  `filed` => [``, ``], ]
 *    key不为数值的元素 表示 某个字段为某值的 whereIn 检索
 *    例如 ['id' => [1, 2, 3], ] 对应  ->whereIn('id', [1, 2, 3])
 * ③ [ [``, ``], ]
 *    key为数值的元素 表示 使用某种检索
 * 例如 [   ['whereBetween', 'votes', [1, 100]],  ]   对应  ->whereBetween('votes', [1, 100])
 * 例如 [   ['whereIn', 'id', [1, 2, 3]],  ]   对应  ->whereIn('id', [1, 2, 3])
 * 例如 [   ['whereNull', 'updated_at'],  ]   对应  ->whereNull('updated_at')
 * ④ [  `filed` => AbstractQuery, ]
 *    key不为数值的元素 value是 AbstractQuery 表示 某个字段为 AbstractQuery 定义的检索
 * 例如 [   'votes' => whereBetween([1, 100])  ]   对应  ->whereBetween('votes', [1, 100])
 * 例如 [   'id' => whereIn([1, 2, 3])  ]   对应  ->whereIn('id', [1, 2, 3])
 * 例如 [   'updated_at' => whereNull() ],  ]   对应  ->whereNull('updated_at')
 * 注意：
 * $_redis_prefix_db 下级数据缓存 建议只用数据表级缓存
 * 同一分类下的缓存数据必须来源于同一张表  不可缓存连表数据 防止无法分析依赖
 * 缓存的key 需要自己生成有意义的字符串 及 匹配清除缓存的 匹配字符串
 * @package Tiny\Traits
 */
trait OrmTrait
{
    use CacheTrait, MapInstanceTraits;

    private static $_db_map = [];
    protected static $_redis_prefix_db = 'DbCache';

    ####################################
    ############ 获取配置 ##############
    ####################################

    /**
     * 修复 更新 或 创建 数组  选出可填充的 key
     * @param array $data
     * @return array
     */
    protected static function _fixFillAbleData(array $data)
    {
        $ret = [];
        foreach ($data as $key => $item) {
            $_key = Util::trimlower($key);
            if (static::_fillAble($_key)) {
                $ret[$key] = is_array($item) ? json_encode($item) : $item;
            }
        }
        return $ret;
    }

    private static function _fixFiledKey($filed)
    {
        $idx = strpos($filed, '#');
        $filed = $idx > 0 ? substr($filed, 0, $idx) : $filed;
        return trim($filed);
    }

    public static function getBuilder()
    {
        $table_name = static::tableName();
        $table = static::_getDb()->table($table_name);
        return $table;
    }

    /**
     * 直接获取 ORM table 不推荐直接使用该接口  推荐使用模块预设方法
     * @param array $where 检索条件数组 具体格式参见文档
     * @param array $select
     * @param array $orderBy
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     * @throws OrmStartUpError
     */
    public static function tableBuilder(array $where = [], array $select = [], array $orderBy = [])
    {
        $table = static::getBuilder();
        if (empty($where)) {
            return $table;
        }
        $query_list = [];
        foreach ($where as $_filed => $item) {
            // $filed 支持 `filed#123` 注释方式来为同一个filed 添加多个and条件
            // TODO 处理 orWhere 逻辑暂时未处理

            if (is_integer($_filed)) {
                if (is_array($item)) {
                    /*
                     * ③ [ [``, ``], ]
                     *    key为数值的元素 表示 使用某种检索
                     * 例如 [   ['whereBetween', 'votes', [1, 100]],  ]   对应  ->whereBetween('votes', [1, 100])
                     * 例如 [   ['whereIn', 'id', [1, 2, 3]],  ]   对应  ->whereIn('id', [1, 2, 3])
                     * 例如 [   ['whereNull', 'updated_at'],  ]   对应  ->whereNull('updated_at')
                     * */
                    $func = $item[0];
                    $query = array_slice($item, 1);
                    $query_list[] = [true, $func, $query];
                    continue;
                }
                throw new OrmStartUpError("ORM build where error list item:" . strval($item));
            }

            $filed = self::_fixFiledKey($_filed);
            if (empty($filed)) {
                throw new OrmStartUpError("ORM build where error filed {$filed}({$_filed})");
            }

            if ($item instanceof AbstractQuery) {
                /*
                 * ④ [  `filed` => AbstractQuery, ]
                 *    key不为数值的元素 value是 AbstractQuery 表示 某个字段为 AbstractQuery 定义的检索
                 * 例如 [   'votes' => whereBetween([1, 100])  ]   对应  ->whereBetween('votes', [1, 100])
                 * 例如 [   'id' => whereIn([1, 2, 3])  ]   对应  ->whereIn('id', [1, 2, 3])
                 * 例如 [   'updated_at' => whereNull() ],  ]   对应  ->whereNull('updated_at')
                 * */
                $query_list[] = $item->buildQuery($filed);  //list($enable, $action, $query)
                continue;
            }

            if (is_array($item)) {
                /*
                 * ② [  `filed` => [``, ``], ]
                 *    key不为数值的元素 表示 某个字段为某值的 whereIn 检索
                 *    例如 ['id' => [1, 2, 3], ] 对应  ->whereIn('id', [1, 2, 3])
                 * */
                $query = [$filed, $item];
                $query_list[] = [true, 'whereIn', $query];
                continue;
            }

            if (is_string($item) || is_float($item) || is_integer($item) || is_bool($item)) {
                /*
                 * ① [  `filed` => `value`, ]
                 *    key不为数值，value不是数组   表示 某个字段为某值的 where = 检索
                 *    例如 ['votes' => 100, ]  对应 ->where('votes', '=', 100)
                 * */
                $query = [$filed, '=', $item];
                $query_list[] = [true, 'where', $query];
                continue;
            }
            throw new OrmStartUpError("ORM build where error dict {$_filed}=>" . print_r($item, true));
        }

        foreach ($query_list as $query_item) {
            if (empty($query_item)) {
                continue;
            }
            list($enable, $func, $query) = $query_item;
            //只有 $enable 为 true 的情况下 条件才会生效
            if (!$enable) {
                continue;
            }
            //依次调用设置的查询条件
            call_user_func_array([$table, $func], $query);
        }
        if (!empty($select)) {
            $table->select($select);
        }
        if (!empty($orderBy) && !empty($orderBy[0])) {
            $column = $orderBy[0];
            $direction = !empty($orderBy[1]) && Util::trimlower($orderBy[1]) == 'desc' ? 'desc' : 'asc';
            $table->orderBy($column, $direction);
        }

        return $table;
    }

    /**
     * 使用这个特性的子类必须 实现这个方法 返回特定格式的数组 表示数据表的配置
     * @return OrmConfig
     * @throws OrmStartUpError
     */
    protected static function getOrmConfig()
    {
        throw new OrmStartUpError("cannot call OrmTrait::getOrmConfig() must overwrite it");
    }

    public static function maxSelect()
    {
        return static::getOrmConfig()->getMaxSelect();
    }

    public static function sqlDebug()
    {
        return static::getOrmConfig()->getDebug();
    }

    public static function cacheTime()
    {
        return static::getOrmConfig()->getCacheTime();
    }

    public static function tableName()
    {
        return static::getOrmConfig()->getTableName();
    }

    public static function dbName()
    {
        return static::getOrmConfig()->getDbName();
    }

    public static function primaryKey()
    {
        return static::getOrmConfig()->getPrimaryKey();
    }

    ####################################
    ############ 可重写方法 #############
    ####################################

    protected static function getCachePreFix()
    {
        $prefix = static::$_redis_prefix_db;
        if (empty($prefix)) {
            $prefix = static::$_cache_prefix_key;
        }
        return trim($prefix);
    }

    public static function incOneById($id, $filed, $value = 1)
    {
        if (empty($id) || empty($filed)) {
            return;
        }
        static::incItem($id, $filed, $value);
        static::getOneById($id, -1);
    }

    public static function decOneById($id, $filed, $value = 1)
    {
        if (empty($id) || empty($filed)) {
            return;
        }
        static::decItem($id, $filed, $value);
        static::getOneById($id, -1);
    }

    public static function incAndGetOne($id, $filed, $value = 1)
    {
        if (empty($id) || empty($filed)) {
            return null;
        }
        static::incItem($id, $filed, $value);
        return static::getOneById($id, 0);
    }

    public static function decAndGetOne($id, $filed, $value = 1)
    {
        if (empty($id) || empty($filed)) {
            return null;
        }
        static::decItem($id, $filed, $value);
        return static::getOneById($id, 0);
    }

    /**
     * 根据主键获取数据 自动使用缓存
     * @param $id
     * @param null $timeCache
     * @return mixed|null
     */
    public static function getOneById($id, $timeCache = null)
    {
        if (empty($id)) {
            return null;
        }

        $cache_time = static::cacheTime();
        $db_name = static::dbName();
        $table_name = static::tableName();
        $primary_key = static::primaryKey();
        if (empty($cache_time)) {
            if (empty($timeCache)) {
                return static::getItem($id);
            } elseif ($timeCache < 0) {
                return null;
            }
        }

        $timeCache = is_null($timeCache) ? $cache_time : intval($timeCache);

        $tag = "{$primary_key}={$id}";
        $table = "{$db_name}.{$table_name}";

        $data = static::_cacheDataManager($table, $tag, function () use ($id) {
            $tmp = static::getItem($id);
            return $tmp;
        }, function ($data) {
            return !empty($data) ? true : 3;
        }, $timeCache, static::getCachePreFix(), [], self::sqlDebug());

        return $data;
    }

    public static function delOneById($id)
    {
        $ret = static::delItem($id);
        static::getOneById($id, -1);
        return $ret;
    }

    /**
     * 根据主键 获取单个条目的 某个字段   自动使用缓存
     * @param int $id 条目 主键 id
     * @param string $key 需要的字段  键名
     * @param string $default 无对应条目时的默认值
     * @param null $timeCache
     * @return string
     */
    public static function valueOneById($id, $key, $default = '', $timeCache = null)
    {
        $tmp = static::getOneById($id, $timeCache);
        if (empty($tmp)) {
            return $default;
        }
        return $tmp[$key];
    }

    /**
     * 根据主键获取多个数据 自动使用缓存
     * @param array $id_list
     * @param null $timeCache
     * @return array
     */
    public static function getManyById(array $id_list, $timeCache = null)
    {
        $ret_dict = static::getDictById($id_list, $timeCache);
        $ret_list = [];
        foreach ($id_list as $id) {
            if (!empty($ret_dict[$id])) {
                $ret_list[] = $ret_dict[$id];
            } else {
                $ret_list[] = null;
            }
        }
        return $ret_list;
    }

    /**
     * 根据主键获取多个数据 自动使用缓存
     * @param array $id_list
     * @param null $timeCache
     * @return array
     */
    public static function getDictById(array $id_list, $timeCache = null)
    {
        if (empty($id_list)) {
            return [];
        }
        if (count($id_list) == 1) {
            return [$id_list[0] => static::getOneById($id_list[0], $timeCache)];
        }
        $cache_time = static::cacheTime();
        $primary_key = static::primaryKey();
        $db_name = static::dbName();
        $table_name = static::tableName();

        $tag_list = [];
        foreach ($id_list as $id) {
            $tag_list["{$id}"] = "{$primary_key}={$id}";
        }
        $id_set = array_keys($tag_list);

        $table = "{$db_name}.{$table_name}";
        $timeCache = is_null($timeCache) ? $cache_time : intval($timeCache);

        $no_cache_list = [];
        $cache_dict = [];
        $db_dict = [];
        if ($timeCache > 0) {
            $cache_dict = self::_mgetDataManager($table, $tag_list, $timeCache, static::getCachePreFix(), self::sqlDebug());
            foreach ($cache_dict as $cid => $item) {
                if (empty($item)) {
                    $no_cache_list[] = $cid;
                }
            }
        } else {
            $no_cache_list = $id_set;
        }

        if (!empty($no_cache_list)) {
            $db_dict = static::dictItem([$primary_key => $no_cache_list]);
            if ($timeCache > 0) {
                foreach ($db_dict as $cid => $item) {
                    self::_cacheDataManager($table, $tag_list[$cid], function () use ($item) {
                        return $item;
                    }, function ($data) {
                        return !empty($data);
                    }, $timeCache, static::getCachePreFix(), [], self::sqlDebug());
                }
            }
        }
        $ret_dict = [];
        foreach ($id_list as $id) {
            if (!empty(!empty($db_dict[$id]))) {
                $ret_dict[$id] = $db_dict[$id];
            } elseif (!empty($cache_dict[$id])) {
                $ret_dict[$id] = $cache_dict[$id];
            } else {
                $ret_dict[$id] = null;
            }
        }
        return $ret_dict;
    }

    /**
     * 根据主键更新数据 自动更新缓存
     * @param $id
     * @param array $data
     * @return int
     */
    public static function setOneById($id, array $data)
    {
        if (empty($id)) {
            return null;
        }
        $update = 0;
        if (!empty($data)) {
            $update = static::setItem($id, $data);
        }
        static::getOneById($id, -1);
        return $update;
    }

    public static function setAndGetOne($id, array $data)
    {
        if (empty($id)) {
            return null;
        }
        if (!empty($data)) {
            static::setItem($id, $data);
        }
        return static::getOneById($id, 0);
    }

    /**
     * 创建数据 自动更新缓存
     * @param $data
     * @return int
     */
    public static function createOne(array $data)
    {
        $id = static::newItem($data);
        !empty($id) && static::getOneById($id, -1);
        return $id;
    }

    /**
     * 更新或插入数据  优先根据条件查询数据 无法查询到数据时插入数据  自动更新缓存
     * @param array $where 检索条件数组 具体格式参见文档
     * @param array $value 需要插入的数据  格式为 [`filed` => `value`, ]
     * @return mixed|null 返回数据
     */
    public static function upsertOne(array $where, array $value)
    {
        $id = static::upsertItem($where, $value);
        return !empty($id) ? static::getOneById($id, -1) : null;
    }

    /**
     * 更新或插入数据  优先根据条件查询数据 无法查询到数据时插入数据  自动更新缓存
     * @param array $where 检索条件数组 具体格式参见文档
     * @param array $value 需要插入的数据  格式为 [`filed` => `value`, ]
     * @return mixed|null 返回数据
     */
    public static function upsertAndGetOne(array $where, array $value)
    {
        $id = static::upsertItem($where, $value);
        return !empty($id) ? static::getOneById($id, 0) : null;
    }

    /**
     * 添加新数据 自动更新缓存
     * @param array $data
     * @return mixed|null
     */
    public static function createAndGetOne(array $data)
    {
        if (!empty($data)) {
            $id = static::newItem($data);
            return !empty($id) ? static::getOneById($id, 0) : null;
        } else {
            return null;
        }
    }

    /**
     * @param $val
     * @return mixed
     */
    protected static function _fixItem($val)
    {
        return $val;
    }

    /**
     * 判断 新增或修改的数据  键名 是否允许填充  默认全部允许
     * @param $key
     * @return bool
     */
    protected static function _fillAble($key)
    {
        false && func_get_args();
        return true;
    }

    ####################################
    ############ 辅助函数 ##############
    ####################################

    public static function raw($value)
    {
        return call_user_func_array([static::_getDb(), 'raw'], [$value]);
    }

    /**
     * 运行查询 并给出缓存的key 缓存结果  默认只缓存非空结果
     * @param SelectRunner $select
     * @param string $prefix
     * @param bool $is_log
     * @return array
     * @throws OrmStartUpError
     */
    protected static function runSelect(SelectRunner $select, $prefix = null, $is_log = false)
    {
        if (empty($select->key)) {
            throw new OrmStartUpError("runQuery with empty key");
        }
        if (empty($select->func) && $select->timeCache >= 0) {
            throw new OrmStartUpError("runQuery with empty func but timeCache gte 0");  //timeCache 为负数时 可以允许空的 func
        }

        $prefix = !is_null($prefix) ? $prefix : static::getCachePreFix();

        return static::_cacheDataManager($select->method, $select->key, $select->func, $select->filter, $select->timeCache, $is_log, $prefix, $select->tags);
    }

    ####################################
    ############ 辅助函数 ##############
    ####################################

    /**
     * 根据主键获取某个字段的值
     * @param int $id
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public static function getFiledById($id, $name, $default = null)
    {
        $tmp = static::getOneById($id);
        return isset($tmp[$name]) ? $tmp[$name] : $default;
    }

    /**
     * @return \Illuminate\Database\Connection
     * @throws OrmStartUpError
     */
    private static function _getDb()
    {
        $db_name = static::dbName();
        if (!empty(self::$_db_map[$db_name])) {
            return self::$_db_map[$db_name];
        }

        $table_name = static::tableName();
        $primary_key = static::primaryKey();
        $max_select = static::maxSelect();
        if (empty($table_name) || empty($primary_key) || empty($max_select) || empty($db_name)) {
            throw new OrmStartUpError('Orm:' . __CLASS__ . 'with error config');
        }
        self::$_db_map[$db_name] = DbHelper::initDb()->getConnection($db_name);
        return self::$_db_map[$db_name];
    }

    protected static function recordRunSql($time, $sql, $param, $tag = 'sql')
    {
        $db_name = static::dbName();
        $table_name = static::tableName();

        $sql_str = Util::prepare_query($sql, $param);
        $_tag = str_replace(__TRAIT__, "{$db_name}.{$table_name}", $tag);
        static::getOrmConfig()->doneSql($sql_str, $param, $time, $_tag);
    }

    ####################################
    ########### 原 build 函数 ############
    ####################################

    /**
     * Get a single column's value from the first result of a query.
     *
     * @param \Illuminate\Database\Query\Builder $table
     * @param  string $column
     * @return mixed
     */
    public static function _value($table, $column)
    {
        $start_time = microtime(true);
        $result = $table->value($column);
        static::sqlDebug() && static::recordRunSql(microtime(true) - $start_time, $table->toSql(), $table->getBindings(), __METHOD__);
        return $result;
    }

    /**
     * Execute the query and get the first result.
     *
     * @param \Illuminate\Database\Query\Builder $table
     * @param  array $columns
     * @param  array $sort_option
     * @return mixed|static
     */
    public static function _first($table, $columns = ['*'], array $sort_option = [])
    {
        $start_time = microtime(true);
        if (!empty($sort_option[0])) {
            $field = trim($sort_option[0]);
            $direction = !empty($sort_option[1]) ? Util::trimlower($sort_option[1]) : 'asc';
            $direction = $direction == 'desc' ? 'desc' : 'asc';
            $table->orderBy($field, $direction);
        }
        $result = $table->first($columns);
        static::sqlDebug() && static::recordRunSql(microtime(true) - $start_time, $table->toSql(), $table->getBindings(), __METHOD__);
        return $result;
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param \Illuminate\Database\Query\Builder $table
     * @param  array $columns
     * @return array|static[]
     */
    public static function _get($table, $columns = ['*'])
    {
        $start_time = microtime(true);
        $result = $table->get($columns);
        static::sqlDebug() && static::recordRunSql(microtime(true) - $start_time, $table->toSql(), $table->getBindings(), __METHOD__);

        $rst = [];
        foreach ($result as $key => $val) {
            $rst[] = $val;
        }
        return $rst;
    }


    /**
     * Chunk the results of the query.
     *
     * @param \Illuminate\Database\Query\Builder $table
     * @param  int $count
     * @param  callable $callback
     * @return bool
     */
    public static function _chunk($table, $count, callable $callback)
    {
        $start_time = microtime(true);
        $result = $table->chunk($count, $callback);
        static::sqlDebug() && static::recordRunSql(microtime(true) - $start_time, $table->toSql(), $table->getBindings(), __METHOD__);
        return $result;
    }

    /**
     * Chunk the results of a query by comparing numeric IDs.
     *
     * @param \Illuminate\Database\Query\Builder $table
     * @param  int $count
     * @param  callable $callback
     * @param  string $column
     * @param  string $alias
     * @return bool
     */
    public static function _chunkById($table, $count, callable $callback, $column = 'id', $alias = null)
    {
        $start_time = microtime(true);
        $result = $table->chunkById($count, $callback, $column, $alias);
        static::sqlDebug() && static::recordRunSql(microtime(true) - $start_time, $table->toSql(), $table->getBindings(), __METHOD__);
        return $result;
    }

    /**
     * Execute a callback over each item while chunking.
     *
     * @param \Illuminate\Database\Query\Builder $table
     * @param  callable $callback
     * @param  int $count
     * @return bool
     */
    public static function _each($table, callable $callback, $count = 1000)
    {
        $start_time = microtime(true);
        $result = $table->each($callback, $count);
        static::sqlDebug() && static::recordRunSql(microtime(true) - $start_time, $table->toSql(), $table->getBindings(), __METHOD__);
        return $result;
    }

    /**
     * Get an array with the values of a given column.
     *
     * @param \Illuminate\Database\Query\Builder $table
     * @param  string $column
     * @param  string|null $key
     * @return array
     */
    public static function _pluck($table, $column, $key = null)
    {
        $start_time = microtime(true);
        $result = $table->pluck($column, $key);
        static::sqlDebug() && static::recordRunSql(microtime(true) - $start_time, $table->toSql(), $table->getBindings(), __METHOD__);
        $rst = [];
        foreach ($result as $k => $val) {
            $rst[] = $val;
        }
        return $rst;
    }

    /**
     * Concatenate values of a given column as a string.
     *
     * @param \Illuminate\Database\Query\Builder $table
     * @param  string $column
     * @param  string $glue
     * @return string
     */
    public static function _implode($table, $column, $glue = '')
    {
        $start_time = microtime(true);
        $result = $table->implode($column, $glue);
        static::sqlDebug() && static::recordRunSql(microtime(true) - $start_time, $table->toSql(), $table->getBindings(), __METHOD__);
        return $result;
    }

    /**
     * Determine if any rows exist for the current query.
     *
     * @param \Illuminate\Database\Query\Builder $table
     * @return bool
     */
    public static function _exists($table)
    {
        $start_time = microtime(true);
        $result = $table->exists();
        static::sqlDebug() && static::recordRunSql(microtime(true) - $start_time, $table->toSql(), $table->getBindings(), __METHOD__);
        return $result;
    }

    /**
     * Retrieve the "count" result of the query.
     *
     * @param \Illuminate\Database\Query\Builder $table
     * @param  string $columns
     * @return int
     */
    public static function _count($table, $columns = '*')
    {
        $start_time = microtime(true);
        $result = $table->count($columns);
        static::sqlDebug() && static::recordRunSql(microtime(true) - $start_time, $table->toSql(), $table->getBindings(), __METHOD__);
        return $result;
    }

    /**
     * Retrieve the minimum value of a given column.
     *
     * @param \Illuminate\Database\Query\Builder $table
     * @param  string $column
     * @return mixed
     */
    public static function _min($table, $column)
    {
        $start_time = microtime(true);
        $result = $table->min($column);
        static::sqlDebug() && static::recordRunSql(microtime(true) - $start_time, $table->toSql(), $table->getBindings(), __METHOD__);
        return $result;
    }

    /**
     * Retrieve the maximum value of a given column.
     *
     * @param \Illuminate\Database\Query\Builder $table
     * @param  string $column
     * @return mixed
     */
    public static function _max($table, $column)
    {
        $start_time = microtime(true);
        $result = $table->max($column);
        static::sqlDebug() && static::recordRunSql(microtime(true) - $start_time, $table->toSql(), $table->getBindings(), __METHOD__);
        return $result;
    }

    /**
     * Retrieve the sum of the values of a given column.
     *
     * @param \Illuminate\Database\Query\Builder $table
     * @param  string $column
     * @return mixed
     */
    public static function _sum($table, $column)
    {
        $start_time = microtime(true);
        $result = $table->sum($column);
        static::sqlDebug() && static::recordRunSql(microtime(true) - $start_time, $table->toSql(), $table->getBindings(), __METHOD__);
        return $result;
    }

    /**
     * Retrieve the average of the values of a given column.
     *
     * @param \Illuminate\Database\Query\Builder $table
     * @param  string $column
     * @return mixed
     */
    public static function _avg($table, $column)
    {
        $start_time = microtime(true);
        $result = $table->avg($column);
        static::sqlDebug() && static::recordRunSql(microtime(true) - $start_time, $table->toSql(), $table->getBindings(), __METHOD__);
        return $result;
    }

    /**
     * Alias for the "avg" method.
     *
     * @param \Illuminate\Database\Query\Builder $table
     * @param  string $column
     * @return mixed
     */
    public static function _average($table, $column)
    {
        $start_time = microtime(true);
        $result = $table->average($column);
        static::sqlDebug() && static::recordRunSql(microtime(true) - $start_time, $table->toSql(), $table->getBindings(), __METHOD__);
        return $result;
    }

    /**
     * Execute an aggregate function on the database.
     *
     * @param \Illuminate\Database\Query\Builder $table
     * @param  string $function
     * @param  array $columns
     * @return mixed
     */
    public static function _aggregate($table, $function, $columns = ['*'])
    {
        $start_time = microtime(true);
        $result = $table->aggregate($function, $columns);
        static::sqlDebug() && static::recordRunSql(microtime(true) - $start_time, $table->toSql(), $table->getBindings(), __METHOD__);
        return $result;
    }

    /**
     * Execute a numeric aggregate function on the database.
     *
     * @param \Illuminate\Database\Query\Builder $table
     * @param  string $function
     * @param  array $columns
     * @return float|int
     */
    public static function _numericAggregate($table, $function, $columns = ['*'])
    {
        $start_time = microtime(true);
        $result = $table->numericAggregate($function, $columns);
        static::sqlDebug() && static::recordRunSql(microtime(true) - $start_time, $table->toSql(), $table->getBindings(), __METHOD__);
        return $result;
    }

    /**
     * Update a record in the database.
     *
     * @param \Illuminate\Database\Query\Builder $table
     * @param  array $data
     * @return int
     */
    public static function _update($table, array $data)
    {
        $data = self::_fixFillAbleData($data);
        $start_time = microtime(true);
        $result = $table->update($data);
        static::sqlDebug() && static::recordRunSql(microtime(true) - $start_time, $table->toSql(), $table->getBindings(), __METHOD__);
        return $result;
    }

    /**
     * Insert or update a record matching the attributes, and fill it with values.
     *
     * @param \Illuminate\Database\Query\Builder $table
     * @param  array $attributes
     * @param  array $data
     * @return bool
     */
    public static function _updateOrInsert($table, array $attributes, array $data = [])
    {
        $data = self::_fixFillAbleData($data);
        $start_time = microtime(true);
        $result = $table->updateOrInsert($attributes, $data);
        static::sqlDebug() && static::recordRunSql(microtime(true) - $start_time, $table->toSql(), $table->getBindings(), __METHOD__);
        return $result;
    }

    /**
     * Increment a column's value by a given amount.
     *
     * @param \Illuminate\Database\Query\Builder $table
     * @param  string $column
     * @param  int $amount
     * @param  array $extra
     * @return int
     */
    public static function _increment($table, $column, $amount = 1, array $extra = [])
    {
        $data = self::_fixFillAbleData($extra);
        $start_time = microtime(true);
        $result = $table->increment($column, $amount, $data);
        static::sqlDebug() && static::recordRunSql(microtime(true) - $start_time, $table->toSql(), $table->getBindings(), __METHOD__);
        return $result;
    }

    /**
     * Decrement a column's value by a given amount.
     *
     * @param \Illuminate\Database\Query\Builder $table
     * @param  string $column
     * @param  int $amount
     * @param  array $extra
     * @return int
     */
    public static function _decrement($table, $column, $amount = 1, array $extra = [])
    {
        $data = self::_fixFillAbleData($extra);
        $start_time = microtime(true);
        $result = $table->decrement($column, $amount, $data);
        static::sqlDebug() && static::recordRunSql(microtime(true) - $start_time, $table->toSql(), $table->getBindings(), __METHOD__);
        return $result;
    }

    /**
     * Delete a record from the database.
     *
     * @param \Illuminate\Database\Query\Builder $table
     * @return int
     * @internal param mixed $id
     */
    public static function _delete($table)
    {
        $start_time = microtime(true);
        $result = $table->delete();
        static::sqlDebug() && static::recordRunSql(microtime(true) - $start_time, $table->toSql(), $table->getBindings(), __METHOD__);
        return $result;
    }

    /**
     * Paginate the given query.
     *
     * @param \Illuminate\Database\Query\Builder $table
     * @param  int $perPage
     * @param  array $columns
     * @param  string $pageName
     * @param  int|null $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     *
     * @throws \InvalidArgumentException
     */
    public static function _paginate($table, $perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $start_time = microtime(true);
        $result = $table->paginate($perPage, $columns, $pageName, $page);
        static::sqlDebug() && static::recordRunSql(microtime(true) - $start_time, $table->toSql(), $table->getBindings(), __METHOD__);
        return $result;
    }

    ####################################
    ########### 条目操作函数 ############
    ####################################

    /**
     * 查询数据总量
     * @param array $where 检索条件数组 具体格式参见文档
     * @param array $columns 需要获取的列 格式为[`column_1`, ]  默认为所有
     * @return int  数据条目数
     */
    public static function countItem(array $where = [], array $columns = ['*'])
    {
        $start_time = microtime(true);
        $table = static::tableBuilder($where);
        $count = $table->count($columns);
        static::sqlDebug() && static::recordRunSql(microtime(true) - $start_time, $table->toSql(), $table->getBindings(), __METHOD__);
        return $count;
    }

    /**
     * 分页查询数据  不允许超过最大数量限制
     * @param int $start 起始位置 skip
     * @param int $limit 数量限制 take 上限为 $this->_max_select_item_counts
     * @param array $sort_option 排序依据 格式为 ['column', 'asc|desc']
     * @param array $where 检索条件数组 具体格式参见文档
     * @param array $columns 需要获取的列 格式为[`column_1`, ]  默认为所有
     * @param array $columns 需要获取的列 格式为[`column_1`, ]  默认为所有
     * @param array | string $with
     * @return array 数据 list 格式为 [`item`, ]
     */
    public static function selectItem($start = 0, $limit = 0, array $sort_option = [], array $where = [], array $columns = ['*'], $with = '')
    {
        $start_time = microtime(true);
        $max_select = static::maxSelect();
        $table = static::tableBuilder($where);
        if (!empty($with)) {
            $table->with($with);
        }
        $start = $start <= 0 ? 0 : $start;
        $limit = $limit > $max_select ? $max_select : $limit;
        if ($start > 0) {
            $table->skip($start);
        }
        if ($limit > 0) {
            $table->take($limit);
        } else {
            $table->take($max_select);
        }
        if (!empty($sort_option[0])) {
            $field = trim($sort_option[0]);
            $direction = !empty($sort_option[1]) ? Util::trimlower($sort_option[1]) : 'asc';
            $direction = $direction == 'desc' ? 'desc' : 'asc';
            $table->orderBy($field, $direction);
        }
        $data = $table->get($columns);
        static::sqlDebug() && static::recordRunSql(microtime(true) - $start_time, $table->toSql(), $table->getBindings(), __METHOD__);

        $rst = [];
        foreach ($data as $key => $val) {
            $rst[$key] = static::_fixItem($val);
        }
        return $rst;
    }

    /**
     * 获取以主键为key的dict   不允许超过最大数量限制
     * @param array $where 检索条件数组 具体格式参见文档
     * @param array $columns 需要获取的列 格式为[`column_1`, ]  默认为所有
     * @return array 数据 dict 格式为 [`item.primary_key` => `item`, ]
     */
    public static function dictItem(array $where = [], array $columns = ['*'])
    {
        $start_time = microtime(true);
        $max_select = static::maxSelect();
        $primary_key = static::primaryKey();
        $table = static::tableBuilder($where);
        $table->take($max_select);
        $data = $table->get($columns);
        static::sqlDebug() && static::recordRunSql(microtime(true) - $start_time, $table->toSql(), $table->getBindings(), __METHOD__);

        $rst = [];
        foreach ($data as $key => $val) {
            $id = $val[$primary_key];
            $rst[$id] = static::_fixItem($val);
        }
        return $rst;
    }

    /**
     * 根据查询条件 获取第一条记录
     * @param array $where 检索条件数组 具体格式参见文档
     * @param array $sort_option 排序依据 格式为 ['column', 'asc|desc']
     * @param array $columns 需要获取的列 格式为[`column_1`, ]  默认为所有
     * @param string | array $with
     * @param string | array $orderBy
     * @return mixed
     */
    public static function firstItem(array $where = [], array $sort_option = [], array $columns = ['*'], $with = '', $orderBy = '')
    {
        $start_time = microtime(true);
        $table = static::tableBuilder($where);
        if (!empty($sort_option[0])) {
            $field = trim($sort_option[0]);
            $direction = !empty($sort_option[1]) ? Util::trimlower($sort_option[1]) : 'asc';
            $direction = $direction == 'desc' ? 'desc' : 'asc';
            $table->orderBy($field, $direction);
        }
        if (!empty($with)) {
            $table->with($with);
        }
        if (!empty($orderBy)) {
            $table->groupBy($orderBy);
        }
        $item = $table->first($columns);
        static::sqlDebug() && static::recordRunSql(microtime(true) - $start_time, $table->toSql(), $table->getBindings(), __METHOD__);
        return static::_fixItem($item);
    }

    /**
     * 更新或插入数据  优先根据条件查询数据 无法查询到数据时插入数据
     * @param array $where 检索条件数组 具体格式参见文档
     * @param array $value 需要插入的数据  格式为 [`filed` => `value`, ]
     * @return int 返回数据 主键 自增id
     */
    public static function upsertItem(array $where, array $value)
    {
        $data = self::_fixFillAbleData($value);
        $primary_key = static::primaryKey();
        $tmp = static::firstItem($where);
        if (empty($tmp)) {
            return static::newItem($data);
        } else {
            $id = $tmp[$primary_key];
            static::setItem($id, $data);
            return $id;
        }
    }

    ####################################
    ########### 单条记录操作 ############
    ####################################

    /**
     * 根据主键 获取单个条目的 某个字段
     * @param int $id 条目 主键 id
     * @param string $key 需要的字段  键名
     * @param string $default 无对应条目时的默认值
     * @return mixed
     */
    public static function valueItem($id, $key, $default = '')
    {
        $tmp = static::getItem($id);
        if (empty($tmp)) {
            return $default;
        }
        return $tmp[$key];
    }

    /**
     * 检查 某条记录是否存在 存在 返回条目 id 不存在 返回 0
     * @param mixed $value 需匹配的字段的值
     * @param string $filed 字段名 默认为 null 表示使用主键
     * @return int
     */
    public static function checkItem($value, $filed = null)
    {
        $primary_key = static::primaryKey();
        if (is_array($value)) {
            $where = $value;
        } else {
            $filed = $filed ?: $primary_key;
            $where = [strtolower($filed) => $value];
        }
        $tmp = static::firstItem($where, [], [$primary_key]);
        if (!empty($tmp) && !empty($tmp[$primary_key])) {
            return $tmp[$primary_key];
        } else {
            return 0;
        }
    }

    /**
     * 根据某个字段的值 获取第一条记录
     * @param mixed $value 需匹配的字段的值
     * @param string $filed 字段名 默认为 null 表示使用主键
     * @param array $columns 需要获取的列 格式为[`column_1`, ]  默认为所有
     * @param string | array $with
     * @return mixed
     */
    public static function getItem($value, $filed = null, array $columns = ['*'], $with = '')
    {
        $primary_key = static::primaryKey();
        $filed = $filed ?: $primary_key;
        return static::firstItem([strtolower($filed) => $value], [], $columns, $with);
    }

    /**
     * 插入数据 返回插入的自增id
     * @param array $data 数据[`filed` => `value`, ]
     * @return int
     */
    public static function newItem(array $data)
    {
        $data = self::_fixFillAbleData($data);
        $start_time = microtime(true);
        $primary_key = self::primaryKey();

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = json_encode($value);
            }
        }
        $table = static::tableBuilder();
        $id = $table->insertGetId($data, $primary_key);
        static::sqlDebug() && static::recordRunSql(microtime(true) - $start_time, $table->toSql(), $table->getBindings(), __METHOD__);
        return $id;
    }

    /**
     * 根据主键修改数据
     * @param int $id 主键值
     * @param array $data 更新的数据 格式为 [`filed` => `value`, ]
     * @return int 操作影响的行数
     */
    public static function setItem($id, array $data)
    {
        $data = self::_fixFillAbleData($data);
        $start_time = microtime(true);
        $primary_key = static::primaryKey();
        unset($data[$primary_key]);
        $table = static::tableBuilder()->where($primary_key, $id);
        $update = $table->update($data);
        static::sqlDebug() && static::recordRunSql(microtime(true) - $start_time, $table->toSql(), $table->getBindings(), __METHOD__);
        return $update;
    }

    /**
     * 根据主键删除数据
     * @param int $id 主键值
     * @return int 操作影响的行数
     */
    public static function delItem($id)
    {
        $start_time = microtime(true);
        $primary_key = static::primaryKey();
        $table = static::tableBuilder()->where($primary_key, $id);
        $delete = $table->delete();
        static::sqlDebug() && static::recordRunSql(microtime(true) - $start_time, $table->toSql(), $table->getBindings(), __METHOD__);
        return $delete;
    }

    /**
     * 根据主键增加某字段的值
     * @param int $id 主键id
     * @param string $filed 需要增加的字段
     * @param int $value 需要改变的值 默认为 1
     * @return int  操作影响的行数
     */
    public static function incItem($id, $filed, $value = 1)
    {
        $start_time = microtime(true);
        $primary_key = static::primaryKey();
        $table = static::tableBuilder()->where($primary_key, $id);
        $increment = $table->increment($filed, $value);
        static::sqlDebug() && static::recordRunSql(microtime(true) - $start_time, $table->toSql(), $table->getBindings(), __METHOD__);
        return $increment;
    }

    /**
     * 根据主键减少某字段的值
     * @param int $id 主键id
     * @param string $filed 需要减少的字段
     * @param int $value 需要改变的值 默认为 1
     * @return int  操作影响的行数
     */
    public static function decItem($id, $filed, $value = 1)
    {
        $start_time = microtime(true);
        $primary_key = static::primaryKey();
        $table = static::tableBuilder()->where($primary_key, $id);
        $decrement = $table->decrement($filed, $value);
        static::sqlDebug() && static::recordRunSql(microtime(true) - $start_time, $table->toSql(), $table->getBindings(), __METHOD__);
        return $decrement;
    }

}