# OAuth clients

An OAuth client is a service that lets members log in through Handover, such as Control Center or a forum.
Each client gets an ID and a secret, which the service uses to talk to Handover.

Administrators manage clients. An administrator is a direct member of a group marked as an admin group, see [groups](../groups.md#access).

## Managing clients in the browser

Open **OAuth Clients** in the header of the groups pages, or go to `/admin/oauth-clients`.
The page lists every active client with its ID, name and redirect URI.

- **New client**: enter a descriptive name and the redirect URI of the service. Members see the name, so make it clear.
- **Edit**: change the name or the redirect URI.
- **Regenerate**: create a new secret. The old secret stops working at once.
- **Delete**: remove the client and revoke its existing tokens.

!!! warning "Copy the secret right away"
    Handover stores only a hash of the secret.
    It shows the plaintext secret once, after you create a client or regenerate its secret.
    If you lose it, regenerate the secret and update the service.

Clients created here are confidential clients for the authorization code grant.
Each client has one redirect URI.

## Managing clients on the command line

You can also create a client with Passport's command:

```sh
php artisan passport:client
```

Answer the prompts with a descriptive name and the redirect URI of the service, and keep the client confidential.
The command prints the client ID and secret.

To revoke a client and all its tokens, run the command below. It asks for the client ID.

```sh
php artisan passport:client:delete
```

--8<-- "exec-in-container.md"

## What members see

Members never see a consent screen. Handover approves every client automatically, because the clients belong to your division.

The dashboard lists the services a member has authorised that still hold an active token.

Next, configure the service itself, see [connecting a client](../integrations/clients.md).
