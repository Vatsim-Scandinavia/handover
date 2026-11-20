# Connecting a client

Handover works like VATSIM Connect.
A service that supports VATSIM Connect can use Handover by changing the URLs, the client ID and the secret.
A service that supports OpenID Connect can use Handover's discovery, ID token and UserInfo endpoint instead. See [OpenID Connect](oidc.md).

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
| JSON Web Key Set (JWKS) | `https://YOUR_HANDOVER/.well-known/jwks.json` |

The callback URL of the service must exactly match one of the client's redirect URIs in Handover.

## Scopes

Request the scopes the service needs, separated by spaces.
The service may request only the scopes its client allows.
A request for any other scope fails with the `invalid_scope` error.
A refresh fails the same way if an administrator removes a scope that the token holds.
For details, see [allowed scopes](../concepts/oauth-clients.md#allowed-scopes).

| Scope | Gives access to |
| ----- | --------------- |
| `full_name` | First and last name |
| `email` | E-mail address |
| `vatsim_details` | Ratings, region, division and subdivision |
| `country` | Country |
| `groups` | The groups the member belongs to |
| `openid` | Sign in with [OpenID Connect](oidc.md). The user API ignores it. |
| `profile` | First and last name, as [OpenID Connect](oidc.md#scopes-and-claims) claims. The user API ignores it. |

If the service requests no scopes, Handover grants `full_name`, `email`, `vatsim_details` and `country`, limited to the scopes the client allows.
It never grants `groups`, `openid` or `profile` by default.

See the [user API](../reference/api.md) for the data each scope returns.

## Tokens

- An access token expires after one hour. To get a new access token, use the refresh token.
- A refresh token expires after 30 days. Each refresh returns a new refresh token that is valid for another 30 days. Store the new refresh token, because the old one stops working.
- By default, each member can have one session per client. When a member signs in again, Handover revokes their earlier access and refresh tokens for that client. An administrator can turn this off per client. See [single session](../concepts/oauth-clients.md#single-session).
- If the client has [required groups](../concepts/oauth-clients.md#required-groups), a refresh fails with the `invalid_grant` error once the member has left them. Send the member through the sign-in again.
- Handover deletes revoked and expired tokens daily. For details, see [background jobs](../reference/background-jobs.md).
- Handover may replace its signing key without warning if the key leaks. Access tokens signed by that key then fail. Use the refresh token to get a new access token. See [signing keys](../setup/signing-keys.md#replace-a-leaked-key).

### Verifying access tokens

Most services send the access token to Handover and never read it.
If the service checks access tokens itself:

- An access token is a JWT signed with RS256. Its `kid` header names the signing key.
- Pick the key by `kid` from the JWKS. When you see an unknown `kid`, fetch the JWKS again.
- Cache the JWKS for at most one hour.
- If the token holds the `groups` scope, it carries a `groups` claim with the IDs of the member's groups. See [groups](oidc.md#groups).

## Control Center

Control Center supports Handover out of the box.
See [Control Center's documentation](https://docs.vatsca.org/controlcenter/) for its settings.
