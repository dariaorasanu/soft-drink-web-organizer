<?php

require_once __DIR__ . '/../repositories/UserRepository.php';

class UserService
{
    public function __construct(private readonly UserRepositoryInterface $userRepository) {}

    public function register(string $username, string $email, string $password): int
    {
        if (empty(trim($username)) || empty(trim($email)) || empty($password)) {
            throw new InvalidArgumentException('Toate câmpurile sunt obligatorii.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Adresa de email nu este validă.');
        }

        if (strlen($password) < 8) {
            throw new InvalidArgumentException('Parola trebuie să aibă minim 8 caractere.');
        }

        if ($this->userRepository->findByEmail($email) !== null) {
            throw new InvalidArgumentException('Există deja un cont cu acest email.');
        }

        if ($this->userRepository->findByUsername($username) !== null) {
            throw new InvalidArgumentException('Username-ul este deja folosit.');
        }

        return $this->userRepository->create([
            'username'      => htmlspecialchars(trim($username), ENT_QUOTES, 'UTF-8'),
            'email'         => strtolower(trim($email)),
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'role'          => 'user',
        ]);
    }

    public function login(string $email, string $password): ?User
    {
        if (empty($email) || empty($password)) {
            return null;
        }
        $user = $this->userRepository->findByEmail(strtolower(trim($email)));
        if ($user === null) {
            return null;
        }
        if (!password_verify($password, $user->passwordHash)) {
            return null;
        }
        return $user;
    }

    public function startSession(User $user): void
    {
        $_SESSION['user_id']   = $user->id;
        $_SESSION['username']  = $user->username;
        $_SESSION['role']      = $user->role;
    }

    public function logout(): void
    {
        session_destroy();
        session_start();
        session_regenerate_id(true);
    }

    public function getCurrentUser(): ?User
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }
        return $this->userRepository->findById((int) $_SESSION['user_id']);
    }

    public function isLoggedIn(): bool
    {
        return !empty($_SESSION['user_id']);
    }
}