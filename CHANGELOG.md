# Changelog

## [4.0.0](https://github.com/Vatsim-Scandinavia/handover/compare/v3.3.0...v4.0.0) (2026-08-31)


### ⚠ BREAKING CHANGES

* **passport:** Without an authorization view bound, a Handover install running Passport 13 cannot complete any OAuth authorization; the provider is non-functional.
* **passport:** Passport v13 changes the returned fields, even with this enabled, from 'client.redirect' to 'client.redirect_uris'.

### Features

* **dashboard:** app-owned authorized-clients endpoint ([4c832a6](https://github.com/Vatsim-Scandinavia/handover/commit/4c832a6a19949491009718d04976450c24d6c39b))
* **groups:** add nested groups with transitive membership ([10f6094](https://github.com/Vatsim-Scandinavia/handover/commit/10f609454e4a66a20a00398ad38465196ed7028c))
* unified group admin page and OAuth clients admin view ([77d8088](https://github.com/Vatsim-Scandinavia/handover/commit/77d80883cc0a1775d03c173cfd13dc3bf8ffd16e))


### Bug Fixes

* **deps:** update frontend dependencies and drop unused packages ([6c735a8](https://github.com/Vatsim-Scandinavia/handover/commit/6c735a8531bc8ad8d62a98b4ff938643ffbd581f))
* **deps:** upgrade laravel/framework to v13 ([#159](https://github.com/Vatsim-Scandinavia/handover/issues/159)) ([ed7f242](https://github.com/Vatsim-Scandinavia/handover/commit/ed7f24281e83f623f3c28e4edec85f3ffa1c5462))
* **deps:** upgrade non-framework dependencies to latest major ([#158](https://github.com/Vatsim-Scandinavia/handover/issues/158)) ([fc04a48](https://github.com/Vatsim-Scandinavia/handover/commit/fc04a48f4fdefbb33595466b47dd8479f43046e0))
* **frontend:** bundle FontAwesome 7 webfonts via [@use](https://github.com/use) ([c5ae229](https://github.com/Vatsim-Scandinavia/handover/commit/c5ae22930983e864600e2bd9c25f8541943b32f1))
* **groups:** make grantingRulesFor() closures push by reference ([10f6094](https://github.com/Vatsim-Scandinavia/handover/commit/10f609454e4a66a20a00398ad38465196ed7028c))
* **passport:** bind authorization view for headless Passport 13 ([5b35302](https://github.com/Vatsim-Scandinavia/handover/commit/5b35302b77d692574e51a292ed6e522b1cda1182))
* **passport:** implement OAuthenticatable contract on User ([bd74707](https://github.com/Vatsim-Scandinavia/handover/commit/bd7470790ceabf92fac0e296b6a1ab4ec67572c7))
* **passport:** re-enable JSON API routes and use redirect_uris ([37849f3](https://github.com/Vatsim-Scandinavia/handover/commit/37849f35c03a9af64ac0efbc937b68e0ce81ee05))
* **passport:** retain integer client ids on legacy schema ([844c7a2](https://github.com/Vatsim-Scandinavia/handover/commit/844c7a2aca53271248e861bc2375f367cab90e04))


### Miscellaneous Chores

* **deps:** upgrade various backend dependencies ([#155](https://github.com/Vatsim-Scandinavia/handover/issues/155)) ([a4ff521](https://github.com/Vatsim-Scandinavia/handover/commit/a4ff52191e3026f5081de7761f9014c2f0008e37))
* **frontend:** replace deprecated darken() with color.adjust() ([b2bde9a](https://github.com/Vatsim-Scandinavia/handover/commit/b2bde9abc723b8cfff1f1c28f8cb80e3269d7315))
* ignore .phpunit.cache directory ([9bee49e](https://github.com/Vatsim-Scandinavia/handover/commit/9bee49eb7b27cf2e05d50b05f52061c4cc40db16))

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
