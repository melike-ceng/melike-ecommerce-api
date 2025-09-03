\
    <?php
    use App\Database;

    function base64url_encode(string $data): string { return rtrim(strtr(base64_encode($data), '+/', '-_'), '='); }
    function base64url_decode(string $data): string { return base64_decode(strtr($data, '-_', '+/')); }

    function jwt_encode(array $payload): string {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $now = time();
        $payload = array_merge($payload, [
            'iss' => JWT_ISSUER,
            'iat' => $now,
            'exp' => $now + JWT_TTL,
        ]);
        $h = base64url_encode(json_encode($header));
        $p = base64url_encode(json_encode($payload));
        $sig = hash_hmac('sha256', "$h.$p", JWT_SECRET, true);
        $s = base64url_encode($sig);
        return "$h.$p.$s";
    }

    function jwt_decode(string $jwt): ?array {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) return null;
        [$h, $p, $s] = $parts;
        $calc = base64url_encode(hash_hmac('sha256', "$h.$p", JWT_SECRET, true));
        if (!hash_equals($calc, $s)) return null;
        $payload = json_decode(base64url_decode($p), true);
        if (!is_array($payload)) return null;
        if (($payload['iss'] ?? '') !== JWT_ISSUER) return null;
        if (($payload['exp'] ?? 0) < time()) return null;
        return $payload;
    }
