<?php

return [
    // مفاتيح VAPID للإشعارات (ولّدها مرة واحدة وضعها في .env).
    // الإشعارات تُفعَّل تلقائيًا بمجرد ضبط المفتاح العام.
    'vapid_public' => env('VAPID_PUBLIC_KEY', ''),
    'vapid_private' => env('VAPID_PRIVATE_KEY', ''),
    'subject' => env('VAPID_SUBJECT', 'mailto:admin@banha.shop'),
];
