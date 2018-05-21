<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/9/29
 * Time: 14:55
 */

namespace Tiny\OrmQuery;


class whereIn extends AbstractQuery
{
    public $values = null;

    /**
     * whereBetween constructor.
     * @param array $values
     * @param callable|null $filter 本条件是否生效的回调函数 参数为自身
     */
    public function __construct($values, callable $filter = null)
    {
        $this->values = $values;

        parent::__construct($filter);
    }

    /**
     * @return array  返回 $query格式的数组  表示查询参数数组
     */
    protected function _queryArgs()
    {
        return [$this->values];
    }
}
