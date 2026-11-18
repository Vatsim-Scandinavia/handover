# Client ids and schema

Status: Accepted, 2026-11-19. Amended 2026-11-19: legacy columns drop after a prod beta, inside 4.0.0.

## Context

`oauth_clients` still has the pre-13 Passport layout: integer ids, a comma-separated `redirect` column, and grant types derived at runtime from two booleans. Every admin-created client is confidential and first-party, so each one silently allows `implicit`, `client_credentials` and `device_code` as well as `authorization_code` and `refresh_token`. Live relying parties hold integer client ids in their config, and we don't know every party that relies on today's grants.

## Decision

Adopt Passport 13's layout: add `redirect_uris`, `grant_types` and `owner`, and backfill them. The legacy columns (`user_id`, `redirect`, `personal_access_client`, `password_client`) and the `oauth_personal_access_clients` table are dropped in a later PR. It merges after VATSCA's production has run a 4.0.0 beta containing the schema change, and still ships in 4.0.0. Self-hosters upgrading from 3.x backfill and drop in one `migrate`, and roll back by restoring their backup.

Clients keep one table with a string `id`. Existing clients keep their integer ids, such as `"5"`. New clients get UUIDv7 ids (`Passport::$clientUuids = true`). Every `client_id` column becomes a string. The client model accepts digit-only keys in route binding, so admin URLs for legacy clients still resolve.

Existing clients keep exactly the grant types they have today. New clients get only `authorization_code` and `refresh_token`. Admins can change a client's grant types in the admin form.

Per-client settings that Passport does not know about live in `oauth_client_policies`. It has one row per client, keyed by `client_id`, with typed columns. Every client has a row: the backfill creates one for each existing client, and creating a client creates its row. Columns that have a sensible default get it in the database.

## Consequences

Legacy clients keep `client_credentials`, so Passport's guard against a user id equal to a client id (`TokenGuard.php:146`) still applies to them. It cannot fire in practice, because user ids are VATSIM CIDs and client ids are small.

## Alternatives considered

Moving legacy clients to a separate table: Passport has a single client model and repository, and token tables can point at only one table.

Re-registering every client with a UUID: this breaks every live relying party for no security gain.

Trimming legacy grants to authorization code and refresh: this could break unknown users of the other grants. Admins can trim them one client at a time instead.

Policy columns on `oauth_clients`: a side table keeps our columns clear of future Passport schema changes.

Optional policy rows, where a missing row means defaults: each client's allowed scopes live here and have no safe default, so every client needs a row.
