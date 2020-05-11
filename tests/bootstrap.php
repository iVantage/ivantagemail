<?php
namespace IvantageMailTest;

use Zend\Loader\AutoloaderFactory;
use Zend\Mvc\Service\ServiceManagerConfig;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\ArrayUtils;
use RuntimeException;
use Doctrine\ORM\Tools\SchemaTool;

error_reporting(E_ALL | E_STRICT);
chdir(__DIR__);

class Bootstrap
{
    protected static $serviceManager;
    protected static $config;
    protected static $bootstrap;

    public static function init() {

        // Get the autoloader
        $files = array(__DIR__ . '/../vendor/autoload.php', __DIR__ . '/../../../autoload.php');

        foreach ($files as $file) {
            if (file_exists($file)) {
                $loader = require $file;
                break;
            }
        }

        if (! isset($loader)) {
            throw new RuntimeException('vendor/autoload.php could not be found. Did you run `php composer.phar install`?');
        }

        // Load the IvantageMail module and its companion test suite.
        $loader->add('IvantageMailTest', __DIR__);
        $loader->add('IvantageMail', __DIR__ . '/../src');

        // Load additional module requirements (i.e. Doctrine modules) via the module
        // manager. In theory we should be able to load the main IvantageMail module
        // in this way, but for some reason I haven't been able to get that to work.
        if (file_exists(__DIR__ . '/TestConfig.php')) {
            $config = require __DIR__ . '/TestConfig.php';
        } else {
            $config = require __DIR__ . '/TestConfig.php.dist';
        }
        $_emptyConfig = new ServiceManagerConfig();
        $emptyConfig = $_emptyConfig->ToArray();
        $serviceManager = new ServiceManager($emptyConfig);
        $serviceManager->setService('ApplicationConfig', $config);
        $serviceManager->get('ModuleManager')->loadModules();

        static::$serviceManager = $serviceManager;
        static::$config = $config;
    }

    public static function getServiceManager()
    {
        return static::$serviceManager;
    }



}

Bootstrap::init();
