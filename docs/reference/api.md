# User API

Clients fetch the logged-in member's data from one endpoint, available at two paths.
The response has the same shape as VATSIM Connect's, plus an optional `groups` array.

## Request

```http
GET /api/user
Authorization: Bearer ACCESS_TOKEN
Accept: application/json
```

`GET /api/v1/user` is an alias. It returns exactly the same response.

## Response

```json
{
  "status": "success",
  "data": {
    "cid": 1234567,
    "personal": {
      "name_first": "Jane",
      "name_last": "Doe",
      "name_full": "Jane Doe",
      "name_full_cid": "Jane Doe (1234567)",
      "email": "jane@example.org",
      "country": { "id": "NO", "name": "N/A" }
    },
    "vatsim": {
      "rating": { "id": 3, "short": "S2", "long": "Tower Controller" },
      "pilotrating": { "id": 0, "short": "P0", "long": "No Pilot Rating" },
      "region": { "id": "EMEA", "name": "N/A" },
      "division": { "id": "EUD", "name": "N/A" },
      "subdivision": { "id": "SCA", "name": "N/A" }
    },
    "oauth": { "token_valid": "true" },
    "groups": []
  }
}
```

The values above are examples.

## Fields and scopes

Every field is always present, except `groups`.
A field is `null` when the token lacks the [scope](../integrations/clients.md#scopes) for it.

| Field | Scope |
| ----- | ----- |
| `cid` | none, always set |
| `personal.name_first`, `name_last`, `name_full`, `name_full_cid` | `full_name` |
| `personal.email` | `email` |
| `personal.country.id` | `country` |
| `vatsim.rating`, `pilotrating`, `region.id`, `division.id`, `subdivision.id` | `vatsim_details` |
| `groups` | `groups`. The key is missing without it |

Notes:

- Every `name` field under `country`, `region`, `division` and `subdivision` is always `"N/A"`.
- `oauth.token_valid` is always the string `"true"`. Handover rejects invalid tokens before it builds a response.

## Groups

With the `groups` scope, `groups` lists every group the member belongs to, sorted by name.
It includes groups the member inherits through [member groups](../groups.md#member-groups-transitive-membership).

```json
{
  "id": "018f1e2a-7c3d-7000-8000-abcdef012345",
  "slug": "vacc-norway",
  "name": "vACC Norway",
  "direct": true,
  "tags": ["vacc", "eur"],
  "attributes": {
    "region": "EUR",
    "division": "EUD"
  }
}
```

| Field | Explanation |
| ----- | ----------- |
| `id` | UUIDv7. Use this as the stable reference in your service |
| `slug` | Human-readable name in URLs. It can change |
| `name` | Display name |
| `direct` | `true` for direct membership, `false` for inherited membership |
| `tags` | Free-form tags |
| `attributes` | Key/value pairs from the attribute definitions |

Tags and attributes carry no stability promise.

!!! note "Compatibility"
    Once groups have member groups, `groups` includes inherited groups.
    Clients that want only directly assigned groups should filter on `direct: true`.
