# Background jobs

Handover relies on Laravel's scheduler for recurring work.
Run the scheduler every minute from a crontab *outside* the container:

--8<-- "schedule-cron.md"

## Scheduled jobs

| Job | Runs | What it does |
| --- | ---- | ------------ |
| `member:data` | Daily | Fetches fresh member data from VATSIM Connect |
| `passport:purge` | Daily | Removes revoked tokens and tokens that expired more than seven days ago |

## Member data

For every member with stored VATSIM Connect tokens, `member:data`:

1. Refreshes the VATSIM Connect token if it has expired.
2. Fetches the member from VATSIM Connect.
3. Updates name, e-mail, ratings, country, region, division and subdivision.

If the token refresh fails, Handover deletes the stored tokens.
The member's data then stays as it is until the next login.
