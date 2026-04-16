# Laravel 13 Specific Rules

- Gunakan PHP 8.4 features: typed properties, constructor property promotion, match expression, readonly properties.
- Selalu gunakan FormRequest untuk validation.
- Gunakan Policy untuk authorization.
- Model di app/Models, Controller di app/Http/Controllers, Livewire di app/Livewire.
- Migration: gunakan foreignIdFor() dan constrained().
- Gunakan Laravel 13 JSON:API Resource jika butuh API.
- Setelah edit, selalu jalankan: php artisan route:clear && php artisan config:clear && php artisan optimize:clear
