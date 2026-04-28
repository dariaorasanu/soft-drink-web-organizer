<?php

//pentru a vedea si mentine exact cine e logat - Guard Pattern
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
        $user = $this->userService->getCurrentUser();
        if (!$user?->isAdmin()) {
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