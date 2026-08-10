# Upgrading

Operational steps for moving Handover between versions. Run the commands below inside the application container.

---

## Before you upgrade

- Read the version-specific notes below first; some releases need manual steps.
- Back up at least the database, plus `.env` and the OAuth signing keys under `storage/` (`oauth-private.key` and `oauth-public.key`). The keys are not in the container image, and losing them invalidates every access token already issued.
- Rehearse on staging with a copy of production data whenever a release has breaking changes.
- Enable maintenance mode for the duration of the migration, then lift it once verified:
  ```sh
  php artisan down
  # ... perform the upgrade ...
  php artisan up
  ```
- Keep a rollback path: note the current version (or image tag) and retain the backup until the new version is confirmed healthy.
- Check requirements (PHP version and extensions) for the target release before starting.

---

## All versions

After deploying any new version, apply database changes and clear caches:

```sh
php artisan migrate
php artisan optimize:clear
```

Rebuild the frontend when its dependencies or assets changed:

```sh
npm ci && npm run build
```

---

## Upgrading to 4.0.0

This release upgrades the framework to Laravel 13 and Laravel Passport 13, and moves the frontend toolchain (Vite 8, jQuery 4, and others) to their latest majors. It requires PHP 8.3 or newer.

One manual step is required, and OAuth will break without it.

### OAuth client secrets are now hashed

Passport 13 hashes client secrets by default. Handover's existing clients were stored with plaintext secrets, so every third-party integration fails to authenticate at `/oauth/token` until the stored secrets are hashed. Run once, per environment:

```sh
php artisan passport:hash
```

Client owners do nothing: they keep sending their existing secret, and Passport hashes the incoming value to compare it. Clients created after the upgrade are hashed automatically.

> Never run this in CI or tests; it rewrites stored secrets.

### Steps

1. Work through the [pre-upgrade checklist](#before-you-upgrade) (backup, maintenance mode).
2. Deploy `4.0.0` and install dependencies:
   ```sh
   composer install --no-dev --optimize-autoloader
   npm ci && npm run build
   ```
3. Apply migrations and hash client secrets:
   ```sh
   php artisan migrate
   php artisan passport:hash
   ```
4. Clear caches:
   ```sh
   php artisan optimize:clear
   ```

### Why this is a major release

As an OAuth provider, Handover depends on Passport behaviour that changed in 13: it became headless (the authorization endpoint now needs a view bound) and switched client IDs to UUIDs, which the integer `oauth_clients` schema cannot store. Both are fixed within the release and need no action from you. Client-secret hashing above cannot be handled automatically because it rewrites stored data, which is what makes 4.0.0 a breaking release rather than a 3.x patch.
