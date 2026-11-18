# Token lifetimes and refresh policy

Status: Accepted, 2026-11-18. Amended 2026-11-19: the migration caps each refresh token relative to its access token.

## Context

Access tokens lasted one month and refresh tokens 100 years. Each time a new access token was issued, the `RevokeOldTokens` listener revoked the user's older access tokens for that client, but left their refresh tokens valid. The refresh grant checks only the refresh token's own revoked flag, so a leaked or superseded refresh token kept working for a century.

The relying parties limit what we can change. Booking and Flarum use the access token only at login, and Bookstack does not refresh. Control Center stores one token pair per user. Every night it refreshes each user whose access token has expired, even users who rarely log in, and then reads `/api/user`. If a single refresh fails, Control Center wipes that user's tokens.

## Decision

Access tokens last 1 hour. Tokens already issued keep their one-month expiry.

Refresh tokens last 30 days. Each refresh issues a new refresh token, so the 30 days restart every time. There is no absolute cap.

Each user keeps one session per client. `RevokeOldTokens` stays, and it now also revokes the refresh tokens that belong to the access tokens it revokes. The refresh token just issued belongs to the new access token, so it is never revoked.

A single migration ships with the change. It sets every live refresh token to expire 30 days after its access token expires, and revokes refresh tokens whose access token is already revoked. Old access tokens last up to a month after deploy, and Control Center only refreshes once its access token has expired, so a flat "deploy plus 30 days" cap could expire a refresh token before it is ever used.

## Consequences

Control Center renews each user's tokens on the first night after their old access token expires, and keeps them alive from then on, because 1-hour access tokens are always expired by the next nightly run. Any client that stops refreshing loses access within 30 days, and the daily `passport:purge` task removes the expired tokens.

A new login on one device ends the user's session with the same client on another device. No current client notices, since each keeps one token pair per user on its server.

## Alternatives considered

Dropping `RevokeOldTokens` would allow sessions on several devices, and short-lived tokens already limit what a leak can do. We kept it because it matches today's behaviour and closes the gap directly. If public clients need sessions on several devices, we can revisit this under per-client policy.

An absolute cap, such as one year, would quietly wipe Control Center tokens for users who rarely log in.

Token revocation (RFC 7009) and introspection (RFC 7662) are out of scope. No relying party asked for them.
