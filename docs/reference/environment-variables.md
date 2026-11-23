# Environment variables

Configure Handover through environment variables on the container.
The repository's `docker-compose.yaml` lists the ones most installations need.
Override a variable when its default does not fit.

## Required

| Variable | Default value | Explanation |
| -------- | ------------- | ----------- |
| `APP_OWNER` | `VATSIM` | Full name of your division, shown in the footer as the one who runs your Handover |
| `APP_OWNER_SHORT` | `SCA` | Short name of your division, usually 3 letters, shown to banned users |
| `APP_OWNER_CONTACT` | `webmaster@vatsim.net` | E-mail address members contact for help. Shown on error pages, and to banned users to dispute a ban |
| `APP_DPP_URL` | `https://vatsim.net` | URL to your full [Data Protection Policy](../setup/dpp.md) |
| `APP_DPP_DATE` | `2019-10-10` | Date of your Data Protection Policy, shown as free text |
| `APP_DPO_MAIL` | `webmaster@vatsim.net` | E-mail address of your Data Protection Officer |
| `APP_URL` | `http://localhost` | Public URL of your Handover |
| `APP_ENV` | `production` | Environment of your Handover |
| `APP_KEY` | none | Application key, see [installation](../installation.md#setup) |
| `DB_CONNECTION` | `mysql` | Database connection type |
| `DB_HOST` | `127.0.0.1` | Database host |
| `DB_PORT` | `3306` | Database port |
| `DB_DATABASE` | `handover` | Database name |
| `DB_USERNAME` | `root` | Database username |
| `DB_PASSWORD` | `root` | Database password |
| `DB_TABLE_PREFIX` | empty | Prefix for all database table names |
| `VATSIM_OAUTH_BASE` | `https://auth.vatsim.net` | Base URL of [VATSIM Connect](../integrations/vatsim-connect.md) |
| `VATSIM_OAUTH_CLIENT` | none | OAuth client ID of your division at VATSIM Connect |
| `VATSIM_OAUTH_SECRET` | none | OAuth client secret of your division at VATSIM Connect |

## Optional: Theming

See [customising the theme](../setup/theme.md) for how to apply these.

| Variable | Default value | Explanation |
| -------- | ------------- | ----------- |
| `APP_LOGO` | `vatsca.svg` | File name of your logo in `/app/public/images/logos` |
| `APP_TAGLINE` | `Centralised Login Service` | Short description shown beneath the name on the front page |

## Optional: Extras

| Variable | Default value | Explanation |
| -------- | ------------- | ----------- |
| `APP_DEBUG` | `false` | Show detailed error pages. Never enable in production |
| `APP_OWNER_SUPPORT` | none | HTML telling members where to get help, shown on the 403, 500 and 503 error pages instead of the line pointing to `APP_OWNER_CONTACT`. See [support on error pages](#support-on-error-pages) |
| `VATSIM_OAUTH_SCOPES` | `full_name,email,vatsim_details,country` | Comma-separated scopes Handover requests from VATSIM Connect |
| `SESSION_LIFETIME` | `360` | Session lifetime in minutes. Users must log in again when it passes |
| `SENTRY_LARAVEL_DSN` | none | The Sentry DSN. Falls back to `SENTRY_DSN` |
| `SENTRY_TRACES_SAMPLE_RATE` | `0.0` | The Sentry traces sample rate |

## Support on error pages

Error pages for 403, 500 and 503 end with a line telling members where to get help.
By default it points to `APP_OWNER_CONTACT`.
Set `APP_OWNER_SUPPORT` to replace it with your own HTML, for example a link to your helpdesk or Discord:

```yaml
environment:
  - 'APP_OWNER_SUPPORT=Ask for help in <a href="https://discord.gg/example">#support on our Discord</a>.'
```

Handover shows the HTML as it is, without escaping, so only put HTML you trust here.
