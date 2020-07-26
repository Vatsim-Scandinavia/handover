## OAuth2 Handover
Centralized Handover with OAuth2, created using `Laravel 6`. A extra special thanks to VATSIM UK's open source core system which helped us on the right track with this one.

## Setup and install
Just clone this repository and you're almost ready. First make sure you've installed [Composer](https://getcomposer.org) and [Node.js](https://nodejs.org/en/) in your environment.

### Development

1. Duplicate `.env.example` file into `.env` and make sure you're running correct mysql settings
2. In the project folder, run `composer install` to install PHP dependecies
3. Run `npm install` to install front-end dependecies
4. Create app key `php artisan key:generate`
5. Migrate the database with `php artisan migrate`
6. Initialize OAuth2 components `php artisan passport:keys`
7. Add your client auth token with `php artisan passport:client`, skip with ENTER the assign to specific user, name the client something descriptive as it's shown to the user and add the callback URL.
8. Build the front end with `npm run dev`, this may take a while. To watch changes while developing in the front-end code, you may run `npm run watch`
9. Run `php artisan serve` to host the page at `localhost:8000`. Note: OAuth often requires a HTTPS host.


### Production

1. Duplicate `.env.example` file into `.env` and make sure you're running correct mysql settings and app_url
2. In the project folder, run `composer install --no-dev --optimize-autoloader` to install PHP dependecies
3. Run `npm install --production` to install front-end dependecies
4. Create app key `php artisan key:generate`
5. Migrate the database with `php artisan migrate`
6. Initialize OAuth2 components `php artisan passport:keys`
7. Add your client auth token with `php artisan passport:client`, skip with ENTER the assign to specific user, name the client something descriptive as it's shown to the user and add the callback URL.
8. Build the front end with `npm run prod`, this may take a while.


## Updating Data Protection Policy
1. Update the date and url to PDF in `.env`
2. If needed, update the `dpp.blade.php` view file regarding the simplified version
3. Run this SQL query `UPDATE users SET accepted_privacy = 0` in the correct environment
4. Delete the file(s) in `/storage/framework/sessions` to log everyone out, so they'll be forced to accept again on next login


## Credentials

* Method: `Authorization Code`
* Client ID: Usually a short id number
* Client Secret: String of hash you generated earlier

* Authorization Endpoint: `/oauth/authorize`
* Token Endpoint: `/oauth/token`
* User Information Endpoint: `/api/user`
* Login Callback: `/login`
