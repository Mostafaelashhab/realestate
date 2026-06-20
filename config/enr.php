<?php

return [
    // رمز سري للوصول لصفحة المزامنة (احفظه في .env). لو فاضي، الصفحة مقفولة.
    'sync_token' => env('ENR_SYNC_TOKEN'),

    // نقطة بحث الرحلات الرسمية (تُنادى من متصفح المشرف لا من السيرفر).
    'search_url' => env('ENR_SEARCH_URL', 'https://obs.enr.gov.eg/api/v1/tickets/search'),
];
