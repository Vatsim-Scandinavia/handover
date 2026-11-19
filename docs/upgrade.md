---
icon: material/cog-refresh
---

# Upgrading

These are the operational steps for moving Handover between versions.
Always read the section for your target version first. Some releases need manual steps.

--8<-- "exec-in-container.md"

## Before you upgrade

- Back up the database, plus the storage volume. The volume holds the OAuth signing keys (`oauth-private.key` and `oauth-public.key`). Losing them invalidates every access token already issued.
- Rehearse on staging with a copy of production data when a release has breaking changes.
- Note the image tag you run now, and keep the backup until the new version is confirmed healthy.
- Put Handover in maintenance mode for the duration of the upgrade. The flag lives on the storage volume, so it survives recreating the container:

    ```sh
    php artisan down
    # ... perform the upgrade ...
    php artisan up
    ```

## All versions

Pull the new image and recreate the container.
The image ships with Composer dependencies installed and frontend assets built, so you do not build anything yourself.

Then apply database changes and clear caches:

```sh
php artisan migrate --force
php artisan optimize:clear
```

If you run a [custom theme](setup/theme.md), run the theme build again, because recreating the container resets it.

!!! info "Why `--force`"
    In production, `migrate` asks for confirmation.
    Without an interactive terminal it answers no and does nothing.
    `--force` skips the prompt.

## Upgrading from 3.x to 4.0.0 { #upgrading-to-400 }

This release moves Handover to Laravel 13 and Laravel Passport 13, and the frontend toolchain to Vite 8 and jQuery 4.
It needs one manual step on the database: hashing the OAuth client secrets.

!!! danger "OAuth breaks until you hash the client secrets"
    Every integration fails to get a token at `/oauth/token` until you run `php artisan passport:hash`.
    Members cannot log in to any connected service in the meantime.
    Plan the upgrade so you run it straight after the migration.

!!! warning "Access tokens now expire after one hour"
    After the upgrade, access tokens expire after one hour and refresh tokens expire after 30 days.
    If an integration uses an access token for more than an hour, it must use its refresh token to get a new one.
    If an integration does not refresh within 30 days, the member must sign in again.
    For details, see [token lifetimes are shorter](#token-lifetimes-are-shorter).

### Upgrade steps

1. Back up the database. This is not optional for this release: hashing the secrets cannot be undone, and a [rollback](#rolling-back-to-3x) needs the backup.
2. Enable maintenance mode in the running 3.x container:

    ```sh
    php artisan down
    ```

3. Change the image tag from `:v3` to `:v4`, pull it, and recreate the container.
   The floating `:v3` tag never moves to 4.0.0, so you must change it.
4. Apply the database changes. This adds the `group_hierarchy` table for [nested groups](groups.md#nested-groups-transitive-membership) and shortens the lifetime of existing refresh tokens:

    ```sh
    php artisan migrate --force
    ```

5. Hash the OAuth client secrets. Run this once per environment:

    ```sh
    php artisan passport:hash --force
    ```

    Like `migrate`, the command asks for confirmation and answers no without an interactive terminal. `--force` skips the prompt.
    Running it twice is safe: it skips secrets that are already hashed.

6. Clear the caches:

    ```sh
    php artisan optimize:clear
    ```

7. If you run a [custom theme](setup/theme.md), run the theme build again.
8. Lift maintenance mode:

    ```sh
    php artisan up
    ```

9. Log in to a connected service through Handover. This confirms that the token exchange works with hashed secrets.

!!! warning
    Never run `passport:hash` in CI or tests. It rewrites stored secrets.

### Rolling back to 3.x

Once you have run `passport:hash`, a 3.x container cannot verify any client secret, because 3.x compares secrets in plain text.
Switching the image back is not enough.

1. Switch the image tag back to the one you noted before the upgrade, and recreate the container.
2. Restore the database backup you took in step 1.
3. Clear the caches with `php artisan optimize:clear`.

The `group_hierarchy` table from 4.0.0 does no harm to 3.x if you keep it, but the restore removes it anyway.

### What changed

#### Client secrets are hashed

Passport 13 hashes client secrets by default.
3.x stored them in plain text, which is why the manual `passport:hash` step exists.

Client owners change nothing.
They keep sending their existing secret, and Passport hashes the incoming value to compare it.
Clients you create after the upgrade are hashed from the start.
Handover shows a new client's secret only once, so copy it when you create the client.

#### OAuth clients are managed in the admin UI

Administrators now create, edit and delete clients, and regenerate their secrets, under **OAuth Clients** in the admin header.
See [OAuth clients](concepts/oauth-clients.md).

Passport's JSON routes are gone: `/oauth/clients`, `/oauth/scopes`, `/oauth/tokens` and `/oauth/personal-access-tokens`.
If you scripted against them, use the admin UI or the [artisan commands](reference/commands.md) instead.
The routes your integrations use, `/oauth/authorize`, `/oauth/token` and `/api/user`, are unchanged.

#### Token lifetimes are shorter

Access tokens now expire after one hour instead of one month.
Refresh tokens now expire after 30 days instead of 100 years.
Each refresh returns a new refresh token that is valid for another 30 days.
An integration that refreshes at least once every 30 days keeps its access.

Each member can have one session per client.
When a member signs in again, Handover revokes their earlier access and refresh tokens for that client.

Access tokens issued before the upgrade keep their one-month expiry.
The migration updates each existing refresh token as follows:

- If its access token is revoked, the migration revokes the refresh token.
- If its access token is missing or has no expiry, the refresh token expires 30 days after the migration runs.
- Otherwise, the refresh token expires 30 days after its access token expires.

The migration never extends a refresh token's expiry.

#### Client IDs stay integers

Passport 13 uses UUIDs for new client IDs by default.
Handover keeps integer IDs, so existing integrations keep their client ID.

#### The groups API reports inherited groups

Each entry in the `groups` array now has a `direct` field.
Once you nest groups, the array also lists groups a member inherits.
Clients that only want direct memberships should filter on `direct: true`.
See the [user API](reference/api.md#groups).

#### Why this is a major release

Passport 13 changed behaviour Handover depends on as an OAuth provider.
It became headless, so the authorization endpoint needs a view bound, and it switched client IDs to UUIDs.
Handover handles both in code.
Hashing the client secrets cannot be handled in code, because it rewrites stored data.
That manual step is what makes this 4.0.0 rather than a 3.x release.
