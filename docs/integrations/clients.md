# Connecting a client

Handover works like VATSIM Connect.
A service that supports VATSIM Connect can use Handover by changing the URLs, the client ID and the secret.

## OAuth credentials

First [create an OAuth client](../concepts/oauth-clients.md) in Handover. Then configure the service with:

| Setting | Value |
| ------- | ----- |
| Method | Authorization code |
| Client ID | The ID of the client: a number for older clients, a UUID for new ones |
| Client secret | The secret shown when you created the client |
| Authorization endpoint | `https://YOUR_HANDOVER/oauth/authorize` |
| Token endpoint | `https://YOUR_HANDOVER/oauth/token` |
| User information endpoint | `https://YOUR_HANDOVER/api/user` |

The callback URL of the service must exactly match one of the client's redirect URIs in Handover.

## Scopes

Request the scopes the service needs, separated by spaces.

| Scope | Gives access to |
| ----- | --------------- |
| `full_name` | First and last name |
| `email` | E-mail address |
| `vatsim_details` | Ratings, region, division and subdivision |
| `country` | Country |
| `groups` | The groups the member belongs to |

If the service requests no scopes, Handover grants `full_name`, `email`, `vatsim_details` and `country`.
It never grants `groups` by default.

See the [user API](../reference/api.md) for the data each scope returns.

## Tokens

- An access token expires after one hour. To get a new access token, use the refresh token.
- A refresh token expires after 30 days. Each refresh returns a new refresh token that is valid for another 30 days. Store the new refresh token, because the old one stops working.
- Each member can have one session per client. When a member signs in again, Handover revokes their earlier access and refresh tokens for that client.
- Handover deletes revoked and expired tokens daily. For details, see [background jobs](../reference/background-jobs.md).

## Control Center

Control Center supports Handover out of the box.
See [Control Center's documentation](https://docs.vatsca.org/controlcenter/) for its settings.
