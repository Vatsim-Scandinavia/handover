## OAuth2 Handover
Centralized Handover with OAuth2, created using `Laravel 6`.

## Setup and install
Just clone this repository and you're almost ready. First make sure you've installed [Composer](https://getcomposer.org) and [Node.js](https://nodejs.org/en/) on your computer.

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

Do not run any of the commands as root/sudo, unless explicitly listed!

1. Make sure you've the correct permissions to run some of the commands as local user. The `fixwebperm` can be used to achive that `alias fixwebperm="sudo chgrp -R www-data /var/www/*; sudo chmod -R g+rw /var/www/*; find /var/www/* -type d -print0 | sudo xargs -0 chmod g+s"`
2. Duplicate `.env.example` file into `.env` and make sure you're running correct mysql settings and app_url
3. In the project folder, run `composer install --no-dev` to install PHP dependecies
4. Run `npm install --production` to install front-end dependecies
5. Create app key `php artisan key:generate`
6. Migrate the database with `php artisan migrate`
7. Initialize OAuth2 components `php artisan passport:keys`
8. Add your client auth token with `php artisan passport:client`, skip with ENTER the assign to specific user, name the client something descriptive as it's shown to the user and add the callback URL.
9. Build the front end with `npm run prod`, this may take a while.


## Credentials

* Method: `Authorization Code`
* Client ID: Usually a short id number
* Client Secret: String of hash you generated earlier

* Authorization Endpoint: `/oauth/authorize`
* Token Endpoint: `/oauth/token`
* User Information Endpoint: `/api/user`

* Data load in user information equals to all fields in users database table.