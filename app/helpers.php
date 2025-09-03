\
    <?php
    use App\Database;

    function json_body(): array {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    function json_success(string $message = 'Tamam', $data = null): string {
        http_response_code(200);
        return json_encode(['success'=>true,'message'=>$message,'data'=>$data ?? new stdClass(),'errors'=>[]], JSON_UNESCAPED_UNICODE);
    }

    function json_error(string $message, int $code = 400, $data = null, array $errors = []): string {
        http_response_code($code);
        return json_encode(['success'=>false,'message'=>$message,'data'=>$data ?? new stdClass(),'errors'=>$errors], JSON_UNESCAPED_UNICODE);
    }

    function bearer_token(): ?string {
        $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['Authorization'] ?? '';
        if (preg_match('/Bearer\s+(.*)$/i', $hdr, $m)) return trim($m[1]);
        return null;
    }

    function require_auth(): array {
        $t = bearer_token();
        if (!$t) { echo json_error('Yetkisiz', 401); exit; }
        $p = jwt_decode($t);
        if (!$p) { echo json_error('Yetkisiz', 401); exit; }
        return [ 'uid' => (int)$p['uid'], 'role' => $p['role'] ?? 'user' ];
    }

    function require_admin(): void {
        $a = require_auth();
        if (($a['role'] ?? 'user') !== 'admin') { echo json_error('Yetkisiz', 401); exit; }
    }


function json_created(string $message = 'Tamam', $data = null, ?string $location = null): string {
    if ($location) header('Location: ' . $location);
    http_response_code(201);
    return json_encode(['success'=>true,'message'=>$message,'data'=>$data ?? new stdClass(),'errors'=>[]], JSON_UNESCAPED_UNICODE);
}

function log_event(string $level, string $message, array $context = []): void {
    try {
        $base = dirname(__DIR__); // project root
        $dir = $base . '/storage/logs';
        if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
        $entry = [
            'ts' => date('c'),
            'level' => $level,
            'msg' => $message,
            'ctx' => $context
        ];
        @file_put_contents($dir . '/app.log', json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
    } catch (\Throwable $e) {
        // swallow logging errors
    }
}
