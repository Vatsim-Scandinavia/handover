## Handover
Centralised Handover with OAuth2, using `Laravel 9`. Created by [Daniel L.](https://github.com/blt950) (1352906) and [Matan B.](https://github.com/MatanBudimir) (1375048). An extra special thanks to VATSIM UK's open source core system which helped us on the right track with this one.

## Prerequisites

### Docker (Recommended)
- A Docker environment to deploy containers. We recommend [Portainer](https://www.portainer.io/).
- MySQL database to store data.
- Preferably a reverse proxy setup if you plan to host more than one website on the same server.

In the instructions where we use `docker exec`, we assume your container is named `handover`. If you have named it differently, please replace this.

### Manual (Unsupported)
If you don't want to use Docker, you need:
- An environment that can host PHP websites, such as Apache, Ngnix or similar.
- MySQL database to store data.
- Comply with [Laravel 9 Requirements](https://laravel.com/docs/9.x/deployment#server-requirements)
- Manually build the composer, npm and setting up cron jobs and clearing all caches on updates.

## Setup and install

To setup your Docker instance simply follow these steps:
1. Pull the `ghcr.io/vatsim-scandinavia/handover:v3` Docker image
2. Setup your MySQL database (not included in Docker image)
3. Configure the environment variables as described in the [CONFIGURE.md](CONFIGURE.md#environment)
4. Start the container in the background.
5. Setup the database.
   ```sh
   docker exec -it handover php artisan migrate
   ```
6. To ensure that users will not need to log in after each time you re-deploy or upgrade the container, you need to create and store an application key in your environment and setup a shared volume. 
   ```sh
   docker exec -it handover php artisan key:get
   docker volume create handover_sessions
   ```
   Copy the key and set it as the `APP_KEY` environment variable in your Docker configuration and bind the volume when creating the container with `handover_sessions:/app/storage/framework/sessions`.
7. Setup a crontab _outside_ the container to run `* * * * * docker exec --user www-data -i handover php artisan schedule:run >/dev/null` every minute. This patches into the container and runs the required cronjobs.
8. Bind the 8080 (HTTP) and/or 8443 (HTTPS) port to your reverse proxy or similar.

## Adding OAuth Clients
Add your client auth token with 
```sh
docker exec -it handover php artisan passport:client
```
Skip with ENTER when asked to assign to specific user. Name the client something descriptive as it's shown to the user and add the callback URL. The generated ID and Secret can now be used from other OAuth2 services to connect to Handover.

## Updating Data Protection Policy (TODO: Docker command)
When you update your DPP, you should make all users explicitly accept the new policy again. To do this, follow these steps:

1. Update the date and url to DPP in your environment variables
2. If needed, update the `dpp.blade.php` view file regarding the simplified version
3. Run this SQL query `UPDATE users SET accepted_privacy = 0` in the correct environment
4. Delete the file(s) in `/storage/framework/sessions` to log everyone out, so they'll be forced to accept again on next login

## Present automation
The only job the cron has automated today is daily member check and pull freshest data from VATSIM

## OAuth Credentials

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

### GitHub Branches
We name branches with `feat/name-here` or `fix/name-here` for features or fixes, for example `feat/new-api` or `fix/login-bug`