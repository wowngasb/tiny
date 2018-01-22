<?php
/**
 * Created by PhpStorm.
 * User: a
 * Date: 2017/9/21
 * Time: 16:41
 */

namespace Tiny\Abstracts;

use Tiny\Traits\CacheTrait;
use Tiny\Traits\EventTrait;
use Tiny\Traits\LogTrait;

class AbstractClass
{
    use EventTrait, LogTrait, CacheTrait;

    protected static $detail_log = false;

}