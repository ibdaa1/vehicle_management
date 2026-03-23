<?php
/**
 * Core Router Class
 * 
 * Simple router that maps HTTP method + URI pattern to controller actions.
 * Supports route parameters like /roles/{id}.
 */

namespace App\Core;

class Router
{
    private array $routes = [];
    private string $basePath;

    public function __construct(string $basePath = '')
    {
        $this->basePath = rtrim($basePath, '/');
    }

    /**
     * Register a GET route.
     */
    public function get(string $path, string $controller, string $action): self
    {
        return $this->addRoute('GET', $path, $controller, $action);
    }

    /**
     * Register a POST route.
     */
    public function post(string $path, string $controller, string $action): self
    {
        return $this->addRoute('POST', $path, $controller, $action);
    }

    /**
     * Register a PUT route.
     */
    public function put(string $path, string $controller, string $action): self
    {
        return $this->addRoute('PUT', $path, $controller, $action);
    }

    /**
     * Register a DELETE route.
     */
    public function delete(string $path, string $controller, string $action): self
    {
        return $this->addRoute('DELETE', $path, $controller, $action);
    }

    /**
     * Add a route to the internal routes table.
     */
    private function addRoute(string $method, string $path, string $controller, string $action): self
    {
        $this->routes[] = [
            'method'     => $method,
            'pattern'    => $this->basePath . '/' . ltrim($path, '/'),
            'controller' => $controller,
            'action'     => $action,
        ];
        return $this;
    }

    /**
     * Dispatch the request to the matching route.
     *
     * @return array|null ['controller', 'action', 'params'] or null if no match
     */
    public function dispatch(Request $request): ?array
    {
        $uri    = rtrim($request->uri(), '/') ?: '/';
        $method = $request->method();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->matchRoute($route['pattern'], $uri);
            if ($params !== false) {
                return [
                    'controller' => $route['controller'],
                    'action'     => $route['action'],
                    'params'     => $params,
                ];
            }
        }

        return null;
    }

    /**
     * Match a route pattern against a URI.
     * Returns extracted params array on match, false otherwise.
     *
     * Pattern: /api/v1/roles/{id}  =>  regex: /api/v1/roles/([^/]+)
     */
    private function matchRoute(string $pattern, string $uri)
    {
        $pattern = rtrim($pattern, '/') ?: '/';

        // Convert {param} to named regex groups
        $regex = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (preg_match($regex, $uri, $matches)) {
            // Extract only named parameters
            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            return $params;
        }

        return false;
    }
}
