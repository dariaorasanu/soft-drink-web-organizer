<?php

require_once __DIR__ . '/../service/UserService.php';

class UserController
{
    public function __construct(private readonly UserService $userService) {}

    //POST /api/users.php?action=register
    public function register(): void
    {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        try {
            $userId = $this->userService->register($username, $email, $password);
            $user   = $this->userService->getCurrentUser()
                ?? $this->userService->login($email, $password);

            $this->userService->startSession($user);

            $this->jsonSuccess([
                'message' => 'Cont creat cu succes!',
                'user'    => $user->toArray(),
            ]);

        } catch (InvalidArgumentException $e) {
            $this->jsonError($e->getMessage(), 422);
        } catch (Exception $e) {
            $this->jsonError('Internal Error', 500);
        }
    }

    //POST /api/users.php?action=login
    public function login(): void
    {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        try {
            $user = $this->userService->login($email, $password);

            if ($user === null) {
                $this->jsonError('Email sau parolă incorectă.', 401);
                return;
            }

            $this->userService->startSession($user);

            $this->jsonSuccess([
                'message'  => 'Autentificare reușită!',
                'user'     => $user->toArray(),
                'redirect' => '/pages/home.php',
            ]);

        } catch (Exception $e) {
            $this->jsonError('Eroare internă. Încearcă din nou.', 500);
        }
    }

    //POST /api/users.php?action=logout
    public function logout(): void
    {
        $this->userService->logout();
        $this->jsonSuccess(['redirect' => '/pages/auth.php']);
    }


    //GET /api/users.php?action=me
    public function me(): void
    {
        $user = $this->userService->getCurrentUser();

        if ($user === null) {
            $this->jsonError('Neautentificat.', 401);
            return;
        }

        $this->jsonSuccess(['user' => $user->toArray()]);
    }

    private function jsonSuccess(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, ...$data]);
        exit;
    }

    private function jsonError(string $message, int $status = 400): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    }
}