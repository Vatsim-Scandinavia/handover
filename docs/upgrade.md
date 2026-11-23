---
icon: material/cog-refresh
---

# Upgrading

These are the operational steps for moving Handover between versions.
Always read the section for your target version first. Some releases need manual steps.

--8<-- "exec-in-container.md"

## Before you upgrade

- Back up the database, plus the storage volume. The volume holds the OAuth [signing keys](setup/signing-keys.md) in `oauth-keys/`. In 3.x, it held them in `oauth-private.key` and `oauth-public.key`. Losing the keys invalidates every access token already issued.
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

If you run a [custom theme](setup/theme.md), rebuild your [custom image](setup/custom.md) on the new version.

!!! info "Why `--force`"
    In production, `migrate` asks for confirmation.
    Without an interactive terminal it answers no and does nothing.
    `--force` skips the prompt.

## Upgrading from 3.x to 4.0.0 { #upgrading-to-400 }

This release moves Handover to Laravel 13 and Laravel Passport 13, and the frontend toolchain to Vite 8 and jQuery 4.
It needs one manual step on the database: hashing the OAuth client secrets.
The container also moves the OAuth signing key into a key store that supports [key rotation](setup/signing-keys.md). You need to do nothing for that.

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

1. Back up the database and the storage volume. This is not optional for this release: hashing the secrets, changing the client schema and moving the signing key cannot be undone, and a [rollback](#rolling-back-to-3x) needs the backup.
2. Enable maintenance mode in the running 3.x container:

    ```sh
    php artisan down
    ```

3. Change the image tag from `:v3` to `:v4`, pull it, and recreate the container.
   The floating `:v3` tag never moves to 4.0.0, so you must change it.
   On start, the container imports `oauth-private.key` into `oauth-keys/`. For details, see [signing keys move to a key store](#signing-keys-move-to-a-key-store).
4. Apply the database changes:

    ```sh
    php artisan migrate --force
    ```

    The migrations do the following:

    - Add the `group_hierarchy` table for [member groups](groups.md#member-groups-transitive-membership).
    - Shorten the lifetime of existing refresh tokens.
    - Move OAuth clients to the [Passport 13 client schema](#oauth-clients-use-the-passport-13-schema).
    - Give every client [allowed scopes, required groups and a session setting](#client-access-settings).
    - Add the columns that [OpenID Connect](#handover-supports-openid-connect) sign-ins need.
    - Add the allowed origins of [public clients](#handover-supports-public-clients).
    - Add post-logout redirect URIs for [logout](#logging-out-asks-for-confirmation).

    If you run Handover without the container, step 3 imports nothing, and the migration imports the signing key instead.

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

7. Remove any `VITE_THEME_*` variables. They are no longer used. If you had custom colours, see [light and dark themes](#light-and-dark-themes).
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
3. Restore `oauth-private.key` and `oauth-public.key` from the storage volume backup you took in step 1.
   4.0.0 moved the signing key into `oauth-keys/`, and 3.x reads only these two files.
   If you set the key in `PASSPORT_PRIVATE_KEY` and `PASSPORT_PUBLIC_KEY` instead, set both variables again.
4. Clear the caches with `php artisan optimize:clear`.

The `group_hierarchy` table from 4.0.0 does no harm to 3.x if you keep it, but the restore removes it anyway.

3.x cannot read the 4.0.0 client schema, so you must restore the backup.
The restore removes every client created after the upgrade, together with its tokens.
Clients created after the upgrade have UUID IDs, which 3.x cannot store.
To keep such a client, create it again in 3.x and give the service its new ID and secret.

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

#### OAuth clients use the Passport 13 schema

Client IDs are now strings.
Existing clients keep their numeric ID, such as `5`, so existing integrations change nothing.
New clients get a UUID as their ID.

Each client now stores its redirect URIs as a list and its grant types explicitly.
The migration fills both from the old columns:

- The redirect URIs come from the old comma-separated `redirect` column.
- The grant types are exactly the ones Passport allowed the client before the upgrade. For a client created in the admin UI, these are authorization code, refresh token, client credentials, implicit and device code.

New clients get only authorization code and refresh token.
Administrators can change the grant types and redirect URIs of any client in the admin UI.
Editing a client in the admin UI no longer drops its extra redirect URIs.
See [OAuth clients](concepts/oauth-clients.md#grant-types).

After the backfill, the migration drops the old `user_id`, `redirect`, `personal_access_client` and `password_client` columns, and the `oauth_personal_access_clients` table.
You take no action. To undo the change, restore your backup as described in [rolling back to 3.x](#rolling-back-to-3x).

#### Clients limit scopes, members and sessions { #client-access-settings }

Each client now has three settings:

- [Allowed scopes](concepts/oauth-clients.md#allowed-scopes): the scopes the client may request.
- [Required groups](concepts/oauth-clients.md#required-groups): the groups whose members may sign in.
- [Single session](concepts/oauth-clients.md#single-session): whether each member keeps one session with the client.

The migration sets them so that every existing client keeps its behaviour. You need to do nothing:

- The client may request every scope that 3.x has: `full_name`, `email`, `vatsim_details`, `country` and `groups`.
- The client has no required groups, so every member may sign in.
- Each member keeps a single session with the client.

A request for a scope the client does not allow now fails with the `invalid_scope` error.
In 3.x, Handover granted any known scope to any client.

When you create a client in the admin UI, you must pick its allowed scopes.

#### Handover supports OpenID Connect

Handover is now an OpenID Connect provider. It adds:

- Discovery at `/.well-known/openid-configuration`.
- ID tokens.
- A UserInfo endpoint at `/oauth/userinfo`.

See [OpenID Connect](integrations/oidc.md).

You need to do nothing.
OpenID Connect is opt-in for each client: a service can use it only after an administrator ticks the new `openid` scope in the client's allowed scopes.
No existing client gets `openid` or `profile`, so existing services keep working.

Access tokens that hold the `groups` scope now carry a `groups` claim with the IDs of the member's groups.

#### Handover supports public clients

Administrators can now create public clients for services that run in the member's browser.
A public client has no secret, must use PKCE with the `S256` method, and calls the token and UserInfo endpoints from its allowed origins.
See [OAuth clients](concepts/oauth-clients.md#client-type) and [public clients](integrations/oidc.md#public-clients).

Existing clients that have a secret do not change.
A client created before 4.0.0 without a secret is now a public client:

- It must send a code challenge with the `S256` method. Handover now refuses the `plain` method.
- When you edit it, its redirect URIs must use `https`, or `http` on `localhost`, `127.0.0.1` or `[::1]`, and it may keep only the authorization code and refresh token grant types.

The token and UserInfo endpoints now check the `Origin` header for public clients.
A request with an `Origin` header that the public client does not allow fails with a `403` response.
Confidential clients are not affected, even when their server sends an `Origin` header.

#### Logging out asks for confirmation

Handover's logout button now sends a form to the new end session endpoint, `/oauth/logout`.
Logout now also ends the whole Handover session and renews its CSRF token.

Links to the old `/logout` path still work, but they no longer log the member out at once.
Handover now asks "Log out of Handover?", and the member must confirm.
If a service links to `/logout`, members need one more click.
To log members out without asking, the service can use [RP-initiated logout](integrations/oidc.md#logout) instead.

Services can now log members out of Handover through OpenID Connect.
The migration gives every existing client an empty list of post-logout redirect URIs, so you need to do nothing.
To let a service send members back after logout, add its post-logout redirect URIs in the admin UI.
See [post-logout redirect URIs](concepts/oauth-clients.md#post-logout-redirect-uris).

#### Signing keys move to a key store

Handover now keeps its OAuth signing keys in `oauth-keys/` on the storage volume, and you can rotate them with artisan commands.
See [signing keys](setup/signing-keys.md).

- On the first start of 4.0.0, the container imports `oauth-private.key` as the first active key and deletes `oauth-private.key` and `oauth-public.key`. If you set `PASSPORT_PRIVATE_KEY` instead, the container imports that key.
- Handover then ignores `PASSPORT_PRIVATE_KEY` and `PASSPORT_PUBLIC_KEY`. Remove them.
- New access tokens carry a `kid` header that names their key. Access tokens from 3.x have none, and keep working while the imported key is published.
- Handover publishes its public keys at `/.well-known/jwks.json`.
- `passport:keys` no longer has any effect.

Services that read `/api/user` need no changes.

!!! warning "Services that check tokens with a copy of the public key"
    Some services check access tokens themselves, with a copy of `oauth-public.key`.
    They keep working after the upgrade, but fail once you [rotate the key](setup/signing-keys.md#rotate-the-signing-key).
    Before you rotate, move them to the JWKS. See [verifying access tokens](integrations/clients.md#verifying-access-tokens).

#### The groups API reports inherited groups

Each entry in the `groups` array now has a `direct` field.
Once you nest groups, the array also lists groups a member inherits.
Clients that only want direct memberships should filter on `direct: true`.
See the [user API](reference/api.md#groups).

#### Light and dark themes

Handover now has a light and a dark theme, and follows the operating system by default.
Signed-in members can switch between system, light and dark with the toggle next to their name.
The choice is kept in the browser.

The `VITE_THEME_*` variables are gone, including `VITE_THEME_BORDER_RADIUS`.
The 4.0.0 betas still read them, so this applies to them too.
If you set custom colours with them, copy `resources/sass/themes/_custom.scss.example` to `_custom.scss`, move your colours into it, and rebuild.
Use `_light.scss` and `_dark.scss` as a reference.
See [customising the theme](setup/theme.md#colours), which also covers how to bake a custom theme into a container image.

`APP_NAME` no longer changes the name Handover shows.
Page titles and headings always say Handover.
Set the new `APP_TAGLINE` to describe your instance beneath the front page heading.
Leave `APP_NAME` as it is: it still names the session cookie, so changing or removing it signs every member out once.
A footer on every page says who runs your Handover and credits Handover to VATSIM Scandinavia.
See [front page heading](setup/theme.md#front-page-heading).

#### Why this is a major release

Passport 13 changed behaviour Handover depends on as an OAuth provider.
It became headless, so the authorization endpoint needs a view bound, and it switched client IDs to UUIDs.
Handover handles both in code, and existing clients keep their numeric IDs.
Hashing the client secrets cannot be handled in code, because it rewrites stored data.
That manual step is what makes this 4.0.0 rather than a 3.x release.
