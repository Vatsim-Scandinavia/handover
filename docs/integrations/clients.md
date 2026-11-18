# Connecting a client

Handover works like VATSIM Connect.
A service that supports VATSIM Connect can use Handover by changing the URLs, the client ID and the secret.

## OAuth credentials

First [create an OAuth client](../concepts/oauth-clients.md) in Handover. Then configure the service with:

| Setting | Value |
| ------- | ----- |
| Method | Authorization code |
| Client ID | The ID of the client, a short number |
| Client secret | The secret shown when you created the client |
| Authorization endpoint | `https://YOUR_HANDOVER/oauth/authorize` |
| Token endpoint | `https://YOUR_HANDOVER/oauth/token` |
| User information endpoint | `https://YOUR_HANDOVER/api/user` |

The redirect URI in Handover must match the callback URL of the service.

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

- Access tokens expire after one month.
- Refresh tokens expire after 100 years.
- Handover removes revoked and expired tokens daily, see [background jobs](../reference/background-jobs.md).

## Control Center

Control Center supports Handover out of the box.
See [Control Center's documentation](https://docs.vatsca.org/controlcenter/) for its settings.
