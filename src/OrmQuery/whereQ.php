<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/9/29
 * Time: 14:52
 */

namespace Tiny\OrmQuery;


class whereQ extends AbstractQuery
{
    public $qFunc = null;


    /**
     * where constructor.
     * @param callable $qFunc
     * @param string $operator
     * @param callable|null $filter 本条件是否生效的回调函数 参数为自身
     */
    public function __construct(callable $qFunc, callable $filter = null)
    {
        $this->qFunc = $qFunc;

        parent::__construct($filter);
    }

    /**
     * @return array
     */
    protected function _queryArgs()
    {
        return [$this->qFunc];
    }
}
