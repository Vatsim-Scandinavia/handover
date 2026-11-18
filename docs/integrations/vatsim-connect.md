# VATSIM Connect

Handover logs members in through [VATSIM Connect](https://auth.vatsim.net).
Your division needs its own OAuth client there.

## Setup

1. Create an OAuth client for your division at VATSIM Connect.
2. Set its redirect URL to `/login` on your Handover, for example `https://handover.example.org/login`.
3. Set these [environment variables](../reference/environment-variables.md):

| Variable | Explanation |
| -------- | ----------- |
| `VATSIM_OAUTH_BASE` | Base URL of VATSIM Connect, `https://auth.vatsim.net` by default |
| `VATSIM_OAUTH_CLIENT` | The client ID from VATSIM Connect |
| `VATSIM_OAUTH_SECRET` | The client secret from VATSIM Connect |
| `VATSIM_OAUTH_SCOPES` | The scopes to request, `full_name,email,vatsim_details,country` by default |

Members must grant all of these scopes. Handover refuses the login if VATSIM Connect does not return the e-mail address and VATSIM details.

## Who can log in

Handover refuses a login when the member:

- is suspended on VATSIM (rating 0),
- has an inactive VATSIM account (rating -1), or
- is banned in Handover. The member sees the reason and the `APP_OWNER_CONTACT` address.

Handover also logs out members whose stored rating is 0.

## Keeping data up to date

Handover stores the VATSIM Connect tokens of each member.
A daily job uses them to fetch fresh data from VATSIM, see [background jobs](../reference/background-jobs.md).
