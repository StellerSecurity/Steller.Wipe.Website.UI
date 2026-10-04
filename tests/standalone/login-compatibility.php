<?php
// Runs the real controllers against memory-only doubles. No Laravel boot,
// credentials, database, HTTP client or production endpoint is loaded.
namespace Illuminate\Http {
    class RedirectResponse {
        public array $flash = [];
        public function __construct(public string $target) {}
        public function with($key, $value): self { $this->flash[$key] = $value; return $this; }
        public function route($name): self { $this->target = $name; return $this; }
    }
    class Session {
        public array $data = [];
        public function regenerate($destroy): void {}
        public function regenerateToken(): void {}
        public function put($values): void { $this->data = [...$this->data, ...$values]; }
        public function get($key, $default = null): mixed { return $this->data[$key] ?? $default; }
        public function pull($key, $default = null): mixed { $v = $this->get($key, $default); unset($this->data[$key]); return $v; }
        public function invalidate(): void { $this->data = []; }
    }
    class Request {
        public Session $session;
        public function __construct(public array $body = []) { $this->session = new Session; }
        public function input($name): mixed { return $this->body[$name] ?? null; }
        public function validate($rules): array {
            foreach ($rules as $name => $list) {
                $value = $this->input($name);
                foreach ($list as $rule) {
                    if (str_starts_with($rule, 'in:') && !in_array($value, explode(',', substr($rule, 3)), true)) throw new \RuntimeException('Invalid input');
                    if (str_starts_with($rule, 'max:') && is_string($value) && strlen($value) > (int) substr($rule, 4)) throw new \RuntimeException('Invalid input');
                }
            }
            return $this->body;
        }
        public function session(): Session { return $this->session; }
    }
}
namespace Illuminate\View { class View { public function __construct(public string $name, public array $data = []) {} } }
namespace Illuminate\Support { class Str { public static function random($length): string { return str_repeat('a', $length); } } }
namespace App\Http\Controllers { class Controller {} }
namespace App\Services {
    class WipeService {
        public array $calls = [];
        public function result($values): object { return new class($values) {
            public function __construct(private array $values) {}
            public function successful(): bool { return true; }
            public function object(): object { return (object) $this->values; }
        }; }
        public function auth($username, $password): object { $this->calls[] = ['auth', $username, $password]; return $this->result(['auth_token' => 'synthetic-session']); }
        public function findByToken($token): object { $this->calls[] = ['findByToken', $token]; return $this->result(['id' => 'synthetic-device', 'status' => 1, 'auth_token' => 'synthetic-session']); }
        public function updateStatus($id, $status, $reason): object { $this->calls[] = ['updateStatus', $id, $status, $reason]; return $this->result([]); }
    }
}
namespace {
    use App\Services\WipeService;
    use App\Http\Controllers\LoginController;
    use App\Http\Controllers\DashboardController;
    use Illuminate\Http\Request;
    use Illuminate\Http\RedirectResponse;
    function back(): RedirectResponse { return new RedirectResponse('back'); }
    function redirect(): RedirectResponse { return new RedirectResponse(''); }
    function view($name, $values = []): \Illuminate\View\View { return new \Illuminate\View\View($name, $values); }
    function config($name, $default = null): mixed { return $default; }
    function abort($status): never { throw new \RuntimeException((string) $status); }
    function check($condition, $message): void { if (!$condition) throw new \RuntimeException($message); }
    require __DIR__.'/../../app/Http/Controllers/LoginController.php';
    require __DIR__.'/../../app/Http/Controllers/DashboardController.php';
    require __DIR__.'/../../app/WipeStatus.php';
    require __DIR__.'/../../app/WipedBy.php';

    foreach (['old-token', str_repeat('x', 512), ' old-token '] as $token) {
        $service = new WipeService;
        $request = new Request(['method' => '1', 'token' => $token]);
        $result = (new LoginController($service))->authenticate($request);
        check($result->target === 'dashboard', 'Legacy redirect changed');
        check($request->session->data['auth_token'] === 'synthetic-session', 'Legacy session changed');
        check($service->calls === [['findByToken', trim($token)]], 'Legacy lookup changed');
    }
    $service = new WipeService;
    $request = new Request(['method' => '0', 'username' => ' test-user ', 'password' => ' test-password ']);
    (new LoginController($service))->authenticate($request);
    check($service->calls === [['auth', 'test-user', ' test-password ']], 'Username/password behavior changed');

    foreach (['spw2.synthetic', 'spw2.'.str_repeat('A', 5500), 'spw3.synthetic', 'SPW2.synthetic', ' spw2.synthetic', 'spw2'] as $token) {
        $service = new WipeService;
        $request = new Request(['method' => '1', 'token' => $token]);
        $result = (new LoginController($service))->authenticate($request);
        check($result->target === 'back', 'Reserved token accepted');
        check($service->calls === [], 'Private token forwarded to legacy backend');
        check($request->session->data === [], 'Private token saved to session');
        check(!str_contains(json_encode($result->flash), $token), 'Private token reflected');
    }

    $service = new WipeService;
    $request = new Request;
    $request->session->data = ['auth_token' => 'synthetic-session', 'wipe_authenticated_at' => time()];
    $dashboard = new DashboardController($service);
    $dashboard->show($request);
    $page = $dashboard->confirmWipe($request);
    check(array_column($service->calls, 0) === ['findByToken', 'findByToken'], 'Read-only pages changed wipe status');
    $request->body = ['action_token' => $page->data['action_token'], 'confirmation' => 'WIPE'];
    $dashboard->wipe($request);
    check(end($service->calls) === ['updateStatus', 'synthetic-device', \App\WipeStatus::WIPING->value, \App\WipedBy::WEBSITE->value], 'Legacy wipe contract changed');
    $count = count($service->calls);
    try { $dashboard->wipe($request); throw new \RuntimeException('Replay accepted'); }
    catch (\RuntimeException $e) { check($e->getMessage() === '419', 'Replay not rejected'); }
    check(count($service->calls) === $count, 'Replay touched the legacy API');
    echo "PASS: old token login, username login, confirmation and one-time legacy wipe unchanged; signed/unknown tokens never forwarded or stored. Memory doubles only.\n";
}
