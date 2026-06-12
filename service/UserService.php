<?php

require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/JWTService.php';
require_once __DIR__ . '/CookieService.php';

class UserService
{
    private JWTService    $jwt;
    private CookieService $cookie;

    public function __construct(private readonly UserRepositoryInterface $userRepository)
    {
        $this->jwt    = new JWTService(JWT_SECRET);
        $this->cookie = new CookieService();
    }


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
            'username'      => trim($username),
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
        $token = $this->jwt->encode($user->id, $user->role);
        $this->cookie->set($token);
    }


    public function logout(): void
    {
        $this->cookie->delete();
    }


    public function getCurrentUser(): ?User
    {
        $token = $this->cookie->get();
        if ($token === null) {
            return null;
        }
        try {
            $payload = $this->jwt->decode($token);
        } catch (RuntimeException $e) {
            $this->cookie->delete();
            return null;
        }
        return $this->userRepository->findById((int) $payload->user_id);
    }


    public function isLoggedIn(): bool
    {
        $token = $this->cookie->get();
        if ($token === null) {
            return false;
        }

        try {
            $this->jwt->decode($token);
            return true;
        } catch (RuntimeException $e) {
            return false;
        }
    }

    public function getCurrentRole(): ?string
    {
        $token = $this->cookie->get();
        if ($token === null) {
            return null;
        }

        try {
            $payload = $this->jwt->decode($token);
            return $payload->role ?? null;
        } catch (RuntimeException $e) {
            return null;
        }
    }
}