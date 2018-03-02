<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/9/29
 * Time: 14:52
 */

namespace Tiny\OrmQuery;


class where extends AbstractQuery
{
    public $operator = null;
    public $value = null;


    /**
     * where constructor.
     * @param mixed $value
     * @param string $operator
     * @param callable|null $filter 本条件是否生效的回调函数 参数为自身
     */
    public function __construct($value, $operator = '=', callable $filter = null)
    {
        $operator = strtolower(trim($operator));
        $this->operator = !empty(self::$_allow_operator[$operator]) ? $operator : '=';
        $this->value = $value;

        parent::__construct($filter);
    }

    /**
     * @return array
     */
    protected function _queryArgs()
    {
        return [$this->operator, $this->value];
    }
}
