<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/9/29
 * Time: 14:53
 */

namespace Tiny\OrmQuery;


class whereNull extends AbstractQuery
{

    /**
     * @return array  返回 $query格式的数组  表示查询参数数组
     */
    protected function _queryArgs()
    {
        return [];
    }
}
