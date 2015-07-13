<?php

namespace Caspian;

use Caspian\Api\JsonHTTPResponse;
use Caspian\Database\Collection;
use Caspian\Events\BundleEvent;
use Caspian\Events\ControllerEvent;
use Caspian\Events\CoreEvent;
use Caspian\Events\RequestEvent;
use Caspian\Events\RoutingEvent;
use Caspian\Utils\Image;
use Caspian\Utils\IO;
use Caspian\Utils\Request;
use Caspian\Security\Input;
use Whoops\Run;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Handler\JsonResponseHandler;

class Application
{
    const VERSION    = '2.0.0';
    const POWERED_BY = 'Caspian 2.0.0';

    /* Environment */
    const CLI         = 'CLI';
    const DEVELOPMENT = 'development';
    const STAGING     = 'staging';
    const PRODUCTION  = 'production';

    /**
     *
     * Events instance
     * @var \Caspian\Event
     *
     */
    public $events;

    /**
     *
     * Session instance
     * @var \Caspian\Session\Session
     *
     */
    public $session;

    /**
     *
     * Configuration
     * @var \stdClass
     *
     */
    public $config;

    /**
     *
     * Constants
     * @var \stdClass
     *
     */
    public $constants;

    /**
     *
     * Environment
     * @var String
     *
     */
    public $environment = "";

    /**
     *
     * Application's url
     * @var String
     *
     */
    public $site_url = "";

    /**
     *
     * The directory where the application is (if not a docroot)
     * @var String
     *
     */
    public $url_path = "";

    /**
     *
     * Root path
     * @var String
     *
     */
    public $root_path = "";

    /**
     *
     * The current requested uri
     * @var String
     *
     */
    public $uri = "";

    /**
     *
     * Current domain
     * @var String
     *
     */
    public $domain = "";

    /**
     *
     * Current detected route
     * @var \stdClass
     *
     */
    public $route;

    /**
     *
     * Alternate routes (for other languages)
     * @var \stdClass
     *
     */
    public $alternate_routes;

    /**
     *
     * Bundle instances
     * @var \stdClass
     *
     */
    public $bundles;

    /**
     *
     * Helper instances
     * @var \stdClass
     *
     */
    public $helpers;

    /**
     *
     * Upload manager
     *
     * @var \Caspian\Upload
     *
     */
    public $upload;

    /**
     *
     * Locale instance
     * @var \Caspian\Locale
     *
     */
    public $locale;

    /**
     *
     * Input instance
     * @var \Caspian\Security\Input
     *
     */
    public $input;

    /**
     *
     * Routing instance
     * @var \Caspian\Routing
     *
     */
    public $router;

    /**
     *
     * Application's static instance
     * @var \Caspian\Application
     *
     */
    public static $instance;

    /**
     *
     * Environment static version
     * @var
     *
     */
    public static $_environment = "";

    /**
     *
     * Create a new application
     *
     * @access  public
     * @final
     *
     */
    final public function __construct()
    {
        if (empty($_SERVER['REQUEST_URI'])) {
            $_SERVER['REQUEST_URI'] = '/';
        }

        $this->bundles  = new \stdClass;
        $this->helpers  = new \stdClass;
        self::$instance = $this;

        $this->autoload();
        $this->events = new EventDispatcher;

        Configuration::loadAppConfigurations();

        $this->config    = Configuration::getObject('configuration');
        $this->constants = Configuration::getObject('constants');

        /* CORS Support */
        if ($this->config->cors->allow != 'none' && php_sapi_name() != 'cli') {
            header('Access-Control-Allow-Origin: ' . $this->config->cors->allow);
            header('Access-Control-Allow-Methods: ' . implode(",", $this->config->cors->methods));
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Headers: X-Requested-With');
        }

        /* Set timezone if not set */
        date_default_timezone_set($this->config->general->timezone);

        /* Set UTF-8 for document charset (header) */
        if (php_sapi_name() != 'cli') {
            header('X-Powered-By: ' . self::POWERED_BY);
        }

        /* Environment */
        $this->setupEnvironment();

        /* Debugging */
        $this->setupDebugging();

        /* Session management */
        $handler = new Session($this->config->development->session);
        session_set_save_handler(
            array($handler, 'open'),
            array($handler, 'close'),
            array($handler, 'read'),
            array($handler, 'write'),
            array($handler, 'destroy'),
            array($handler, 'garbageCollect')
        );

        /* Prevent problems since we are using OO for session management */
        register_shutdown_function('session_write_close');
        session_start();
        $this->session = $handler;

        /* Routing (load routes) */
        $this->router = new Routing;
        $this->router->loadAppRoutes();

        /* EVENT : BundleEvent PRE */
        $this->events->trigger(new BundleEvent(BundleEvent::PRE));

        /* Load bundles */
        if(is_dir(dirname(__DIR__) . '/bundles/')) {
            $this->loadBundles();
        }

        /* EVENT : BundleEvent POST */
        $this->events->trigger(new BundleEvent(BundleEvent::POST));
        
        /* Load helpers */
        $this->loadHelpers();

        /* EVENT : RequestEvent PRE */
        $this->events->trigger(new RequestEvent(RequestEvent::PRE));

        /* Request data (client, ip, request type, etc.) */
        $request       = new Request;
        $this->request = $request;

        /* EVENT : RequestEvent POST */
        $this->events->trigger(new RequestEvent(RequestEvent::POST, $this->request));

        /* EVENT : RoutingEvent PRE */
        $this->events->trigger(new RoutingEvent(RoutingEvent::PRE));

        /* Find route */
        $this->route = $this->router->find($this->uri);

        if (!empty($this->route)) {
            $this->alternate_routes = $this->router->alternate();
        }

        /* EVENT : RoutingEvent POST */
        $this->events->trigger(new RoutingEvent(RoutingEvent::POST, $this->route));

        /* Locale */
        Locale::enforceLocale();
        Locale::loadAll();
        $this->locale = new Locale;

        /* EVENT : CoreEvent LOCALE */
        $this->events->trigger(new CoreEvent(CoreEvent::LOCALE, $this->locale));

        /* Make sure temp folder is writable by the server */
        IO::isDirectoryWritable($this->root_path . '/tmp/', true, false, true);

        /* Input */
        $this->input = new Input;

        /* Upload */
        $this->upload = new Upload;
    }

    /**
     *
     * Run the application
     *
     * @access  public
     * @final
     *
     */
    final public function run()
    {
        /* EVENT : ControllerEvent PRE */
        $this->events->trigger(new ControllerEvent(ControllerEvent::PRE));

        $lang = $this->session->get('app_language');

        if(empty($lang)) {
            $lang = Configuration::get('configuration', 'languages.default');
        }

        setlocale(LC_ALL, $this->config->localization->{$lang} . '.utf8');

        /* IE8 fallback */
        if($this->config->general->allow_ie8 == Configuration::NO && $this->request->ie8 == true) {
            $file = $this->root_path . '/app/Controllers/Error.php';
            include_once $file;
            $instance = new \ErrorController;
            $instance->notSupported();
            $this->quit();
        }

        if (!empty($this->route->action)) {
            list($method, $controller) = explode('@', $this->route->action);

            $name  = ucfirst(strtolower($controller)) . '.php';
            $file  = $this->route->path . '/Controllers/' . $name;
            $class = ucfirst(strtolower($controller)) . 'Controller';

            if (file_exists($file)) {
                include_once $file;

                if (class_exists($class)) {
                    $instance = new $class;

                    if (method_exists($instance, $method)) {
                        call_user_func_array(array($instance, $method), $this->route->arguments);

                        /* EVENT : ControllerEvent POST */
                        $this->events->trigger(new ControllerEvent(ControllerEvent::POST));

                        if ($instance->view->rendered == false) {
                            call_user_func(array($instance->view, 'render'), $method);
                        }
                    } else {
                        throw new \RuntimeException("Method '{$method}' in controller '{$name}' cannot be called, not found.'");
                    }
                } else {
                    throw new \RuntimeException("Controller '{$name}' does not respect convention, expecting class named '{$class}'");
                }
            } else {
                $path = dirname($file);
                throw new \RuntimeException("Controller '{$name}' could not be found in path {$path}.");
            }
        } else {
            Locale::switchLocale(Configuration::get('configuration', 'languages.default'));

            $file = $this->root_path . '/app/Controllers/Error.php';
            include_once $file;
            $instance = new \ErrorController;
            $instance->notFound();
        }
    }

    /**
     *
     * handle application shutdown
     *
     * @access  public
     *
     */
    public function quit()
    {
        Collection::disconnect();
        $this->events->trigger(new CoreEvent(CoreEvent::SHUTDOWN));
        exit();
    }

    /**
     *
     * Setup autoloaders
     *
     * @access  private
     * @final
     *
     */
    final private function autoload()
    {
        /* Autoload composer */
        include_once dirname(__DIR__) . '/vendor/autoload.php';

        /* Caspian autoload */
        spl_autoload_register(__NAMESPACE__ . '\Application::runAutoload');
    }

    /**
     *
     * Caspian's custom autoload
     *
     * @param   string  class name that is being requested
     * @return  bool    success / failure
     * @final
     * @static
     *
     */
    final static function runAutoload($class)
    {
        /* Remove namespace from class name */
        $class = str_replace('\\', '/', $class);

        /* Look in the caspian system folder */
        if (file_exists(dirname(__DIR__) . '/' . $class . '.php')) {
            include_once dirname(__DIR__) . '/' . $class . '.php';
            return true;
        }

        /* Look for models */
        if (file_exists(dirname(__DIR__) . '/app/Models/' . $class . '.php')) {
            include_once dirname(__DIR__) . '/app/Models/' . $class . '.php';
            return true;
        }

        /* Look for Tuna's core if bundle is installed */
        if(class_exists('TunaBundle')) {
            if(file_exists((dirname(__DIR__) . '/bundles/' . $class . '.php'))) {
                include_once dirname(__DIR__) . '/bundles/' . $class . '.php';
            }
        }

        return false;
    }

    /**
     *
     * Setup the application environment variables (host, environment, site url, etc.)
     *
     * @access  private
     *
     */
    private function setupEnvironment()
    {
        if (empty($_SERVER['HTTP_HOST'])) {
            $_SERVER['HTTP_HOST'] = self::CLI;
        }

        if ($_SERVER['HTTP_HOST'] == self::CLI) {
            $this->environment  = Configuration::get('definition', 'cli');
            self::$_environment = $this->environment;
            $this->site_url     = 'http://';
            $this->root_path    = dirname(__DIR__);
            return;
        }

        $protocol = Request::HTTP;

        if (!empty($_SERVER['HTTPS']) AND $_SERVER['HTTPS'] != 'off') {
            $protocol = Request::HTTPS;
        }

        $uri    = explode('?', $_SERVER['REQUEST_URI']);
        $uri[0] = str_replace('.html', '', $uri[0]);

        $this->root_path = dirname(__DIR__);
        $this->url_path  = str_replace($_SERVER['DOCUMENT_ROOT'], '', $this->root_path);
        $this->site_url  = $protocol . '://' . $_SERVER['HTTP_HOST'] . $this->url_path;
        $this->uri       = str_replace('//', '/', str_replace($this->url_path, '/', $uri[0]));
        $this->domain    = str_replace('www.', '', $_SERVER['HTTP_HOST']);

        $domain = str_replace('www.', '', $_SERVER['HTTP_HOST']);

        $dev    = Configuration::get('definition', 'environments.' . self::DEVELOPMENT);
        $stage  = Configuration::get('definition', 'environments.' . self::STAGING);
        $prod   = Configuration::get('definition', 'environments.' . self::PRODUCTION);

        if (in_array($domain, $stage)) {
            $this->environment = self::STAGING;
        } elseif (in_array($domain, $prod)) {
            $this->environment = self::PRODUCTION;
        } elseif (in_array($domain, $dev)) {
            $this->environment = self::DEVELOPMENT;
        } else {
            $this->environment = self::PRODUCTION;
        }

        self::$_environment = $this->environment;
    }

    /**
     *
     * Setup the debugging system
     *
     * @access  private
     *
     */
    private function setupDebugging()
    {
        if ($this->environment == self::DEVELOPMENT || ($this->environment == self::STAGING && $this->config->development->debugging == Configuration::YES)) {
            error_reporting(E_ALL);

            $whoops      = new Run();
            $errorPage   = new PrettyPageHandler;
            $jsonHandler = new JsonResponseHandler;

            $errorPage->setPageTitle('Caspian Exception');
            $errorPage->setEditor('sublime');
            $errorPage->addDataTable('Platform', array(
                'Caspian version' => self::VERSION,
                'PHP version'     => phpversion() . '-' . PHP_OS
            ));

            $jsonHandler->onlyForAjaxRequests(true);
            $whoops->pushHandler($errorPage);
            $whoops->pushHandler($jsonHandler);
            $whoops->register();

            ini_set('display_errors', 'On');
        } else {
            error_reporting(0);
            ini_set('display_errors', 'Off');
        }
    }

    /**
     *
     * load all bundles
     *
     * @access  private
     * @throws  \RuntimeException
     *
     */
    private function loadBundles()
    {
        $directory = new \RecursiveDirectoryIterator($this->root_path . '/bundles/');
        $iterator  = new \RecursiveIteratorIterator($directory);

        $files = array();
        foreach ($iterator as $info) {
            if (stristr($info->getPathname(), 'bundle.php')) {
                $files[] = $info->getPathname();
            }
        }
        
        if (!empty($files)) {
            foreach ($files as $bundle) {
                include_once $bundle;

                $name       = str_replace('.php', '', basename($bundle));
                $clean_name = strtolower(str_replace('Bundle', '', $name));
                $class      = ucfirst($name);

                if (!class_exists($class)) {
                    throw new \RuntimeException("The bundle '{$name}' is not valid bundle. It must contain the {$class} class.");
                }

                $instance = new $class;
                $parent   = get_parent_class($instance);
                
                if ($parent != 'Caspian\Bundle') {
                    throw new \RuntimeException("The class '{$name}' is not valid bundle class. It must extend the Caspian\\Bundle class.");
                }

                $this->bundles->{$clean_name} = $instance;

                /* Load models for that bundle */
                $models = glob(dirname($bundle) . '/Models/*.php');
                if (!empty($models)) {
                    foreach ($models as $model) {
                        include_once $model;
                    }
                }

                /* Load helpers for that bundle */
                $helpers = glob(dirname($bundle) . '/Helpers/*.php');
                if (!empty($helpers)) {
                    foreach ($helpers as $helper) {
                        include_once $helper;

                        $name  = str_replace('.php', '', basename($helper));
                        $clean_name = strtolower($name);
                        $class = ucfirst($name);

                        if (class_exists($class)) {
                            $this->helpers->{$clean_name} = new $class;
                        }
                    }
                }

                /* Routing (load routes) */
                $this->router->loadBundleRoutes(dirname($bundle));

                /* Run init method */
                $instance->setBundlePath(dirname($bundle));
                $instance->verifyInstall();
            }
        }
    }

    /**
     *
     * Load all the possible helpers (system, app)
     *
     * @access  private
     *
     */
    private function loadHelpers()
    {
        /* System helpers */
        $sysfiles = glob($this->root_path . '/Caspian/Helpers/*.php');

        foreach ($sysfiles as $file) {
            include_once $file;

            $name  = strtolower(str_replace('.php', '', basename($file)));
            $class = 'Caspian\\Helpers\\' . ucfirst($name);

            if (class_exists($class)) {
                $this->helpers->{$name} = new $class;
            }
        }

        /* App helpers */
        $appfiles = glob($this->root_path . '/app/Helpers/*.php');

        foreach ($appfiles as $file) {
            include_once $file;

            $name  = strtolower(str_replace('.php', '', basename($file)));
            $class = ucfirst($name);

            if (class_exists($class)) {
                $this->helpers->{$name} = new $class;
            }
        }
    }
}
