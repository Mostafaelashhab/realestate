<?php

return [
    // ضع هنا معرّفات الأفلييت لما تتعاقد مع الشركاء (تظهر تلقائيًا في روابط "خدمات الوجهة").
    'booking_aid' => env('AFFILIATE_BOOKING_AID', ''),  // Booking.com affiliate id
    'gobus_ref' => env('AFFILIATE_GOBUS_REF', ''),      // Go Bus referral
    'enabled' => env('AFFILIATE_ENABLED', true),        // إظهار قسم خدمات الوجهة
];
