## Handover
Centralised Handover with OAuth2, using `Laravel 9`. Created by [Daniel L.](https://github.com/blt950) (1352906) and [Matan B.](https://github.com/MatanBudimir) (1375048). An extra special thanks to VATSIM UK's open source core system which helped us on the right track with this one.

## Prerequisites
- An environment that can host PHP websites, such as Apache, Ngnix or similar.
- [Laravel 9 Requirements](https://laravel.com/docs/9.x/deployment#server-requirements)
- Installed SSL Certificate to serve this service through HTTPS

## Setup and install
Just clone this repository and you're almost ready. First, make sure you've installed [Composer](https://getcomposer.org) and [Node.js](https://nodejs.org/en/) in your environment.

1. Run `./deploy init` to setup the required files
2. Configure the .env file accordingly, everything from top down to and including VATSIM OAuth should be configured, rest is optional.
3. [Setup Cron in your environment](https://laravel.com/docs/9.x/scheduling#running-the-scheduler) 
4. Run `npm run dev` in development environment or `npm run dev` in production to build front-end assets
5. Run `php artisan serve` to host the page at `localhost:8000` in development environment. Note: It's tricky to host this locally due to HTTPS requirement, so it might in some cases be easier to test in a staging environment with a proper domain or an docker container.
6. Make sure your PHP environment allows running `curl_multi_exec` function

## Adding OAuth Clients
Add your client auth token with `php artisan passport:client`, skip with ENTER when asked to assign to specific user. Name the client something descriptive as it's shown to the user and add the callback URL. The generated ID and Secret can now be used from other OAuth2 services to connect to Handover.

## Updating Data Protection Policy
1. Update the date and url to PDF in `.env`
2. If needed, update the `dpp.blade.php` view file regarding the simplified version
3. Run this SQL query `UPDATE users SET accepted_privacy = 0` in the correct environment
4. Delete the file(s) in `/storage/framework/sessions` to log everyone out, so they'll be forced to accept again on next login

## Present automation
The only job the cron has automated today is daily member check and pull freshest data from VATSIM

## Credentials

* Method: `Authorization Code`
* Client ID: Usually a short id number
* Client Secret: String of hash you generated earlier

* Authorization Endpoint: `/oauth/authorize`
* Token Endpoint: `/oauth/token`
* User Information Endpoint: `/api/user`
* Login Callback: `/login`

## Contribution and conventions
Contributions are much appreciated to help everyone move this service forward with fixes and functionalities. We recommend you to fork this repository here on GitHub so you can easily create pull requests back to the main project.

In order to keep a collaborative project in the same style and understandable, it's important to follow some conventions:

##### GitHub Branches
We name branches with `topic/name-here` including fixes and features, for instance `topic/new-api` or `topic/mentor-mail-fix`

##### Models/SQL
* MySQL tables are named in plural e.g `training_reports`, not `training_report`
* Models are named in singular e.g. `Training`, not `Trainings`
* Models names don't have any specific suffix or prefix
* Models are per Laravel 8 located in root of `App/Models` folder.

##### Controllers
* Controllers are suffixed with `Controller`, for instance `TrainingController`
* Controllers are named in singular e.g. `TrainingController`, not `TrainingsController`
* The controllers should mainly consist of the methods of "7 restful controller actions" [Check out this video](https://laracasts.com/series/laravel-6-from-scratch/episodes/21?autoplay=true)

##### Other
* We name our views with blade suffix for clarity, like `header.blade.php`
* For more in-depth conventions which we try to follow, check out [Laravel best practices](https://www.laravelbestpractices.com)
* We tab with 4 spaces for increased readability
* The service language is UK English