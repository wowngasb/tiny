<?php

namespace Tiny\Plugin;

use Illuminate\Database\Capsule\Manager;
use Tiny\Application;
use Tiny\Func;

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
        $db_config = Application::app()->getEnv('ENV_DB');
        $db_config = [
            'driver' => Func::v($db_config, 'driver', 'mysql'),
            'host' => Func::v($db_config, 'host', '127.0.0.1'),
            'port' => Func::v($db_config, 'port', 3306),
            'database' => Func::v($db_config, 'database', 'test'),
            'username' => Func::v($db_config, 'username', 'root'),
            'password' => Func::v($db_config, 'password', ''),
            'charset' => Func::v($db_config, 'charset', 'utf8'),
            'collation' => Func::v($db_config, 'collation', 'utf8_unicode_ci'),
            'prefix' => Func::v($db_config, 'prefix', ''),
        ];
        return $db_config;
    }

    /**
     * @param string|array $config
     * @return \Illuminate\Database\Connection
     */
    public function getConnection($config = null)
    {
        if (is_string($config)) {
            $db_config = self::getBaseConfig();
            $db_config['database'] = strtolower($config);
            $key = $db_config['database'];
        } else if (is_array($config)) {
            $db_config = $config;
            $key = "{$db_config['host']}:{$db_config['port']}@{$db_config['username']}#{$db_config['database']}";
        } else {
            $db_config = $config;
            $key = md5(print_r($config, true));
        }
        parent::addConnection($db_config, $key);
        return $this->manager->connection($key);
    }

}


