<?php

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$lifetime = (int) config('session.lifetime');
echo "config lifetime={$lifetime}\n";

$expire = Illuminate\Support\Carbon::now()->addSeconds(60 * $lifetime)->getTimestamp();
$c = new Symfony\Component\HttpFoundation\Cookie('XSRF-TOKEN', 'tok', $expire, '/', null, null, false, false, 'lax');
echo 'max-age='.$c->getMaxAge()."\n";
echo 'set-cookie='.(string) $c."\n";

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$slug = App\Models\Tenant::query()->where('status', 'active')->value('slug') ?? 'demo';
echo "slug={$slug}\n";

$get = Illuminate\Http\Request::create('/'.$slug.'/login', 'GET');
$getResponse = $kernel->handle($get);
echo 'GET status='.$getResponse->getStatusCode()."\n";

$sessionName = null;
$sessionId = null;
$xsrf = null;
foreach ($getResponse->headers->getCookies() as $cookie) {
    echo 'SET '.$cookie->getName().' secure='.var_export($cookie->isSecure(), true).' domain='.var_export($cookie->getDomain(), true).' maxage='.$cookie->getMaxAge()."\n";
    if (str_contains($cookie->getName(), '_session')) {
        $sessionName = $cookie->getName();
        $sessionId = $cookie->getValue();
    }
    if ($cookie->getName() === 'XSRF-TOKEN') {
        $xsrf = $cookie->getValue();
    }
}
$kernel->terminate($get, $getResponse);

echo "sessionCookie={$sessionName}\n";

$post = Illuminate\Http\Request::create('/'.$slug.'/login', 'POST', [
    'email' => 'nosuch@example.com',
    'password' => 'password',
], array_filter([
    $sessionName => $sessionId,
    'XSRF-TOKEN' => $xsrf,
]), [], [
    'HTTP_X_XSRF_TOKEN' => (string) $xsrf,
    'HTTP_ACCEPT' => 'text/html, application/xhtml+xml',
    'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
]);

$postResponse = $kernel->handle($post);
echo 'POST status='.$postResponse->getStatusCode()."\n";
echo 'POST location='.(string) $postResponse->headers->get('Location')."\n";
if ($postResponse->getStatusCode() === 419) {
    echo "GOT_419\n";
}
$kernel->terminate($post, $postResponse);
