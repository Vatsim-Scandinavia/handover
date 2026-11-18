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

## Passport commands

Laravel Passport provides these commands. Handover uses them as follows.

| Command | Purpose |
| ------- | ------- |
| `passport:client` | Create an OAuth client, see [OAuth clients](../concepts/oauth-clients.md#managing-clients-on-the-command-line) |
| `passport:keys` | Create the OAuth signing keys. The container runs it on first start |
| `passport:hash` | Hash stored client secrets, needed once when [upgrading to 4.0.0](../upgrade.md#upgrading-to-400) |
| `passport:purge` | Remove revoked and expired tokens. The scheduler runs it daily |
