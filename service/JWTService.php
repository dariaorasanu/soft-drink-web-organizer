<?php

class JWTService
{
    private string $secret;
    private int $ttl;//cat o sa traiasca
    public function __construct(string $secret, int $ttlDays = 7)
    {
        $this->secret = $secret;
        $this->ttl= $ttlDays * 24 * 60 * 60;
    }

    //creem un jwt nou
    public function encode(int $userId, string $role): string
    {
        $header = $this->base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT',]));

        $payload = $this->base64UrlEncode(json_encode([
            'user_id' => $userId,
            'role' => $role,
            'iat'=> time(),
            'exp' => time() + $this->ttl,
        ]));

        $signature = $this->sign($header . '.' . $payload);
        return $header . '.' . $payload . '.' . $signature;
    }

    //verifica si extrage datele dintr un token
    public function decode(string $token): object
    {
        //impartim tokenul in cele trei parti
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new RuntimeException('Token invalid: format incorect.');
        }

        [$header, $payload, $signature] = $parts;
        $expectedSignature = $this->sign($header . '.' . $payload);

        if (!hash_equals($expectedSignature, $signature)) {
            throw new RuntimeException('Token invalid: semnătură incorectă.');
        }

        $data = json_decode($this->base64UrlDecode($payload));

        if ($data === null) {
            throw new RuntimeException('Token invalid: payload corupt.');
        }

        if (!isset($data->exp) || $data->exp < time()) {
            throw new RuntimeException('Token expirat.');
        }
        return $data;
    }


    private function sign(string $data): string
    {
        return $this->base64UrlEncode(
            hex2bin(hash_hmac('sha256', $data, $this->secret))
        );
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder !== 0) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}