<?php
/**
 * Core Request Class
 * 
 * Encapsulates HTTP request data (GET, POST, JSON body, headers).
 * Provides a clean interface to access request parameters.
 */

namespace App\Core;

class Request
{
    private array $get;
    private array $post;
    private array $body;
    private array $headers;
    private string $method;
    private string $uri;

    public function __construct()
    {
        $this->get     = $_GET ?? [];
        $this->post    = $_POST ?? [];
        $this->headers = $this->normalizeHeaders();
        $this->method  = $this->resolveMethod();
        $this->uri     = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        // Parse JSON body
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);
        $this->body = is_array($json) ? $json : [];
    }

    /**
     * Get HTTP method (supports _method override for forms).
     */
    public function method(): string
    {
        return $this->method;
    }

    /**
     * Get request URI path.
     */
    public function uri(): string
    {
        return $this->uri;
    }

    /**
     * Get a parameter from input (checks JSON body, POST, GET in order).
     */
    public function input(string $key, $default = null)
    {
        return $this->body[$key]
            ?? $this->post[$key]
            ?? $this->get[$key]
            ?? $default;
    }

    /**
     * Get all input data merged.
     */
    public function all(): array
    {
        return array_merge($this->get, $this->post, $this->body);
    }

    /**
     * Get only specific keys from input.
     */
    public function only(array $keys): array
    {
        $all = $this->all();
        $result = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $all)) {
                $result[$key] = $all[$key];
            }
        }
        return $result;
    }

    /**
     * Get a query string parameter.
     */
    public function query(string $key, $default = null)
    {
        return $this->get[$key] ?? $default;
    }

    /**
     * Get a header value (lowercase key).
     */
    public function header(string $key, $default = null): ?string
    {
        return $this->headers[strtolower($key)] ?? $default;
    }

    /**
     * Extract Bearer token from Authorization header.
     */
    public function bearerToken(): ?string
    {
        $auth = $this->header('authorization');
        if ($auth && preg_match('/Bearer\s+(.+)/i', $auth, $m)) {
            return trim($m[1]);
        }
        // Fallback to custom headers
        return $this->header('x-auth-token')
            ?? $this->header('x-session-token');
    }

    /**
     * Get the client IP address.
     */
    public function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Get the user agent string.
     */
    public function userAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Check if the request expects a JSON response.
     */
    public function expectsJson(): bool
    {
        $accept = $this->header('accept', '');
        return str_contains($accept, 'application/json')
            || str_contains($accept, '*/*')
            || !empty($this->header('x-requested-with'));
    }

    /**
     * Normalize all request headers to lowercase keys.
     */
    private function normalizeHeaders(): array
    {
        $h = [];
        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $k => $v) {
                $h[strtolower($k)] = $v;
            }
        } else {
            foreach ($_SERVER as $k => $v) {
                if (str_starts_with($k, 'HTTP_')) {
                    $name = strtolower(str_replace(' ', '-', str_replace('_', ' ', substr($k, 5))));
                    $h[$name] = $v;
                }
            }
        }
        return $h;
    }

    /**
     * Resolve the actual HTTP method (supports _method override).
     */
    private function resolveMethod(): string
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($method === 'POST') {
            $override = $_POST['_method'] ?? $this->body['_method'] ?? null;
            if ($override) {
                $method = strtoupper($override);
            }
        }
        return $method;
    }
}
