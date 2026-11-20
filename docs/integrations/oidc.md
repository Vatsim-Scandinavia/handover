# OpenID Connect

Handover is an OpenID Connect provider.
A service that supports OpenID Connect can sign members in with discovery, an ID token and the UserInfo endpoint, instead of the VATSIM Connect-style [user API](../reference/api.md).

## Turning on OpenID Connect for a client

OpenID Connect is opt-in for each client.
To turn it on, tick `openid` in the client's [allowed scopes](../concepts/oauth-clients.md#allowed-scopes), and usually `profile`, `email` and `groups` as well.
Clients created in 3.x do not have `openid` either.
A request for `openid` from a client that does not allow it fails with the `invalid_scope` error.

The service keeps its client ID, secret and redirect URIs.
The [user API](../reference/api.md) keeps working for every client.

## Discovery

Configure the service with the discovery URL:

```text
https://YOUR_HANDOVER/.well-known/openid-configuration
```

The discovery document lists the endpoints, scopes and claims that Handover supports.
The issuer is Handover's `APP_URL`, without a trailing slash.
Services compare the issuer exactly, so set `APP_URL` to the public URL that services use. See [environment variables](../reference/environment-variables.md).

If the service cannot read discovery, configure it with these settings instead:

| Setting | Value |
| ------- | ----- |
| Issuer | `https://YOUR_HANDOVER` |
| Authorization endpoint | `https://YOUR_HANDOVER/oauth/authorize` |
| Token endpoint | `https://YOUR_HANDOVER/oauth/token` |
| UserInfo endpoint | `https://YOUR_HANDOVER/oauth/userinfo` |
| JSON Web Key Set (JWKS) | `https://YOUR_HANDOVER/.well-known/jwks.json` |
| Response type | `code` |
| Signing algorithm | `RS256` |

## Scopes and claims

Request `openid` together with the scopes for the claims the service needs.
The ID token and the UserInfo endpoint return the same claims.

| Scope | Claims |
| ----- | ------ |
| `openid` | `sub`: the member's CID, as a string |
| `profile` | `name`, `given_name` and `family_name` |
| `email` | `email` |
| `groups` | `groups`: the IDs of the member's groups, see [groups](#groups) |

Handover does not send `email_verified`. Some libraries refuse to link accounts by e-mail without it. Configure the service not to require it.
The `full_name`, `vatsim_details` and `country` scopes add no claims. They apply to the user API only.
Handover leaves out a claim that has no value, such as `family_name` for a member with no last name.

## Groups

The `groups` claim is an array of group IDs.
Each ID is a UUID that never changes.
The array covers every group the member belongs to, including groups they inherit through [nested groups](../groups.md#nested-groups-transitive-membership).
It is empty when the member belongs to no group.

Map roles in the service from group IDs, not from group names or slugs.
Administrators can rename a group at any time, but its ID stays the same.

When the client is granted `groups`, the access token also carries a `groups` claim with the same IDs.
Handover sets the list when it issues the token, so it can be up to one hour out of date.

## ID token

The token endpoint returns an `id_token` next to the access token when the sign-in grants `openid`.
Verify its signature the same way as an access token. See [verifying access tokens](clients.md#verifying-access-tokens).

| Claim | Value |
| ----- | ----- |
| `iss` | The issuer |
| `sub` | The member's CID, as a string |
| `aud` | The client ID |
| `iat` | When Handover issued the ID token |
| `exp` | When the ID token expires, one hour after `iat` |
| `auth_time` | When the member signed in to Handover. Left out if the member's Handover session started before the upgrade to 4.0.0. |
| `nonce` | The `nonce` the service sent to the authorization endpoint, if it sent one |
| `at_hash` | The hash of the access token |

Send a `nonce` with each sign-in and check that the ID token returns it.
A refresh returns a new access token and refresh token, but no ID token.

## UserInfo endpoint

Send the access token to `/oauth/userinfo` with `GET` or `POST`. Handover returns the claims as JSON:

```http
GET /oauth/userinfo
Authorization: Bearer ACCESS_TOKEN
```

```json
{
  "sub": "1234567",
  "name": "Jane Doe",
  "given_name": "Jane",
  "family_name": "Doe",
  "email": "jane@example.org",
  "groups": ["0195f1d2-7c1a-7b3e-9a41-2f0c8d6e4b10"]
}
```

An access token without the `openid` scope gets a `403` response with the `insufficient_scope` error.
A missing or invalid access token gets a `401` response.

## Limitations

- Handover does not support logging out from the service (RP-initiated logout).
- Handover ignores the `max_age` and `claims` request parameters.
- Handover never shows a consent screen and ignores `prompt=consent`. `prompt=none` and `prompt=login` work as OpenID Connect defines.
- Every client is confidential and must send its secret to the token endpoint.
