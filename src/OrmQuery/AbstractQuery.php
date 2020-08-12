<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/29 0029
 * Time: 10:15
 */

namespace Tiny\OrmQuery;


use Tiny\Util;

abstract class AbstractQuery
{

    protected static $_allow_operator = [
        '=' => 1,
        '!=' => 1,
        '>' => 1,
        '<' => 1,
        '>=' => 1,
        '<=' => 1,
        '<>' => 1,
        'like' => 1,
        'LIKE' => 1,
    ];

    protected $_filter = null;

    public function __construct(callable $filter = null)
    {
        $this->_filter = $filter;
    }

    final public function buildQuery($column)
    {
        $action = Util::class2name(get_class($this));
        $action = $action == 'whereQ' ? 'where' : $action;
        $action = $action == 'orWhereQ' ? 'orWhere' : $action;

        $enable = !empty($this->_filter) ? call_user_func_array($this->_filter, []) : true;

        $query = $this->_queryArgs();
        if (!empty($column)) {
            if (is_array($query)) {
                array_unshift($query, $column);  //把字段名插入到 参数的第一个
            } else {
                $query = [$column,];
            }
        }
        return [$enable, $action, $query];
    }

    /**
     * @return array  返回 $query格式的数组  表示查询参数数组
     */
    abstract protected function _queryArgs();

}