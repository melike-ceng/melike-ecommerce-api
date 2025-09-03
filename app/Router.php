\
    <?php
    namespace App;

    class Router {
        private array $routes = [];

        public function register(string $method, string $pattern, callable $handler): void {
            $this->routes[] = [$method, $pattern, $handler];
        }

        public function dispatch(): void {
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
            foreach ($this->routes as [$m, $pat, $h]) {
                if ($m !== $method) continue;
                if (preg_match($pat, $uri, $matches)) {
                    try {
                        $res = $h($matches);
                        if ($res !== null) echo $res; // handler zaten json yazabilir
                    } catch (\Throwable $e) {
                        http_response_code(500);
                        if (function_exists('\log_event')) { \log_event('error', 'router.exception', ['err'=>$e->getMessage()]); }
                        echo json_encode(['success'=>false,'message'=>'Sunucu hatası','data'=>[],'errors'=>['err'=>$e->getMessage()]], JSON_UNESCAPED_UNICODE);
                    }
                    return;
                }
            }
            http_response_code(404);
            echo json_encode(['success'=>false,'message'=>'Bulunamadı','data'=>[],'errors'=>[]], JSON_UNESCAPED_UNICODE);
        }
    }
