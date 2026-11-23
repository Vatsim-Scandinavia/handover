# OpenID Connect

Handover is an OpenID Connect provider.
A service that supports OpenID Connect can sign members in with discovery, an ID token and the UserInfo endpoint, instead of the VATSIM Connect-style [user API](../reference/api.md).

## Turning on OpenID Connect for a client

OpenID Connect is opt-in for each client.
To turn it on, tick `openid` in the client's [allowed scopes](../concepts/oauth-clients.md#allowed-scopes), and usually `profile`, `email` and `groups` as well.
Clients created in 3.x do not have `openid` either.
A request for `openid` from a client that does not allow it fails with the `invalid_scope` error.

The service keeps its client ID, secret and redirect URIs.
A service that runs in the browser needs a [public client](#public-clients).
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
| End session endpoint | `https://YOUR_HANDOVER/oauth/logout` |
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
The array covers every group the member belongs to, including groups they inherit through [member groups](../groups.md#member-groups-transitive-membership).
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

## Public clients

A service that runs in the member's browser, such as a single-page app, cannot keep a secret.
Register it as a [public client](../concepts/oauth-clients.md#client-type) instead.
A public client has no secret, so it signs members in with the authorization code flow and PKCE (RFC 7636).

- Send a `code_challenge` with `code_challenge_method=S256` to the authorization endpoint. Handover refuses a request without a challenge, or with the `plain` method, with the `invalid_request` error.
- Send the matching `code_verifier` and the `client_id` to the token endpoint, but no `client_secret`.
- A refresh needs only the `refresh_token` and the `client_id`.

A public client can call the token endpoint and the UserInfo endpoint from the browser.
Handover allows a call only from the client's [allowed origins](../concepts/oauth-clients.md#allowed-origins):

- The token endpoint checks the `Origin` header against the allowed origins of the client named by `client_id`.
- The UserInfo endpoint checks the `Origin` header against the allowed origins of the client the access token was issued to.
- If the origin is not allowed, Handover refuses the request with a `403` response and no CORS headers.

Handover does not check the `Origin` header for a confidential client, and sends it no CORS headers.

Handover answers a CORS preflight for an origin that any client allows.
The responses never allow credentials, so do not send cookies with these calls.
The authorization endpoint and the [user API](../reference/api.md) do not support CORS.

Refresh tokens and sessions work for a public client as they do for a confidential one.
Each refresh returns a new refresh token, and the [single session](../concepts/oauth-clients.md#single-session) setting applies.
A public client often runs on several devices per member, so consider turning single session off for it.

## Logout

A service can log the member out of Handover when they log out of the service (OpenID Connect RP-Initiated Logout 1.0).
Send the member's browser to the end session endpoint, `/oauth/logout`, with `GET` or a form `POST`:

```http
GET /oauth/logout?id_token_hint=ID_TOKEN&post_logout_redirect_uri=https%3A%2F%2Fservice.example.org%2Flogged-out&state=STATE
```

| Parameter | Use |
| --------- | --- |
| `id_token_hint` | Optional. An ID token Handover issued to the service for the member. An expired ID token works. |
| `client_id` | Optional. The client ID. Send it when you send no `id_token_hint`. |
| `post_logout_redirect_uri` | Optional. Where Handover sends the member after logout. It must exactly match one of the client's [post-logout redirect URIs](../concepts/oauth-clients.md#post-logout-redirect-uris). |
| `state` | Optional. Handover adds it to the `post_logout_redirect_uri` unchanged. |

Handover ignores `ui_locales` and `logout_hint`.

Handover answers a `POST` with a redirect to the same endpoint with `GET`, because the browser does not send Handover's session cookie with a `POST` from another site.
The parameters, including the `id_token_hint`, then appear in the URL.

Handover checks the `id_token_hint` signature against its published keys, and checks its `iss`.
It does not check `exp`.
If Handover cannot verify the hint, it treats the request as if it had no hint.

Handover takes the client from the `aud` of the hint, or from `client_id`.
If both are present and name different clients, Handover shows an error page and does not redirect.
If the `post_logout_redirect_uri` is not registered for that client, or Handover cannot tell the client, Handover shows an error page and does not redirect.

What happens next depends on who is signed in to Handover:

- If the hint names the member who is signed in, Handover logs them out without asking.
- If someone else is signed in, or the request has no valid hint, Handover asks the member to confirm the logout. If they confirm, Handover logs them out.
- If nobody is signed in, Handover does not ask.

After logout, Handover sends the member to the `post_logout_redirect_uri`, with `state` if you sent one.
Without a `post_logout_redirect_uri`, Handover shows a page that says the member is logged out.

Logout ends the member's session in Handover only.
Handover revokes no access or refresh tokens, and the member stays signed in to VATSIM and to other services.
End the member's session in the service before you send them to Handover.

## Limitations

- Handover does not support back-channel or front-channel logout, so it does not tell other services when a member logs out.
- Handover ignores the `max_age` and `claims` request parameters.
- Handover never shows a consent screen and ignores `prompt=consent`. `prompt=none` and `prompt=login` work as OpenID Connect defines.
