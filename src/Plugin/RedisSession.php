<?php
/**
 * Created by PhpStorm.
 * User: kongl
 * Date: 2018/2/6 0006
 * Time: 12:00
 */

namespace Tiny\Plugin;


use Tiny\Application;
use Tiny\Traits\CacheTrait;

class RedisSession implements \SessionHandlerInterface
{

    private $_pre_fix = '';
    private $_db = 0;
    private $_lifeTime = 360000;
    private $_salt = '_session_';

    /**
     * 构造函数
     * @param string $pre_fix
     * @param int $db
     * @param int $lifeTime
     * @param string $salt
     */
    public function __construct($pre_fix = '', $db = 0, $lifeTime = 36000, $salt = '_session_')
    {
        $this->_pre_fix = $pre_fix;
        $this->_db = $db;
        $this->_lifeTime = $lifeTime;
        $this->_salt = $salt;
    }

    /**
     * 开始使用该驱动的session
     */
    public function begin()
    {

    }

    private function _encodeData($sessionData)
    {
        if (empty($sessionData)) {
            return '';
        }
        $gz = gzencode($sessionData, 9);
        return Application::encrypt($gz, $this->_lifeTime, $this->_salt);
    }

    private function _decodeData($sessionData)
    {
        if (empty($sessionData)) {
            return '';
        }
        $sessionData = Application::decrypt($sessionData, $this->_salt);
        $gz = gzdecode($sessionData);
        return $gz;
    }

    /**
     * 自动开始回话或者session_start()开始回话后第一个调用的函数
     * 类似于构造函数的作用
     * @param string $savePath 默认的保存路径
     * @param string $sessionName 默认的参数名，PHPSESSID
     * @return bool
     */
    public function open($savePath, $sessionName)
    {
        return true;
    }

    /**
     * 类似于析构函数，在write之后调用或者 session_write_close() 函数之后调用
     */
    public function close()
    {
        return true;
    }

    /**
     * 读取session信息
     * @param string $sessionId 通过该Id唯一确定对应的session数据
     * @return string session信息/空串
     */
    public function read($sessionId)
    {
        $redis = CacheTrait::_getRedisInstance();
        if (!empty($this->_db)) {
            $redis->select($this->_db);
        }
        if (!empty($this->_pre_fix)) {
            $sessionId = "{$this->_pre_fix}:{$sessionId}";
        }
        $sessionData = $redis->get(trim($sessionId));
        return $this->_decodeData($sessionData);
    }

    /**
     * 写入或者修改session数据
     * @param string $sessionId 要写入数据的session对应的id
     * @param string $sessionData 要写入的数据，已经序列化过了
     * @return bool
     */
    public function write($sessionId, $sessionData)
    {
        $redis = CacheTrait::_getRedisInstance();
        if (!empty($this->_db)) {
            $redis->select($this->_db);
        }
        if (!empty($this->_pre_fix)) {
            $sessionId = "{$this->_pre_fix}:{$sessionId}";
        }
        $sessionData = $this->_encodeData($sessionData);
        if (!empty($sessionData)) {
            return $redis->setex(trim($sessionId), $this->_lifeTime, $sessionData);
        } else {
            return $redis->del(trim($sessionId));
        }
    }

    /**
     * 主动销毁session会话
     * @param string $sessionId 要销毁的会话的唯一id
     * @return bool
     */
    public function destroy($sessionId)
    {
        $redis = CacheTrait::_getRedisInstance();
        if (!empty($this->_db)) {
            $redis->select($this->_db);
        }
        if (!empty($this->_pre_fix)) {
            $sessionId = "{$this->_pre_fix}:{$sessionId}";
        }
        return $redis->delete(trim($sessionId)) >= 1;
    }

    /**
     * 清理绘画中的过期数据
     * @param int $lifeTime 有效期
     * @return bool
     */
    public function gc($lifeTime)
    {
        return true;
    }


}