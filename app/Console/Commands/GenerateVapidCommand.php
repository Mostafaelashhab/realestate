<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/** يولّد زوج مفاتيح VAPID لإشعارات الويب (EC P-256) لوضعهما في .env. */
class GenerateVapidCommand extends Command
{
    protected $signature = 'push:vapid';

    protected $description = 'توليد مفاتيح VAPID لإشعارات الويب';

    public function handle(): int
    {
        $res = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        if (! $res) {
            $this->error('تعذّر توليد المفاتيح — تأكّد من تفعيل امتداد openssl.');

            return self::FAILURE;
        }

        $ec = openssl_pkey_get_details($res)['ec'];
        $b64u = fn (string $b) => rtrim(strtr(base64_encode($b), '+/', '-_'), '=');

        $this->info('أضف هذه القيم إلى .env:');
        $this->line('VAPID_PUBLIC_KEY='.$b64u("\x04".$ec['x'].$ec['y']));
        $this->line('VAPID_PRIVATE_KEY='.$b64u($ec['d']));

        return self::SUCCESS;
    }
}
