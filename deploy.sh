#!/usr/bin/env bash
# نشر قطارات مصر على الاستضافة المشتركة (Hostinger). شغّله من جذر المشروع عبر SSH.
set -e

echo "→ سحب آخر كود"
git pull origin main

echo "→ تثبيت الاعتماديات (بدون dev)"
composer install --no-dev --optimize-autoloader

echo "→ ترحيل قاعدة البيانات"
php artisan migrate --force

echo "→ مسح وإعادة بناء الكاش (لازم بعد كل نشر)"
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✓ تم النشر"
echo "تذكير: تأكد أن الكرون يشغّل: php artisan schedule:run كل دقيقة"
