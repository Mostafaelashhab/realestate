<?php
use App\Models\User;
$u = User::first();
auth()->login($u);
$req = Illuminate\Http\Request::create('/شكاوى', 'POST', [
    'body' => 'تجربة بوست من الاختبار',
    'category' => 'general',
]);
$req->headers->set('Accept', 'application/json');
$req->setUserResolver(fn () => $u);
$resp = app()->handle($req);
echo 'status: '.$resp->getStatusCode().PHP_EOL;
$c = $resp->getContent();
if (preg_match('/<title>(.*?)<\/title>/s', $c, $m)) {
    echo 'title: '.trim(strip_tags($m[1])).PHP_EOL;
}
foreach (['exception','Exception','TokenMismatch','ValidationException','Bad Request'] as $needle) {
    if (stripos($c, $needle) !== false) echo "found: $needle".PHP_EOL;
}
