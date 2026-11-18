# Signing keys and rotation

Status: Accepted, 2026-11-19

## Context

Handover signs access tokens with one RSA keypair. The entrypoint creates it on first boot in the storage volume. Access tokens carry no `kid`, and nothing can rotate the key. OIDC adds id_tokens and a JWKS. vcas-ok will check access tokens against that JWKS. Other vACCs run the same image with only a volume and env vars, and have no secret store.

## Decision

Keys are files in the storage volume, one per key, named by `kid`, plus a marker for the active key. The `kid` is the RFC 7638 thumbprint. Keys are RS256 only. New keys are RSA 2048.

One active key signs both access tokens and id_tokens, and both carry a `kid`. Passport's access token entity is subclassed to add the header. Passport's resource server validator is replaced by one that picks the key by `kid` from every published key.

Rotation is manual, run with artisan commands. A key moves through four states: staged (published, not signing), then active, then retired (published, not signing), then removed. Activation waits until the key has been staged for 1h, the JWKS cache time. Removal waits until the key has been retired for 2h. `--force` overrides both waits. An emergency revoke drops a key at once and, if it was active, activates a fresh one with no staging.

The existing key is imported as the first active key. Tokens with no `kid` are checked against it while it is still published.

## Consequences

Rotating the RSA key never touches refresh tokens or auth codes, which are encrypted with `APP_KEY`. A revoked key fails its live tokens at once, and relying parties recover by refreshing. An `id_token_hint` signed by a removed key can't be verified.

## Alternatives considered

Keys in a DB table encrypted with `APP_KEY`: losing or rotating `APP_KEY` would lose the signing keys too.

Keys in env vars or a secret store: a changing set of keys needs a redeploy, and other vACCs have no secret store.

A separate rotating key for id_tokens, with access tokens left on the Passport key: the Passport key could then never rotate, and access tokens would still lack a `kid`. Many JWKS clients reject those.

Scheduled rotation: more moving parts for a small org, and it breaks relying parties that pin a key by hand.
