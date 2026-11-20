# Signing keys

Handover signs every access token with an RSA key.
Services check the signature against the public keys that Handover publishes.
This page explains how to rotate a key, and how to replace one that has leaked.

--8<-- "exec-in-container.md"

## Back up the keys

Handover keeps its keys on the storage volume, in `/app/storage/oauth-keys`.
Back up the storage volume.
If you lose the keys, every access token stops working until the service refreshes it.

On the first start, the container creates a key, or imports the key from Handover 3.x.
For details, see [`signing-keys:import`](../reference/commands.md#signing-keys-import).

!!! warning "Always mount the storage volume"
    If the container starts without your storage volume, it finds no key and creates a new one.
    Tokens signed by your real key then fail until you mount the volume again.

## Key states

A key moves through these states:

| State | Published | Signs new tokens |
| ----- | --------- | ---------------- |
| Staged | Yes | No |
| Active | Yes | Yes |
| Retired | Yes | No |
| Removed | No | No |

Exactly one key is active.
Handover publishes every staged, active and retired key at `/.well-known/jwks.json`.
Services may cache the published keys for up to one hour.
Handover accepts an access token only while its key is published.

To see the keys and their states, run:

```sh
php artisan signing-keys:list
```

## Rotate the signing key

Rotate the key from time to time, or when someone who had access to the storage volume leaves.
No scheduled job does it for you.
A rotation takes about three hours, but members and services notice nothing.

1. Create a staged key:

    ```sh
    php artisan signing-keys:generate
    ```

    The command prints the `kid` (key ID) of the new key. Handover now publishes the key, but does not sign with it.

2. Wait at least one hour, so every service that caches the published keys has fetched the new key.
3. Activate the new key:

    ```sh
    php artisan signing-keys:activate NEW_KID
    ```

    New tokens now carry the new `kid`. The old key is retired: Handover still publishes it, so tokens it signed keep working.

4. Wait at least two hours, so every access token the old key signed has expired.
5. Remove the old key:

    ```sh
    php artisan signing-keys:remove OLD_KID
    ```

The commands refuse to run before the wait is over.
To skip the wait, add `--force`.
Use it only on a test system, or if you know that no service caches the published keys.
A service that has not fetched the new key rejects tokens signed with it until it fetches the keys again.

!!! warning "The first key after an upgrade from 3.x"
    Access tokens from Handover 3.x last one month and have no `kid`.
    Handover checks them against the key it imported from 3.x.
    `signing-keys:list` shows that key with `yes` in the **Imported** column.
    Wait one month after the upgrade before you remove it, or those tokens fail and services must refresh them.

## Replace a leaked key

If a private key may have leaked, revoke it immediately:

```sh
php artisan signing-keys:revoke KID
```

- Handover deletes the key and stops publishing it. Every access token that the key signed fails.
- If the key was active, Handover creates a new key and activates it immediately, with no staging.
- Services recover by using their refresh token to get a new access token. Refresh tokens do not depend on the signing key.
- Members stay signed in to Handover.

A service that cached the published keys rejects new tokens until it fetches the keys again, which takes up to one hour.
Most libraries fetch the keys again as soon as they see an unknown `kid`.

## Restore a backup

The keys in `/app/storage/oauth-keys` must match the tokens that services hold.
If you restore an older storage volume, keys created since that backup are gone, and the tokens they signed fail.
Services recover by refreshing their tokens.
