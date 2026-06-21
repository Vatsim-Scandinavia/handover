# Groups

The groups system is a general-purpose membership and delegation layer. Administrators define named groups, assign users to them, and write rules that let certain groups manage the membership of others. Client applications can request the `groups` OAuth scope to receive a user's group memberships via `GET /api/user`.

---

## How it works

A group has a slug, an optional description, tags, and attribute values. Tags are free-form strings; attributes are key/value pairs drawn from a global schema of attribute definitions (e.g. `region`, `division`). Any number of groups can be marked as admin groups — members of those groups have full system access.

Manager rules control delegation. A rule says that members of one group (the manager group) can manage the membership of other groups. Rules can match by a specific target group, by tag (any group carrying a given tag), or by attribute value (any group whose attribute matches a key/value pair). Tag and attribute rules are global: they match every group that satisfies the pattern, including ones created in the future.

Manager access is resolved at request time by checking whether any rule points at the requested group through the user's own memberships. The result is cached per user with a 5-minute TTL and invalidated whenever memberships or rules change.

---

## Access

There are three access levels. System administrators (members of any `is_admin_group` group) have full CRUD over groups, memberships, rules, and attribute definitions. Group managers can add and remove members of the groups their rules cover, but cannot edit group metadata or rules. Everyone else can view their own memberships via the API.

The web interface lives under `/groups`. Managers see only the groups they can manage; admins see everything. A global rules overview at `/groups/rules` shows every delegation rule across all groups in one place.

---

## API

Requesting the `groups` scope appends a `groups` array to the `/api/user` response:

```json
{
  "cid": 1234567,
  "personal": { "...": "..." },
  "groups": [
    {
      "id": "018f1e2a-7c3d-7000-8000-abcdef012345",
      "slug": "vacc-norway",
      "name": "vACC Norway",
      "tags": ["vacc", "eur"],
      "attributes": {
        "region": "EUR",
        "division": "EUD"
      }
    }
  ]
}
```

Use `id` (UUIDv7) as the stable canonical reference in client applications. `slug` is human-readable but not guaranteed to stay the same. Tags and attributes carry no API stability contract.

---

## Use-cases

To gate access in a client application, create a group, add members, and check `groups[*].id` in the API response. No hard-coded roles required.

To delegate roster management without granting full admin access, create a group for the managers (e.g. "vACC Directors") and a tag-match rule granting it management of every group tagged `vacc`. Any future group with that tag is automatically covered.

To manage a whole region or division uniformly, assign a shared attribute (e.g. `region = EUR`) to the relevant groups and write one attribute-match rule. The managing group gains access to all matching groups past and future.

Manager rules can be mutual or self-referential, which supports federated governance patterns where groups co-administer each other.

To bootstrap the first administrator, run `php artisan groups:add-admin {cid}`. This finds or creates a `system-administrators` admin group and adds the user to it.
