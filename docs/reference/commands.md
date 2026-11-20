# Artisan commands

Handover adds these commands to Laravel's `php artisan`.

--8<-- "exec-in-container.md"

| Command | Purpose |
| ------- | ------- |
| `groups:add-admin CID` | Add a member to the System Administrators group |
| `key:get` | Print the current application key |
| `member:data` | Fetch fresh member data from VATSIM Connect |
| `passport:client:delete` | Revoke an OAuth client and all its tokens |
| `reset:acceptedprivacy` | Make every member accept the Data Protection Policy again |
| `signing-keys:activate KID` | Make a staged signing key the active key |
| `signing-keys:generate` | Create a staged signing key |
| `signing-keys:import` | Import the Passport signing key, or create the first key |
| `signing-keys:list` | List the signing keys and their states |
| `signing-keys:remove KID` | Remove a retired signing key |
| `signing-keys:revoke KID` | Remove a signing key immediately |

## `groups:add-admin`

```sh
php artisan groups:add-admin 1234567
```

Adds the member with the given CID to the group with the slug `system-administrators`.
If the group does not exist, the command creates it as an admin group.
The member must have logged in to Handover once.

The command fails if a group with that slug exists but is not an admin group.
Fix that group by hand first.

## `key:get`

Prints the application key, so you can set it as `APP_KEY`. See [installation](../installation.md#setup).

## `member:data`

Updates every member who has VATSIM Connect tokens stored. The scheduler runs it daily, see [background jobs](background-jobs.md).

## `passport:client:delete`

Asks for a client ID and a confirmation.
It then revokes the client, its access tokens and its refresh tokens.
You can also delete clients in the [admin pages](../concepts/oauth-clients.md).

## `reset:acceptedprivacy`

Marks every member as not having accepted the Data Protection Policy.
See [updating the policy](../setup/dpp.md#updating-the-policy).

## Signing key commands

These commands manage the keys that sign access tokens.
For when to run them, see [signing keys](../setup/signing-keys.md).

The keys live in `/app/storage/oauth-keys`:

- `<kid>.key` holds one private key. Only the user that runs Handover can read it.
- `keys.json` records the state of each key, when it entered that state, and whether it was imported.

The `kid` is the [RFC 7638](https://www.rfc-editor.org/rfc/rfc7638) thumbprint of the public key.
Keys use RS256.

### `signing-keys:generate` { #signing-keys-generate }

```sh
php artisan signing-keys:generate
```

Creates a 2048-bit RSA key in the staged state and prints its `kid`.
Handover publishes the key but does not sign with it.

### `signing-keys:activate` { #signing-keys-activate }

```sh
php artisan signing-keys:activate KID
```

Makes a staged key the active key and retires the old active key.
The command refuses until the key has been staged for one hour. `--force` skips the wait.

### `signing-keys:remove` { #signing-keys-remove }

```sh
php artisan signing-keys:remove KID
```

Deletes a retired key and stops publishing it.
The command refuses until the key has been retired for two hours. `--force` skips the wait.

### `signing-keys:revoke` { #signing-keys-revoke }

```sh
php artisan signing-keys:revoke KID
```

Deletes a key in any state, immediately. Tokens it signed stop working.
If the key was active, the command creates and activates a new key, with no staging.

### `signing-keys:import` { #signing-keys-import }

```sh
php artisan signing-keys:import
```

Creates or imports the first key.
The container runs it on every start, and a migration runs it once.
If the key store already holds keys, the command does nothing.
Otherwise it does one of the following:

- If `PASSPORT_PRIVATE_KEY` is set, it imports that key as the active key.
- If `/app/storage/oauth-private.key` exists, it imports that key as the active key, then deletes `oauth-private.key` and `oauth-public.key`.
- Otherwise, it creates a new key and activates it.

An imported key keeps its size.
Handover ignores `PASSPORT_PRIVATE_KEY` and `PASSPORT_PUBLIC_KEY` after the import.
The command prints a warning while `PASSPORT_PRIVATE_KEY` is still set.

### `signing-keys:list` { #signing-keys-list }

```sh
php artisan signing-keys:list
```

Lists each key with its `kid`, its state, the time it entered that state, and whether it was imported.

## Passport commands

Laravel Passport provides these commands. Handover uses them as follows.

| Command | Purpose |
| ------- | ------- |
| `passport:client` | Create an OAuth client, see [OAuth clients](../concepts/oauth-clients.md#managing-clients-on-the-command-line) |
| `passport:keys` | Not used. Handover ignores the keys it creates. Use the [signing key commands](#signing-key-commands) instead |
| `passport:hash` | Hash stored client secrets, needed once when [upgrading to 4.0.0](../upgrade.md#upgrading-to-400) |
| `passport:purge` | Remove revoked and expired tokens. The scheduler runs it daily |
