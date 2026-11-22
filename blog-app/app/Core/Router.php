<?php
namespace App\Core;

class Router {
    private static array $routes = [];

    public static function get(string $path, callable|array $handler): void {
        self::add('GET', $path, $handler);
    }

    public static function post(string $path, callable|array $handler): void {
        self::add('POST', $path, $handler);
    }

    public static function put(string $path, callable|array $handler): void {
        self::add('PUT', $path, $handler);
    }

    public static function delete(string $path, callable|array $handler): void {
        self::add('DELETE', $path, $handler);
    }

    private static function add(string $method, string $path, callable|array $handler): void {
        self::$routes[] = [
            'method'  => $method,
            'path'    => trim($path, '/'),
            'handler' => $handler
        ];
    }

    public static function dispatch(string $uri, string $method): void {
        $parsedUri = trim(parse_url($uri, PHP_URL_PATH), '/');
        
        // Strip project subfolder if running in subdirectory
        $scriptDir = trim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
        if (!empty($scriptDir) && str_starts_with($parsedUri, $scriptDir)) {
            $parsedUri = trim(substr($parsedUri, strlen($scriptDir)), '/');
        }

        // Support PUT/DELETE override via _method header/field
        if ($method === 'POST') {
            $method = strtoupper($_POST['_method'] ?? $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? 'POST');
        }

        foreach (self::$routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            // Convert {param} placeholders to regex
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $parsedUri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                $handler = $route['handler'];
                if (is_array($handler)) {
                    [$class, $action] = $handler;
                    $controller = new $class();
                    $controller->$action($params);
                } else {
                    call_user_func($handler, $params);
                }
                return;
            }
        }

        Response::error('Endpoint not found', 404);
    }
}
