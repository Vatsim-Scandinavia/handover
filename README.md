## OAuth2 Handover
Centralized Handover with OAuth2, created using `Laravel 6`.

## Setup and install
Just clone this repository and you're almost ready. First make sure you've installed [Composer](https://getcomposer.org) and [Node.js](https://nodejs.org/en/) on your computer.

1. Duplicate `.env.example` file into `.env` and make sure you're running correct mysql settings
2. In the project folder, run `composer install` to install PHP dependecies and `npm install` (requires Node.js) to run Front-end dependecies.
3. Create app key `php artisan key:generate`
4. Migrate the database with `php artisan migrate`
5. Initialize OAuth2 components `php artisan passport:install` and `php artisan passport:keys` (for production)
5. Run `php artisan serve` to host the page at `localhost:8000`. Note: OAuth often requires a HTTPS host.