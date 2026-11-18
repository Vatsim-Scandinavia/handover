# Installation

Handover ships as a pre-built container image.
We recommend running it with Docker, for example through [Portainer](https://www.portainer.io/).

## Prerequisites

### Docker (recommended)

- A Docker environment to run containers.
- A MySQL database. The image does not include one.
- A reverse proxy, if you host more than one website on the same server.
- An OAuth client from [VATSIM Connect](integrations/vatsim-connect.md).

### Manual (unsupported)

- A web server that can host PHP websites, such as Apache or Nginx.
- A MySQL database.
- PHP and extensions that meet the [Laravel 13 requirements](https://laravel.com/docs/13.x/deployment). Handover needs PHP 8.3 or newer.
- You build the Composer and npm dependencies yourself, set up the scheduler, and clear caches on every update.

The commands on this site assume your container is named `handover`.
Replace the name if yours differs.

## Setup

1. Pull the `%%image_tag%%` image.
2. Create the MySQL database.
3. Create a volume for the storage folder.
   It holds sessions and the OAuth signing keys, so they survive when you recreate the container.

    ```sh
    docker volume create handover_storage
    ```

4. Configure the [environment variables](reference/environment-variables.md).
   The repository's `docker-compose.yaml` is a good starting point.
5. Start the container with the volume bound to `/app/storage`.
   On the first start without an `APP_KEY`, the container generates an application key and the OAuth signing keys.
6. Read the generated application key and set it as the `APP_KEY` environment variable.
   Without a fixed key, users must log in again each time you recreate the container.

    ```sh
    docker exec -it handover php artisan key:get
    ```

    !!! warning "Setting `APP_KEY` before the first start"
        The container only generates the OAuth signing keys when `APP_KEY` is empty.
        If you set `APP_KEY` from the start, generate the keys yourself with `php artisan passport:keys`.

7. Create the database tables.

    ```sh
    docker exec -it --user www-data handover php artisan migrate
    ```

8. Add a crontab entry *outside* the container that runs the scheduler every minute.
   See [background jobs](reference/background-jobs.md) for what it runs.

    --8<-- "schedule-cron.md"

9. Point your reverse proxy at port 80 (HTTP) or 443 (HTTPS) in the container.
   The example `docker-compose.yaml` maps these to 8080 and 8443 on the host.
   The container creates a self-signed certificate for HTTPS on first start.
10. Make yourself an administrator. Log in once, then run:

    ```sh
    docker exec -it --user www-data handover php artisan groups:add-admin YOUR_CID
    ```

You can now [add OAuth clients](concepts/oauth-clients.md) and [customise the theme](setup/theme.md).

## Caching

The image enables OPcache with production settings.
PHP does not check files for changes, so restart the container after you change a file in it.

For development, set `opcache.validate_timestamps` to `1` in `/usr/local/etc/php/php.ini`.
PHP then picks up changed files on its own.

## Updating

Recreate the container with the new image, then apply database changes:

```sh
docker exec -it --user www-data handover php artisan migrate
```

Some releases need manual steps. Read the [upgrade guide](upgrade.md) before you update.
