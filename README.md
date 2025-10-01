# Petstore CRUD (Laravel)

Aplikacja Laravel do integracji z publicznym API **Swagger Petstore** (`/pet`): lista, dodawanie, edycja, usuwanie, podgląd szczegółów oraz **wyszukiwarka AJAX** (po nazwie/ID) + filtr statusu (available/pending/sold/Wszystkie).

## Stos i wersje
- Laravel (12.x)
- PHP 8.2+
- Composer
- Publiczne API: `https://petstore.swagger.io/v2`

## Funkcje
- CRUD `/pet` (GET/POST/PUT/DELETE) przez `App\Services\PetstoreClient`
- Lista z filtrem statusu + **AJAX search** (ID/nazwa)
- Odporność na niestabilność Petstore:
  - retry/timeout w kliencie
  - przyjazna obsługa błędów (bez „error page”)
  - sesyjny cache ostatnio tworzonych/edytowanych rekordów (gdy API „gubi” rekordy)
- Bezpieczne widoki (brakujące pola z API nie wysadzają UI)

## Szybki start (dev)
```bash
git clone https://github.com/<twoj_user>/petstore-laravel.git
cd petstore-laravel
composer install
cp .env.example .env
php artisan key:generate
php artisan serve
# http://127.0.0.1:8000
