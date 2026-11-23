# Changelog

## [4.0.0-beta.6](https://github.com/Vatsim-Scandinavia/handover/compare/v4.0.0-beta.5...v4.0.0-beta.6) (2026-11-23)

This update features a new, slightly cleaner, less centered, look for Handover which improves on error pages, next steps, group overview, apps, and customiztion options.

### Features

* add client policies for scopes, required groups and sessions ([c834088](https://github.com/Vatsim-Scandinavia/handover/commit/c834088aa1d5dd94096ab6c162ec52d03bcb6182))
* add OpenID Connect core ([69e0c0b](https://github.com/Vatsim-Scandinavia/handover/commit/69e0c0b32c6fcadf95753575a3abacc8893dd15d))
* add public clients, PKCE and CORS ([14d3312](https://github.com/Vatsim-Scandinavia/handover/commit/14d33121a505f522794fdb24e4337a0bf9a075f4))
* add RP-initiated logout ([894e04c](https://github.com/Vatsim-Scandinavia/handover/commit/894e04c15004db2e24bcd2280239f14c5ffd9d0a))
* add signing key rotation with kid and JWKS ([f82ee35](https://github.com/Vatsim-Scandinavia/handover/commit/f82ee35986bad84c5f362bb6138e7f70c5ce406f))
* **dashboard:** show services and groups as strips ([52287b3](https://github.com/Vatsim-Scandinavia/handover/commit/52287b380d6b032ac54436ca01de8b2ce398663e))
* **dpp:** lay out the privacy summary as a list ([52287b3](https://github.com/Vatsim-Scandinavia/handover/commit/52287b380d6b032ac54436ca01de8b2ce398663e))
* **errors:** add APP_OWNER_SUPPORT for the support line ([52287b3](https://github.com/Vatsim-Scandinavia/handover/commit/52287b380d6b032ac54436ca01de8b2ce398663e))
* **errors:** brand the error pages ([52287b3](https://github.com/Vatsim-Scandinavia/handover/commit/52287b380d6b032ac54436ca01de8b2ce398663e))
* **errors:** explain each error and the next step ([52287b3](https://github.com/Vatsim-Scandinavia/handover/commit/52287b380d6b032ac54436ca01de8b2ce398663e))
* **landing:** explain what Handover is for ([52287b3](https://github.com/Vatsim-Scandinavia/handover/commit/52287b380d6b032ac54436ca01de8b2ce398663e))
* **oauth:** add next steps to the refused page ([52287b3](https://github.com/Vatsim-Scandinavia/handover/commit/52287b380d6b032ac54436ca01de8b2ce398663e))
* **theme:** add light and dark themes with a local toggle ([#231](https://github.com/Vatsim-Scandinavia/handover/issues/231)) ([c10a1a6](https://github.com/Vatsim-Scandinavia/handover/commit/c10a1a66a66ca8ad26d30c810afcedb2771b1605))
* **theme:** rework for usability, readability & simplicity ([#233](https://github.com/Vatsim-Scandinavia/handover/issues/233)) ([52287b3](https://github.com/Vatsim-Scandinavia/handover/commit/52287b380d6b032ac54436ca01de8b2ce398663e))
* **ui:** move member pages to a left-aligned sheet and top bar ([52287b3](https://github.com/Vatsim-Scandinavia/handover/commit/52287b380d6b032ac54436ca01de8b2ce398663e))
* **ui:** share one top bar between member and management pages ([52287b3](https://github.com/Vatsim-Scandinavia/handover/commit/52287b380d6b032ac54436ca01de8b2ce398663e))


### Bug Fixes

* **copy:** use "log in" and "log out" as verbs ([52287b3](https://github.com/Vatsim-Scandinavia/handover/commit/52287b380d6b032ac54436ca01de8b2ce398663e))
* **dashboard:** link linked services to their origin ([52287b3](https://github.com/Vatsim-Scandinavia/handover/commit/52287b380d6b032ac54436ca01de8b2ce398663e))
* **dashboard:** render linked services on the server ([52287b3](https://github.com/Vatsim-Scandinavia/handover/commit/52287b380d6b032ac54436ca01de8b2ce398663e))
* **dashboard:** replace the data protection banner with a plain note ([52287b3](https://github.com/Vatsim-Scandinavia/handover/commit/52287b380d6b032ac54436ca01de8b2ce398663e))
* **dashboard:** wrap group rows on narrow screens ([52287b3](https://github.com/Vatsim-Scandinavia/handover/commit/52287b380d6b032ac54436ca01de8b2ce398663e))
* **dpp:** raise the privacy summary's text contrast ([52287b3](https://github.com/Vatsim-Scandinavia/handover/commit/52287b380d6b032ac54436ca01de8b2ce398663e))
* let confidential clients send Origin, allow loopback IPs ([1931bd4](https://github.com/Vatsim-Scandinavia/handover/commit/1931bd43b2572f8e90647f8e4a3d5423049417c9))
* **theme:** darken the page background in dark mode ([52287b3](https://github.com/Vatsim-Scandinavia/handover/commit/52287b380d6b032ac54436ca01de8b2ce398663e))
* **theme:** tint dark cards and lighten outline buttons ([52287b3](https://github.com/Vatsim-Scandinavia/handover/commit/52287b380d6b032ac54436ca01de8b2ce398663e))
* **ui:** give each member page its own title ([52287b3](https://github.com/Vatsim-Scandinavia/handover/commit/52287b380d6b032ac54436ca01de8b2ce398663e))
* **ui:** keep footer links readable on hover ([52287b3](https://github.com/Vatsim-Scandinavia/handover/commit/52287b380d6b032ac54436ca01de8b2ce398663e))
* **ui:** keep the management links readable on hover ([52287b3](https://github.com/Vatsim-Scandinavia/handover/commit/52287b380d6b032ac54436ca01de8b2ce398663e))
* **ui:** make the management links read as links ([52287b3](https://github.com/Vatsim-Scandinavia/handover/commit/52287b380d6b032ac54436ca01de8b2ce398663e))


### Miscellaneous Chores

* **deps:** update dependency vue to v3.5.46 ([#226](https://github.com/Vatsim-Scandinavia/handover/issues/226)) ([357a4c4](https://github.com/Vatsim-Scandinavia/handover/commit/357a4c4c059ec5aedbf44d0e1b068fbf0307cac9))

## [4.0.0-beta.5](https://github.com/Vatsim-Scandinavia/handover/compare/v4.0.0-beta.4...v4.0.0-beta.5) (2026-11-19)


### Miscellaneous Chores

* **deps:** update dependency phpunit/phpunit to v12.5.45 ([#218](https://github.com/Vatsim-Scandinavia/handover/issues/218)) ([13e3cfc](https://github.com/Vatsim-Scandinavia/handover/commit/13e3cfce3430d98b57ecb29cb9d01b7ca3207d34))
* **deps:** update dependency sentry/sentry-laravel to v4.31.0 ([#219](https://github.com/Vatsim-Scandinavia/handover/issues/219)) ([14031be](https://github.com/Vatsim-Scandinavia/handover/commit/14031be1d266f07f4562a7963c246fae6a513759))


### Continuous Integration

* build images on tag pushes again ([ad6148b](https://github.com/Vatsim-Scandinavia/handover/commit/ad6148bc593c968bc90d7e8ebbbaa8be1c2a105f))

## [4.0.0-beta.4](https://github.com/Vatsim-Scandinavia/handover/compare/v4.0.0-beta.3...v4.0.0-beta.4) (2026-11-19)


### Features

* search and group the groups overview ([#221](https://github.com/Vatsim-Scandinavia/handover/issues/221)) ([3c1e10c](https://github.com/Vatsim-Scandinavia/handover/commit/3c1e10c6cbadaba8d61bbfbe84fd8331a2f9c6f1))


### Bug Fixes

* **deps:** update dependency sass to v1.108.2 ([#199](https://github.com/Vatsim-Scandinavia/handover/issues/199)) ([368f578](https://github.com/Vatsim-Scandinavia/handover/commit/368f578257edea2df01efb8b5af1cb1225c5b62d))
* drop the global icon margin ([#220](https://github.com/Vatsim-Scandinavia/handover/issues/220)) ([bd41aab](https://github.com/Vatsim-Scandinavia/handover/commit/bd41aab5fab66fbf3d4dd8a1c57e0a595a7d6dc1))
* keep group page in edit mode after saving ([#222](https://github.com/Vatsim-Scandinavia/handover/issues/222)) ([6496e55](https://github.com/Vatsim-Scandinavia/handover/commit/6496e55341923f7df11ee978636705e70af8c91e))


### Miscellaneous Chores

* **deps:** update actions/cache action to v6 ([#200](https://github.com/Vatsim-Scandinavia/handover/issues/200)) ([ff52e15](https://github.com/Vatsim-Scandinavia/handover/commit/ff52e15dd75a589d43b997441d6b29e11974ba5b))
* **deps:** update actions/checkout action to v7 ([#201](https://github.com/Vatsim-Scandinavia/handover/issues/201)) ([6797537](https://github.com/Vatsim-Scandinavia/handover/commit/6797537e51359c2394cd0fb6a75d88dbdf0a1677))
* **deps:** update actions/setup-node action to v7 ([#202](https://github.com/Vatsim-Scandinavia/handover/issues/202)) ([3c3a449](https://github.com/Vatsim-Scandinavia/handover/commit/3c3a4493cad66db9da6d38979491f96f4df5f15a))
* **deps:** update dependency @vitejs/plugin-vue to v6.0.11 ([#176](https://github.com/Vatsim-Scandinavia/handover/issues/176)) ([07ade3b](https://github.com/Vatsim-Scandinavia/handover/commit/07ade3b098fc27b6374554fd68687cee11d05bf0))
* **deps:** update dependency alpinejs to v3.17.5 ([#186](https://github.com/Vatsim-Scandinavia/handover/issues/186)) ([29e2ea2](https://github.com/Vatsim-Scandinavia/handover/commit/29e2ea22e5ceef3df134e98f40a2e8da94751661))
* **deps:** update dependency axios to v1.21.1 ([#187](https://github.com/Vatsim-Scandinavia/handover/issues/187)) ([f812dcd](https://github.com/Vatsim-Scandinavia/handover/commit/f812dcd1509d438e5fbf78188deb3c330dc13df8))
* **deps:** update dependency barryvdh/laravel-debugbar to v4.4.5 ([#177](https://github.com/Vatsim-Scandinavia/handover/issues/177)) ([6580664](https://github.com/Vatsim-Scandinavia/handover/commit/6580664d8b3f465a44f664e52b1a52c6b8743191))
* **deps:** update dependency bootstrap to v5.3.9 ([#179](https://github.com/Vatsim-Scandinavia/handover/issues/179)) ([a474d28](https://github.com/Vatsim-Scandinavia/handover/commit/a474d2877d4401b21b4336d3137e77ec3347ba6c))
* **deps:** update dependency jquery to v4.1.0 ([#188](https://github.com/Vatsim-Scandinavia/handover/issues/188)) ([ea0dfb8](https://github.com/Vatsim-Scandinavia/handover/commit/ea0dfb80ce05ab75569f2d0e71b1a7d557539fe3))
* **deps:** update dependency laravel-vite-plugin to v3.2.1 ([#189](https://github.com/Vatsim-Scandinavia/handover/issues/189)) ([25689d2](https://github.com/Vatsim-Scandinavia/handover/commit/25689d221f06cca7f63cd870185e09051412f710))
* **deps:** update dependency laravel/boost to v2.10.4 ([#190](https://github.com/Vatsim-Scandinavia/handover/issues/190)) ([554acd3](https://github.com/Vatsim-Scandinavia/handover/commit/554acd3570967e87ae2b24bd27f4a3ecc4927d94))
* **deps:** update dependency laravel/framework to v13.43.0 ([#191](https://github.com/Vatsim-Scandinavia/handover/issues/191)) ([9381a7c](https://github.com/Vatsim-Scandinavia/handover/commit/9381a7c707d964c3b3542a3ca9dd9c6bb518c0de))
* **deps:** update dependency laravel/passport to v13.12.0 ([#192](https://github.com/Vatsim-Scandinavia/handover/issues/192)) ([e1eca31](https://github.com/Vatsim-Scandinavia/handover/commit/e1eca312d1dd026245953c80eadb752248e11262))
* **deps:** update dependency laravel/tinker to v3.2.0 ([#193](https://github.com/Vatsim-Scandinavia/handover/issues/193)) ([5f2f66b](https://github.com/Vatsim-Scandinavia/handover/commit/5f2f66bb918b65c60221f8dd0f81f8dbf03d510c))
* **deps:** update dependency league/oauth2-client to v2.9.1 ([#180](https://github.com/Vatsim-Scandinavia/handover/issues/180)) ([88f80aa](https://github.com/Vatsim-Scandinavia/handover/commit/88f80aa2926ddb9993d7022309b4b5734657b154))
* **deps:** update dependency mockery/mockery to v1.6.15 ([#181](https://github.com/Vatsim-Scandinavia/handover/issues/181)) ([1340f13](https://github.com/Vatsim-Scandinavia/handover/commit/1340f13c0b4ba190d29c55628569e21a32073f68))
* **deps:** update dependency node to v26 ([#203](https://github.com/Vatsim-Scandinavia/handover/issues/203)) ([3fdfcfb](https://github.com/Vatsim-Scandinavia/handover/commit/3fdfcfbde081e396e7e0559eae13a5fb14db3a8b))
* **deps:** update dependency nunomaduro/collision to v8.9.6 ([#182](https://github.com/Vatsim-Scandinavia/handover/issues/182)) ([267978d](https://github.com/Vatsim-Scandinavia/handover/commit/267978dd34f4bfed3c8123692f171e1948834766))
* **deps:** update dependency phpunit/phpunit to v12.5.44 ([#183](https://github.com/Vatsim-Scandinavia/handover/issues/183)) ([eee9e9a](https://github.com/Vatsim-Scandinavia/handover/commit/eee9e9acdd6619a742e45805178719828d597be6))
* **deps:** update dependency sentry/sentry-laravel to v4.30.0 ([#194](https://github.com/Vatsim-Scandinavia/handover/issues/194)) ([73ec061](https://github.com/Vatsim-Scandinavia/handover/commit/73ec061576d759a7cb60043ba894188e3da26477))
* **deps:** update dependency vite to v8.3.8 ([#195](https://github.com/Vatsim-Scandinavia/handover/issues/195)) ([128d194](https://github.com/Vatsim-Scandinavia/handover/commit/128d1948c2052a238b524f9cd7151fc141c0adff))
* **deps:** update dependency vue to v3.5.45 ([#184](https://github.com/Vatsim-Scandinavia/handover/issues/184)) ([ffe9cac](https://github.com/Vatsim-Scandinavia/handover/commit/ffe9cacc6f7523b3bd5cf6b84e1f05056ac9e6cd))
* **deps:** update docker.io/library/php docker tag to v8.5.12 ([#196](https://github.com/Vatsim-Scandinavia/handover/issues/196)) ([1521f99](https://github.com/Vatsim-Scandinavia/handover/commit/1521f99abb12c7f82c6ac357f317e54d8897bc0c))
* **deps:** update docker/build-push-action action to v7 ([#205](https://github.com/Vatsim-Scandinavia/handover/issues/205)) ([f162037](https://github.com/Vatsim-Scandinavia/handover/commit/f162037d14ab223e30db249dfbd9498a88ccd5e0))
* **deps:** update docker/login-action action to v4 ([#206](https://github.com/Vatsim-Scandinavia/handover/issues/206)) ([ecde8ce](https://github.com/Vatsim-Scandinavia/handover/commit/ecde8ce2f634c21af47244f5ddbb3bb9beb71f27))
* **deps:** update docker/metadata-action action to v6 ([#207](https://github.com/Vatsim-Scandinavia/handover/issues/207)) ([f80e9f8](https://github.com/Vatsim-Scandinavia/handover/commit/f80e9f8991f4c847b1987bcb10c879e7926ca8de))
* **deps:** update docker/setup-buildx-action action to v4 ([#208](https://github.com/Vatsim-Scandinavia/handover/issues/208)) ([918c2a4](https://github.com/Vatsim-Scandinavia/handover/commit/918c2a408b5e9cfd3f8ebcb16db05d7a662fcd31))
* **deps:** update github artifact actions ([284f254](https://github.com/Vatsim-Scandinavia/handover/commit/284f254bed8befcdcaf7312fae0679e50b644416))
* **deps:** update github artifact actions (major) ([#209](https://github.com/Vatsim-Scandinavia/handover/issues/209)) ([284f254](https://github.com/Vatsim-Scandinavia/handover/commit/284f254bed8befcdcaf7312fae0679e50b644416))
* **deps:** update googleapis/release-please-action action to v5 ([#210](https://github.com/Vatsim-Scandinavia/handover/issues/210)) ([5c4e2c1](https://github.com/Vatsim-Scandinavia/handover/commit/5c4e2c18af2055ce5759e2d4809874882b1c50df))
* **deps:** update mlocati/php-extension-installer docker tag to v2.13.2 ([#197](https://github.com/Vatsim-Scandinavia/handover/issues/197)) ([ff12e0b](https://github.com/Vatsim-Scandinavia/handover/commit/ff12e0ba07b6503e340a9cc87f187d35c526d027))
* **deps:** update mysql docker tag to v26 ([#211](https://github.com/Vatsim-Scandinavia/handover/issues/211)) ([299760e](https://github.com/Vatsim-Scandinavia/handover/commit/299760ecfb4f16e48dec22d23af9295689f4f596))
* **deps:** update mysql docker tag to v8.4.13 ([#185](https://github.com/Vatsim-Scandinavia/handover/issues/185)) ([ec173a7](https://github.com/Vatsim-Scandinavia/handover/commit/ec173a7b7c22a8b0ef99377a6954d6ac3d6759c5))
* **deps:** update node.js to v26 ([#212](https://github.com/Vatsim-Scandinavia/handover/issues/212)) ([97ac238](https://github.com/Vatsim-Scandinavia/handover/commit/97ac238e201f73d9b034246137eef3e05372c0c4))

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
