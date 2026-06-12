<?php

class CookieService
{
    private const COOKIE_NAME = 'sor_token';

    private int $ttl;

    public function __construct(int $ttlDays = 7)
    {
        $this->ttl = $ttlDays * 24 * 60 * 60;
    }


    public function set(string $token): void
    {
        setcookie(self::COOKIE_NAME, $token, [
            'expires'  => time() + $this->ttl,
            'path'     => '/',
            'httponly' => true,   //protectie xss
            'samesite' => 'Lax',  //protecție csfr
             'secure' => true,
        ]);
    }

    public function get(): ?string
    {
        $token = $_COOKIE[self::COOKIE_NAME] ?? null;
        if (empty($token)) {
            return null;
        }
        return $token;
    }

    public function delete(): void
    {
        setcookie(self::COOKIE_NAME, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}