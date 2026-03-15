<?php
/**
 * Core Application Class
 * 
 * Bootstraps the MVC application: loads config, initializes database,
 * registers routes, and dispatches requests to controllers.
 */

namespace App\Core;

class App
{
    private static ?App $instance = null;
    private Router $router;
    private Request $request;
    private array $config;
    private string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
        $this->config   = [];

        self::$instance = $this;
    }

    /**
     * Get the singleton App instance.
     */
    public static function getInstance(): ?self
    {
        return self::$instance;
    }

    /**
     * Get the application base path.
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Bootstrap the application.
     */
    public function boot(): self
    {
        // Load configuration
        $this->loadConfig();

        // Set timezone and encoding
        date_default_timezone_set($this->config['app']['timezone'] ?? 'Asia/Dubai');
        if (function_exists('mb_internal_encoding')) {
            mb_internal_encoding($this->config['app']['charset'] ?? 'UTF-8');
        }

        // Error handling based on debug config
        $debug = $this->config['app']['debug'] ?? false;
        if ($debug) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
            ini_set('display_errors', '0');
        }
        ini_set('log_errors', '1');
        ini_set('default_charset', 'UTF-8');

        // Initialize database (guard: config might be missing if file not found)
        $dbConfig = $this->config['database'] ?? null;
        if (!is_array($dbConfig) || empty($dbConfig)) {
            throw new \RuntimeException(
                'Database configuration missing. Ensure config/database.php exists and returns an array.'
            );
        }
        Database::init($dbConfig);

        // Initialize request
        $this->request = new Request();

        // Initialize router with base URL path
        $baseUrl = $this->config['app']['base_url'] ?? '';
        $this->router = new Router($baseUrl);

        return $this;
    }

    /**
     * Get the router instance for registering routes.
     */
    public function router(): Router
    {
        return $this->router;
    }

    /**
     * Get the request instance.
     */
    public function request(): Request
    {
        return $this->request;
    }

    /**
     * Get a config value.
     */
    public function config(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->config;
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        return $value;
    }

    /**
     * Run the application: dispatch the request to a controller.
     */
    public function run(): void
    {
        // Reset response state for new request
        Response::reset();

        // Set CORS headers
        Response::cors();
        if (Response::isSent()) {
            return;
        }

        // Dispatch to route
        $match = $this->router->dispatch($this->request);

        if ($match === null) {
            Response::error('Route not found', 404);
            return;
        }

        $controllerClass = $match['controller'];
        $action          = $match['action'];
        $params          = $match['params'];

        if (!class_exists($controllerClass)) {
            Response::error('Controller not found: ' . $controllerClass, 500);
            return;
        }

        // Wrap controller instantiation in try-catch
        // Controller constructors create model instances which could fail
        try {
            $controller = new $controllerClass();
        } catch (\Throwable $e) {
            error_log("Controller instantiation failed ({$controllerClass}): " . $e->getMessage());
            Response::error('Failed to initialize controller: ' . $e->getMessage(), 500);
            return;
        }

        if (!method_exists($controller, $action)) {
            Response::error('Action not found: ' . $action, 500);
            return;
        }

        // Call the controller action with request and route params
        try {
            $controller->$action($this->request, $params);
        } catch (\Throwable $e) {
            $cls = get_class($e);
            $msg = $e->getMessage();
            $file = $e->getFile() . ':' . $e->getLine();
            error_log("Controller action failed ({$controllerClass}::{$action}) [{$cls}]: {$msg} in {$file}");
            if (!Response::isSent()) {
                Response::error("Controller error: {$msg}", 500);
            }
        }
    }

    /**
     * Load configuration files from the config directory.
     */
    private function loadConfig(): void
    {
        $configDir = $this->basePath . '/config';

        $configFiles = ['app', 'database'];
        foreach ($configFiles as $name) {
            $file = $configDir . '/' . $name . '.php';
            if (file_exists($file)) {
                $this->config[$name] = require $file;
            }
        }
    }
}
