<?php

namespace Tiny\Plugin;

use Closure;
use Illuminate\Database\Capsule\Manager;
use PDO;
use Tiny\Application;
use Tiny\Exception\OrmStartUpError;
use Tiny\Util;

class DbHelper extends Manager
{
    /**
     * @return Manager|mixed
     */
    public static function initDb()
    {
        if (!empty(self::$instance)) {
            return self::$instance;
        }
        $db_config = self::getBaseConfig();
        $db = new DbHelper();
        $db->addConnection($db_config, $db_config['database']);
        $db->setAsGlobal();
        return self::$instance;
    }

    private static function getBaseConfig()
    {
        $db_config = Application::config('ENV_DB');
        $db_config = [
            'driver' => Util::v($db_config, 'driver', 'mysql'),
            'host' => Util::v($db_config, 'host', '127.0.0.1'),
            'port' => Util::v($db_config, 'port', 3306),
            'database' => Util::v($db_config, 'database', 'test'),
            'username' => Util::v($db_config, 'username', 'root'),
            'password' => Util::v($db_config, 'password', ''),
            'charset' => Util::v($db_config, 'charset', 'utf8'),
            'collation' => Util::v($db_config, 'collation', 'utf8_unicode_ci'),
            'prefix' => Util::v($db_config, 'prefix', ''),
            'options' => [
                PDO::ATTR_TIMEOUT => Util::v($db_config, 'timeout', 5),
            ]
        ];
        return $db_config;
    }

    private static $_connection_map = [];

    /**
     * @param string|array $config
     * @return \Illuminate\Database\Connection
     * @throws OrmStartUpError
     */
    public function getConnection($config = null)
    {
        $default_config = self::getBaseConfig();
        if (is_null($config)) {
            $db_config = $default_config;
            $name = $db_config['database'];
        } else if (is_string($config)) {
            $name = trim($config);
            if (empty($name)) {
                throw new OrmStartUpError('getConnection with empty database name');
            }
            $db_config = array_merge($default_config, ['database' => $name]);
        } else if (is_array($config)) {
            $db_config = array_merge($default_config, $config);
            $name = "mysql:://{$db_config['username']}:@{$db_config['host']}:{$db_config['port']}/{$db_config['database']}";
        } else {
            throw new OrmStartUpError('getConnection with error config type');
        }
        if (!empty(self::$_connection_map[$name])) {
            return self::$_connection_map[$name];
        }

        parent::addConnection($db_config, $name);
        $connection = $this->manager->connection($name);
        if (!empty($connection)) {
            $dispatcher = new DbDispatcher();
            $dispatcher->listen(['*'], function ($payload) {
                if (!empty(self::$_event_callback)) {
                    $type = self::_getTypeOfEvent($payload);
                    call_user_func_array(self::$_event_callback, [$type, $payload]);
                }
            });
            $connection->setEventDispatcher($dispatcher);
            self::$_connection_map[$name] = $connection;
        }
        return $connection;
    }

    private static function _getTypeOfEvent($event)
    {
        if (!is_object($event)) {
            return 'unknown';
        }
        $type = get_class($event);
        switch ($type) {
            case 'Illuminate\\Database\\Events\\QueryExecuted':
                return 'QueryExecuted';
            case 'Illuminate\\Database\\Events\\TransactionBeginning':
                return 'TransactionBeginning';
            case 'Illuminate\\Database\\Events\\TransactionCommitted':
                return 'TransactionCommitted';
            case 'Illuminate\\Database\\Events\\TransactionRolledBack':
                return 'TransactionRolledBack';
            default:
                return 'unknown';
        }
    }

    private static $_event_callback = null;

    /**
     * 回调函数 参数  callback($type, $event)
     * @param Closure $closure
     */
    public static function setOrmEventCallback(Closure $closure)
    {
        self::$_event_callback = $closure;
    }

}


