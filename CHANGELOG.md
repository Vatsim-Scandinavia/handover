# Changelog

## [4.0.0-beta.3](https://github.com/Vatsim-Scandinavia/handover/compare/v4.0.0-beta.2...v4.0.0-beta.3) (2026-11-19)


### Bug Fixes

* **oauth:** redirect guests to login on /oauth/authorize ([#175](https://github.com/Vatsim-Scandinavia/handover/issues/175)) ([8f51758](https://github.com/Vatsim-Scandinavia/handover/commit/8f51758402abb30515fab5e438ac3e54f15608b1))


### Miscellaneous Chores

* Configure Renovate ([#169](https://github.com/Vatsim-Scandinavia/handover/issues/169)) ([fe9b672](https://github.com/Vatsim-Scandinavia/handover/commit/fe9b672f740f0222b56c50d0a3e8a4c6e4dabf25))
* **deps:** update dependency axios to v1.20.0 [security] ([#172](https://github.com/Vatsim-Scandinavia/handover/issues/172)) ([5cac0cf](https://github.com/Vatsim-Scandinavia/handover/commit/5cac0cf48d1079a73125009f798c2fa244788494))
* **deps:** update dependency laravel/framework to v13.40.0 [security] ([#173](https://github.com/Vatsim-Scandinavia/handover/issues/173)) ([b76774e](https://github.com/Vatsim-Scandinavia/handover/commit/b76774ee18ca5c4606e65af56c2cbf425560e9bd))
* **deps:** update dependency vite to v8.2.4 [security] ([#171](https://github.com/Vatsim-Scandinavia/handover/issues/171)) ([3a01f75](https://github.com/Vatsim-Scandinavia/handover/commit/3a01f754eaad832fa25d0918b344383d2bb4e064))

## [4.0.0-beta.2](https://github.com/Vatsim-Scandinavia/handover/compare/v4.0.0-beta.1...v4.0.0-beta.2) (2026-11-19)


### Bug Fixes

* **dashboard:** list authorized clients again on Passport 13 ([80e3a91](https://github.com/Vatsim-Scandinavia/handover/commit/80e3a91596fb1648434dc4563706565889ad97ef))


### Miscellaneous Chores

* drop release-please last-release-sha after 4.0.0-beta.1 ([9f4032f](https://github.com/Vatsim-Scandinavia/handover/commit/9f4032f14f3ad1bfbeb48ade9805410877f3de7a))

## [4.0.0-beta.1](https://github.com/Vatsim-Scandinavia/handover/compare/v3.3.1...v4.0.0-beta.1) (2026-11-19)


### ⚠ BREAKING CHANGES

* new clients get UUIDv7 ids instead of integers. A relying party that expects a numeric client id fails with a new client. Existing clients keep their ids.
* access tokens expire after 1 hour instead of 1 month. A client that uses an access token for longer must refresh it.
* **passport:** Without an authorization view bound, a Handover install running Passport 13 cannot complete any OAuth authorization; the provider is non-functional.
* **passport:** Passport v13 changes the returned fields, even with this enabled, from 'client.redirect' to 'client.redirect_uris'.

### Features

* add /api/v1/user alias and drop stale scope comment ([#165](https://github.com/Vatsim-Scandinavia/handover/issues/165)) ([0cbd943](https://github.com/Vatsim-Scandinavia/handover/commit/0cbd9432b7f6b0987ddd3493d5fe1da09ec1acff))
* adopt Passport 13 client schema with UUID ids ([#167](https://github.com/Vatsim-Scandinavia/handover/issues/167)) ([8486b22](https://github.com/Vatsim-Scandinavia/handover/commit/8486b223e7a04f1e70fe55fd64163827ac1292e9))
* **dashboard:** app-owned authorized-clients endpoint ([4c832a6](https://github.com/Vatsim-Scandinavia/handover/commit/4c832a6a19949491009718d04976450c24d6c39b))
* **groups:** add nested groups with transitive membership ([10f6094](https://github.com/Vatsim-Scandinavia/handover/commit/10f609454e4a66a20a00398ad38465196ed7028c))
* shorten token lifetimes and revoke old refresh tokens ([#166](https://github.com/Vatsim-Scandinavia/handover/issues/166)) ([32de985](https://github.com/Vatsim-Scandinavia/handover/commit/32de985ea12a49717f829896800c65b172dc8a0e))
* unified group admin page and OAuth clients admin view ([77d8088](https://github.com/Vatsim-Scandinavia/handover/commit/77d80883cc0a1775d03c173cfd13dc3bf8ffd16e))


### Bug Fixes

* **deps:** update frontend dependencies and drop unused packages ([6c735a8](https://github.com/Vatsim-Scandinavia/handover/commit/6c735a8531bc8ad8d62a98b4ff938643ffbd581f))
* **deps:** upgrade laravel/framework to v13 ([#159](https://github.com/Vatsim-Scandinavia/handover/issues/159)) ([ed7f242](https://github.com/Vatsim-Scandinavia/handover/commit/ed7f24281e83f623f3c28e4edec85f3ffa1c5462))
* **deps:** upgrade non-framework dependencies to latest major ([#158](https://github.com/Vatsim-Scandinavia/handover/issues/158)) ([fc04a48](https://github.com/Vatsim-Scandinavia/handover/commit/fc04a48f4fdefbb33595466b47dd8479f43046e0))
* **docker:** upgrade PHP to 8.3.36 for security fixes ([d787532](https://github.com/Vatsim-Scandinavia/handover/commit/d78753250df518efc3312f94a265c0edef13785e))
* **frontend:** bundle FontAwesome 7 webfonts via [@use](https://github.com/use) ([c5ae229](https://github.com/Vatsim-Scandinavia/handover/commit/c5ae22930983e864600e2bd9c25f8541943b32f1))
* **groups:** make grantingRulesFor() closures push by reference ([10f6094](https://github.com/Vatsim-Scandinavia/handover/commit/10f609454e4a66a20a00398ad38465196ed7028c))
* **passport:** bind authorization view for headless Passport 13 ([5b35302](https://github.com/Vatsim-Scandinavia/handover/commit/5b35302b77d692574e51a292ed6e522b1cda1182))
* **passport:** implement OAuthenticatable contract on User ([bd74707](https://github.com/Vatsim-Scandinavia/handover/commit/bd7470790ceabf92fac0e296b6a1ab4ec67572c7))
* **passport:** re-enable JSON API routes and use redirect_uris ([37849f3](https://github.com/Vatsim-Scandinavia/handover/commit/37849f35c03a9af64ac0efbc937b68e0ce81ee05))
* **passport:** retain integer client ids on legacy schema ([844c7a2](https://github.com/Vatsim-Scandinavia/handover/commit/844c7a2aca53271248e861bc2375f367cab90e04))


### Miscellaneous Chores

* **deps:** upgrade various backend dependencies ([#155](https://github.com/Vatsim-Scandinavia/handover/issues/155)) ([a4ff521](https://github.com/Vatsim-Scandinavia/handover/commit/a4ff52191e3026f5081de7761f9014c2f0008e37))
* **fix/php-8.3.36:** release 3.3.1 ([#162](https://github.com/Vatsim-Scandinavia/handover/issues/162)) ([3818540](https://github.com/Vatsim-Scandinavia/handover/commit/38185400f90e1a8da0e853f74300c25e922f782c))
* **frontend:** replace deprecated darken() with color.adjust() ([b2bde9a](https://github.com/Vatsim-Scandinavia/handover/commit/b2bde9abc723b8cfff1f1c28f8cb80e3269d7315))
* ignore .phpunit.cache directory ([9bee49e](https://github.com/Vatsim-Scandinavia/handover/commit/9bee49eb7b27cf2e05d50b05f52061c4cc40db16))
* release 4.0.0-beta.1 ([3f2d707](https://github.com/Vatsim-Scandinavia/handover/commit/3f2d70753b20331ed0dd50d7d4b971a69a854647))
* start release-please search after v3.3.0 ([ea72d27](https://github.com/Vatsim-Scandinavia/handover/commit/ea72d277778d68fe96d14258bd798ba1a4c001ad))
* update flake ([472811e](https://github.com/Vatsim-Scandinavia/handover/commit/472811e3897229439c5ef685ce1d707310a8281a))

## [3.3.1](https://github.com/Vatsim-Scandinavia/handover/compare/v3.3.0...v3.3.1) (2026-10-24)


### Bug Fixes

* **docker:** upgrade PHP to 8.3.36 for security fixes ([999a328](https://github.com/Vatsim-Scandinavia/handover/commit/999a328a1c03a8792245898c63821de68e72ae05))

## [3.3.0](https://github.com/Vatsim-Scandinavia/handover/compare/v3.2.0...v3.3.0) (2026-06-21)


### Features

* **groups:** add attribute, tag, and direct group management system ([#150](https://github.com/Vatsim-Scandinavia/handover/issues/150)) ([c8f49d4](https://github.com/Vatsim-Scandinavia/handover/commit/c8f49d47c79c25014c7a5835b851d38eacbf25e8))
* **groups:** add groups:add-admin artisan command  ([#150](https://github.com/Vatsim-Scandinavia/handover/issues/150)) ([c8f49d4](https://github.com/Vatsim-Scandinavia/handover/commit/c8f49d47c79c25014c7a5835b851d38eacbf25e8))
* **groups:** add web interface for handling groups ([#150](https://github.com/Vatsim-Scandinavia/handover/issues/150)) ([c8f49d4](https://github.com/Vatsim-Scandinavia/handover/commit/c8f49d47c79c25014c7a5835b851d38eacbf25e8))
* **oauth:** add groups OAuth scope and API response ([#150](https://github.com/Vatsim-Scandinavia/handover/issues/150)) ([c8f49d4](https://github.com/Vatsim-Scandinavia/handover/commit/c8f49d47c79c25014c7a5835b851d38eacbf25e8))


### Bug Fixes

* add a version link to the groups page ([#151](https://github.com/Vatsim-Scandinavia/handover/issues/151)) ([654d2a6](https://github.com/Vatsim-Scandinavia/handover/commit/654d2a6774f15a0924ca4a8386b648da5be3bc63))
* **deps:** update all deps within current ranges ([#143](https://github.com/Vatsim-Scandinavia/handover/issues/143)) ([09780f5](https://github.com/Vatsim-Scandinavia/handover/commit/09780f54c05c95ca4e3a828d945d10fabc2037be))
* **groups:** prevent non-admins from adding members to admin groups ([#152](https://github.com/Vatsim-Scandinavia/handover/issues/152)) ([ec8f885](https://github.com/Vatsim-Scandinavia/handover/commit/ec8f88593d3705e4be39c9024719b0dbd7b32614))
* return headers at the right time by removing erronous newline ([#145](https://github.com/Vatsim-Scandinavia/handover/issues/145)) ([8bfff5d](https://github.com/Vatsim-Scandinavia/handover/commit/8bfff5defbc8459b73455fdf0ef65b2ae01fa7d7))
