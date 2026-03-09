<?php

/**
 * Lightweight API router with declarative route map.
 *
 * Each route defines: method, controller class, action method, and optional middleware.
 * Middleware values: null (public), 'auth' (any logged-in user), 'admin' (admin only).
 */
class Router
{
    private array $routes = [];

    // ── Route registration ───────────────────────────────────────────────

    /**
     * Register a route.
     *
     * @param string      $endpoint    Endpoint name (e.g. 'player_login')
     * @param string      $method      HTTP method ('GET', 'POST', or '*' for any)
     * @param array       $handler     [ControllerClass::class, 'methodName']
     * @param string|null $middleware   null | 'auth' | 'admin'
     */
    public function add(string $endpoint, string $method, array $handler, ?string $middleware = null): self
    {
        $this->routes[$endpoint] = [
            'method'     => $method,
            'handler'    => $handler,
            'middleware'  => $middleware,
        ];
        return $this;
    }

    // ── Dispatch ─────────────────────────────────────────────────────────

    /**
     * Resolve and dispatch the current request.
     */
    public function dispatch(): void
    {
        $endpoint = $_GET['endpoint'] ?? '';
        if (!$endpoint && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $endpoint = self::input()['endpoint'] ?? '';
        }

        if (!isset($this->routes[$endpoint])) {
            self::error('Endpoint non trovato', 404);
        }

        $route = $this->routes[$endpoint];

        // HTTP method check
        if ($route['method'] !== '*' && $_SERVER['REQUEST_METHOD'] !== $route['method']) {
            self::error('Metodo non consentito', 405);
        }

        // Middleware
        match ($route['middleware']) {
            'auth'  => requireLoginJson(),
            'admin' => requireAdminJson(),
            default => null,
        };

        // Instantiate controller and call method
        [$class, $method] = $route['handler'];
        $controller = new $class();
        $controller->$method();
    }

    // ── Static helpers (available to all controllers) ────────────────────

    /** Parse JSON request body. */
    public static function input(): array
    {
        static $data = null;
        return $data ??= json_decode(file_get_contents('php://input'), true) ?? [];
    }

    /** Send a JSON success response and exit. */
    public static function respond(mixed $data): never
    {
        echo json_encode($data);
        exit;
    }

    /** Send a JSON error response and exit. */
    public static function error(string $msg, int $code = 400): never
    {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error'   => $msg,
        ]);
        exit;
    }
}
