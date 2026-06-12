<?php

// AuthGuard — Guard Pattern pentru protejarea paginilor și rutelor
class AuthGuard
{
    public function __construct(private readonly UserService $userService) {}

    public function requireAuth(): void
    {
        if (!$this->userService->isLoggedIn()) {
            header('Location: /pages/auth.php?error=unauthorized');
            exit;
        }
    }

    public function requireAdmin(): void
    {
        $this->requireAuth();
        $role = $this->userService->getCurrentRole();
        if ($role !== 'admin') {
            header('Location: /pages/auth.php?error=forbidden');
            exit;
        }
    }

    public function requireGuest(): void
    {
        if ($this->userService->isLoggedIn()) {
            header('Location: /pages/home.php');
            exit;
        }
    }
}