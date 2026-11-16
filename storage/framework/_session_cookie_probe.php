<?php

require __DIR__.'/../../../vendor/autoload.php';
$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$lifetime = 525600;
$expire = Illuminate\Support\Carbon::now()->addSeconds(60 * $lifetime)->getTimestamp();
echo 'xsrf expire ts='.$expire.PHP_EOL;
echo 'xsrf date='.date('c', $expire).PHP_EOL;

$sessionExpire = Illuminate\Support\Carbon::now()->addSeconds($lifetime * 60);
echo 'session cookie expire='.$sessionExpire->toIso8601String().PHP_EOL;

$c = new Symfony\Component\HttpFoundation\Cookie(
    'XSRF-TOKEN',
    'tok',
    $expire,
    '/',
    null,
    null,
    false,
    false,
    'lax'
);
echo 'max-age='.$c->getMaxAge().PHP_EOL;
echo 'expires='.$c->getExpiresTime().PHP_EOL;
echo (string) $c.PHP_EOL;

// Simulate full HTTP login CSRF with CSRF enabled
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Ensure a demo tenant exists for routing
$slug = 'demo';
$tenant = App\Models\Tenant::query()->where('slug', $slug)->first();
if (! $tenant) {
    echo "NO_TENANT\n";
    exit(1);
}

$get = Illuminate\Http\Request::create('/'.$slug.'/login', 'GET');
$getResponse = $kernel->handle($get);
echo 'GET status='.$getResponse->getStatusCode().PHP_EOL;
$cookies = $getResponse->headers->getCookies();
foreach ($cookies as $cookie) {
    echo 'SET '.$cookie->getName().' path='.$cookie->getPath().' domain='.var_export($cookie->getDomain(), true).' secure='.var_export($cookie->isSecure(), true).' expires='.$cookie->getExpiresTime().' maxage='.$cookie->getMaxAge().PHP_EOL;
}

// Extract session id + xsrf from response cookies
$sessionName = null;
$sessionId = null;
$xsrf = null;
foreach ($cookies as $cookie) {
    if (str_ends_with($cookie->getName(), '_session')) {
        $sessionName = $cookie->getName();
        $sessionId = $cookie->getValue();
    }
    if ($cookie->getName() === 'XSRF-TOKEN') {
        $xsrf = $cookie->getValue();
    }
}
echo 'sessionName='.$sessionName.PHP_EOL;
echo 'hasXsrf='.(is_string($xsrf) ? 'yes' : 'no').PHP_EOL;

// Decrypt session cookie value the way EncryptCookies does
$encrypter = $app->make('encrypter');
try {
    $rawSession = Illuminate\Cookie\CookieValuePrefix::remove(
        $encrypter->decrypt(urldecode($sessionId), Illuminate\Cookie\Middleware\EncryptCookies::serialized($sessionName))
    );
    echo 'decryptedSessionId='.substr($rawSession, 0, 12).'...'.PHP_EOL;
} catch (Throwable $e) {
    echo 'decryptSessionFail='.$e->getMessage().PHP_EOL;
    $rawSession = $sessionId;
}

$tokenFromSession = null;
try {
    $payload = $app['db']->connection('tenant')->table('sessions')->where('id', $rawSession)->value('payload');
    if ($payload) {
        $data = unserialize(base64_decode($payload));
        $tokenFromSession = $data['_token'] ?? null;
        echo 'sessionToken='.(is_string($tokenFromSession) ? 'yes' : 'no').PHP_EOL;
    } else {
        echo "sessionRowMissing\n";
    }
} catch (Throwable $e) {
    echo 'sessionReadFail='.$e->getMessage().PHP_EOL;
}

$kernel->terminate($get, $getResponse);

// New kernel request for POST with cookies
$post = Illuminate\Http\Request::create('/'.$slug.'/login', 'POST', [
    'email' => 'admin@bidadmin.test',
    'password' => 'password',
], [
    $sessionName => $sessionId,
    'XSRF-TOKEN' => $xsrf,
], [], [
    'HTTP_X_XSRF_TOKEN' => $xsrf,
    'HTTP_ACCEPT' => 'text/html,application/xhtml+xml',
]);

$postResponse = $kernel->handle($post);
echo 'POST status='.$postResponse->getStatusCode().PHP_EOL;
echo 'POST location='.$postResponse->headers->get('Location').PHP_EOL;
if ($postResponse->getStatusCode() === 419) {
    echo "GOT_419\n";
    echo substr($postResponse->getContent(), 0, 500).PHP_EOL;
}
$kernel->terminate($post, $postResponse);
