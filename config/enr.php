<?php

return [
    // إيميل المشرف الوحيد الي يقدر يفتح لوحة الإدارة وأدواتها.
    'admin_email' => env('ADMIN_EMAIL', 'mostafa4661a@gmail.com'),

    // نقطة بحث الرحلات الرسمية (تُنادى من متصفح المشرف لا من السيرفر).
    'search_url' => env('ENR_SEARCH_URL', 'https://obs.enr.gov.eg/api/v1/tickets/search'),

    // إظهار ميزة "المقاعد المتاحة" — مخفية مؤقتًا لحين الحصول على إذن الهيئة.
    'show_seats' => env('ENR_SHOW_SEATS', false),
];
