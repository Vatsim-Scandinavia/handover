# Handover

VATSCA's identity provider. Members sign in through VATSIM; other VATSCA services sign them in through Handover and read their details and group memberships.

## Language

### Groups

**Group id**:
The permanent identifier of a group. Never changes, so it is what relying parties should key on.
_Avoid_: group key, group uuid

**Group slug**:
A human-readable handle for a group. Admins may rename it at any time, so nothing outside Handover should depend on it.
_Avoid_: group name, group identifier

**Effective membership**:
Every group a member belongs to, directly or through a group below it in the hierarchy. It is what relying parties see and what required groups check.
_Avoid_: all groups, inherited groups

### OAuth

**Relying party**:
A service that signs members in through Handover, such as vcas-ok or Control Center.
_Avoid_: consumer, client app, integration

**Client**:
A relying party's registration in Handover: its id, secret, redirect URIs and grant types.
_Avoid_: app, application

**Confidential client**:
A client that holds a secret, because it runs on a server the relying party controls.
_Avoid_: private client, backend client

**Public client**:
A client with no secret, because it runs in the member's browser where a secret can't be kept. It must prove each sign-in with PKCE.
_Avoid_: SPA client, browser client

**Allowed origin**:
A web origin from which a public client may call Handover's token and userinfo endpoints.
_Avoid_: CORS domain, allowed host

**Legacy client**:
A client registered before clients got UUIDs. It keeps its integer id and the grant types it had then.
_Avoid_: old client, v1 client

**Client policy**:
Rules Handover enforces for one client beyond what OAuth itself defines, such as which scopes it may request and who may sign in to it. Every client has one.
_Avoid_: client settings, client config

**Required group**:
A group whose members may sign in to a client. A client with no required groups admits every member.
_Avoid_: allowed group, access group

**Post-logout redirect URI**:
A URI a client registers so Handover may send the member back to it after logout. Handover redirects only to an exact match.
_Avoid_: logout URL, return URL

### Sessions

**Handover session**:
A member's signed-in state in Handover itself, separate from any relying party's session and from the VATSIM SSO session. Logout ends only this.
_Avoid_: SSO session, login
