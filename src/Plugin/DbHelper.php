<?php

namespace Tiny\Plugin;

use Illuminate\Database\Capsule\Manager;
use Tiny\Application;
use Tiny\Exception\OrmStartUpError;
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
        $db_config = Application::get_config('ENV_DB');
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
        parent::addConnection($db_config, $name);
        return $this->manager->connection($name);
    }

}


