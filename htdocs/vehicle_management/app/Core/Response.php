<?php
/**
 * Core Response Class
 * 
 * Provides methods for sending JSON responses with proper headers.
 * Standardizes the response format across the application.
 */

namespace App\Core;

class Response
{
    /**
     * Send a JSON response and exit.
     */
    public static function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Send a success response.
     */
    public static function success($data = null, string $message = 'Success', int $statusCode = 200): void
    {
        $response = ['success' => true, 'message' => $message];
        if ($data !== null) {
            $response['data'] = $data;
        }
        self::json($response, $statusCode);
    }

    /**
     * Send an error response.
     */
    public static function error(string $message, int $statusCode = 400, $errors = null): void
    {
        $response = ['success' => false, 'message' => $message];
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        self::json($response, $statusCode);
    }

    /**
     * Send a paginated response.
     */
    public static function paginated(array $items, int $total, int $page, int $perPage): void
    {
        self::json([
            'success'  => true,
            'data'     => $items,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
        ]);
    }

    /**
     * Set CORS headers.
     */
    public static function cors(): void
    {
        if (isset($_SERVER['HTTP_ORIGIN']) && !headers_sent()) {
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Auth-Token, X-Session-Token');
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}
