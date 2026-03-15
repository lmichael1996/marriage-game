<?php

/**
 * Handles authentication API endpoints (login for admin, player, judge).
 */
class AuthController
{
    public function playerLogin(): void
    {
        $data = Router::input();
        $result = Container::auth()->playerLogin($data['username'] ?? '', $data['room_code'] ?? '');
        if (!($result['success'] ?? false)) {
            Router::error($result['error'] ?? 'Login fallito', 422);
        }
        Router::respond([
            'success'  => true,
            'redirect' => '../public/player.php',
        ]);
    }

    public function adminLogin(): void
    {
        $data = Router::input();
        $result = Container::auth()->adminLogin($data['username'] ?? '', $data['password'] ?? '');
        if (!($result['success'] ?? false)) {
            Router::error($result['error'] ?? 'Login fallito', 401);
        }
        Router::respond([
            'success'  => true,
            'redirect' => '../public/admin.php',
        ]);
    }

    public function judgeLogin(): void
    {
        $data = Router::input();
        $result = Container::auth()->judgeLogin($data['room_code'] ?? '');
        if (!($result['success'] ?? false)) {
            Router::error($result['error'] ?? 'Login fallito', 401);
        }
        Router::respond([
            'success'  => true,
            'redirect' => '../public/judge.php',
        ]);
    }
}
