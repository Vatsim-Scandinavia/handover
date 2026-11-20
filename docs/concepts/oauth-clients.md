# OAuth clients

An OAuth client is a service that lets members log in through Handover, such as Control Center or a forum.
Each client gets an ID, which the service uses to talk to Handover.
A confidential client also gets a secret.

Administrators manage clients. An administrator is a direct member of a group marked as an admin group, see [groups](../groups.md#access).

## Managing clients in the browser

Open **OAuth Clients** in the header of the groups pages, or go to `/admin/oauth-clients`.
The page lists every active client with its ID, name, redirect URIs, grant types, allowed scopes and who may sign in.

- **New client**: choose the [client type](#client-type), enter a descriptive name and the redirect URIs of the service, and tick the scopes it may request. Members see the name, so make it clear.
- **Edit**: change the name, redirect URIs, grant types, allowed scopes, allowed origins, required groups or single session setting.
- **Regenerate**: create a new secret for a confidential client. The old secret stops working at once.
- **Delete**: remove the client and revoke its existing tokens.

!!! warning "Copy the secret right away"
    Handover stores only a hash of the secret.
    It shows the plaintext secret once, after you create a client or regenerate its secret.
    If you lose it, regenerate the secret and update the service.

A new client gets a UUID as its ID. Clients created before 4.0.0 keep their numeric ID.

### Client type

Choose the type when you create a client. You cannot change it later.

| Type | Use |
| ---- | --- |
| Confidential | The service runs on a server and keeps the secret there. This is the default. |
| Public | The service runs in the member's browser, such as a single-page app, where a secret cannot be kept. |

A public client has no secret and no **Regenerate** button.
It must prove each sign-in with PKCE, using the `S256` method, see [public clients](../integrations/oidc.md#public-clients).
It may have only the **Authorization code** and **Refresh token** grant types.
The list marks it as **Public**.

A client created before 4.0.0 without a secret is a public client.

### Redirect URIs

A client can have several redirect URIs, such as one per hostname of the service.
The service must send one of them exactly, so `https://example.test/callback` does not match `https://example.test/callback/`.

A public client's redirect URIs must use `https`.
For development, `http://localhost` is also allowed, with any port.

### Grant types

Grant types set how a client may get tokens.
A new client gets **Authorization code** and **Refresh token**, which is what a service that signs members in needs.

| Grant type | Use |
| ---------- | --- |
| Authorization code | Sign members in through the browser. |
| Refresh token | Get a new access token without signing the member in again. Untick it to make members sign in again once the access token expires. |
| Client credentials | Let the service get a token for itself, with no member. |

Clients created before 4.0.0 keep the grant types they had, which include client credentials, implicit and device code.
Handover does not support implicit, password, personal access or device code.
The form shows them only on clients that already have them, so you can untick them but not add them.
If a service does not use a grant type, untick it.

A public client may have only **Authorization code** and **Refresh token**.

### Allowed scopes

Allowed scopes set which [scopes](../integrations/clients.md#scopes) a client may request.
A new client has none ticked, so you must tick at least one.
Tick only the scopes the service needs.

If a service requests a scope that is not ticked, the sign-in fails with the `invalid_scope` error.
If you remove a scope that a service already holds, its next token refresh fails with the `invalid_scope` error, and the member must sign in again.
If a service requests no scopes, it gets the default scopes `full_name`, `email`, `vatsim_details` and `country`, limited to the ones that are ticked.
If none of those are ticked, the service gets a token with no scopes.

Clients created in 3.x allow every scope except `openid` and `profile`.
Tick those when a service moves to [OpenID Connect](../integrations/oidc.md).

### Allowed origins

Allowed origins set where a public client may call Handover's token and UserInfo endpoints from the browser.
Confidential clients have none.

Enter each origin as a scheme, a host and an optional port, with no path, such as `https://app.example.test` or `http://localhost:5173`.
Like a public client's redirect URIs, an origin must use `https`, or `http://localhost` for development.
Handover stores each origin in the form a browser sends it: in lowercase, and without the default port `443` or `80`.

When you create a public client, the form fills in the origins of its redirect URIs.
Change them if the service calls Handover from another origin.
A request from an origin that is not on the list fails, see [public clients](../integrations/oidc.md#public-clients).

### Required groups

Required groups set who may sign in to a client.
If you tick no groups, every member may sign in.
If you tick one or more groups, only members of at least one of them may sign in.
Membership includes [nested groups](../groups.md#nested-groups-transitive-membership): a member of a group nested in a required group may sign in too.

A member outside the required groups sees an error page in Handover that names the client.
Handover does not send them back to the service.

Handover checks the groups again each time the service refreshes a token.
If the member has left the required groups, the refresh fails with the `invalid_grant` error.
Access tokens that Handover already issued stay valid until they expire, for up to one hour.

You cannot delete a group while a client requires it.
Remove the group from the client first.

### Single session

By default, **Single session** is ticked, and each member can have one session with the client.
When a member signs in again, Handover revokes their earlier access and refresh tokens for that client.

Untick it to let a member stay signed in to the service on several devices at once.

## Managing clients on the command line

You can also create a client with Passport's command:

```sh
php artisan passport:client
```

Answer the prompts with a descriptive name and the redirect URI of the service, and keep the client confidential.
The command prints the client ID and secret.
A client created this way may request the default scopes, admits every member and keeps a single session.
To change that, edit the client in the browser.

To revoke a client and all its tokens, run the command below. It asks for the client ID.

```sh
php artisan passport:client:delete
```

--8<-- "exec-in-container.md"

## What members see

Members never see a consent screen. Handover approves every client automatically, because the clients belong to your division.

The dashboard lists the services a member has authorised that still hold an active token.

Next, configure the service itself, see [connecting a client](../integrations/clients.md).
