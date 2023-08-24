# Configuring

Table of Contents
- [Environment](#environment)
  - [Required](#required)
  - [Optional: Theming](#optional-theming)
  - [Optional: Extras](#optional-extras)

## Environment

Here is a list over all environment variables you may tweak. You may start the container with `docker-compose.yaml` from the root folder, but fill it out with the required variables first.

### Required

Table with all the variables, default value and explanation. Override the environment variable to change the value if the default value does not fit your needs.

| Variable | Default value | Explanation |
| ------- | --- | --- |
| APP_NAME | Handover | Name of your subdivision |
| APP_OWNER | Subdivision Name | Name of your subdivision |
| APP_OWNER_SHORT | SCA | Usually 3 letter name identifying your vACC within VATSIM API |
| APP_OWNER_CONTACT | webmaster@yourvacc.com | E-mail address of your person for ban disputes |
| APP_DPP_URL | https://vatsim.net | URL to your Data Protection Policy |
| APP_DPP_DATE | 2019-10-10 | Date of your Data Protection Policy |
| APP_DPO_MAIL | webmaster@vatsim.net | E-mail address of your Data Protection Officer |
| APP_URL | http://localhost | URL to your Handover |
| APP_ENV | production | Environment of your Handover |
| DB_CONNECTION | mysql | Database connection type |
| DB_HOST | localhost | Database host |
| DB_PORT | 3306 | Database port |
| DB_DATABASE | handover | Database name |
| DB_USERNAME | root | Database username |
| DB_PASSWORD | root | Database password |
| DB_TABLE_PREFIX | null | Database table prefix |
| VATSIM_OAUTH_BASE | https://auth.vatsim.net | OAuth URL of VATSIM Connect |
| VATSIM_OAUTH_CLIENT | null | OAuth ID of your subdivision |
| VATSIM_OAUTH_SECRET | null | OAuth secret of your subdivision |


### Optional: Theming (TODO)

To change the logo to yours, bind your logo image files to `public/images/logos` and change the following variables:

| Variable | Default value | Explanation |
| ------- | --- | --- |
| APP_LOGO | vatsca.svg | The logo of your subdivision, located in `public/images/logos` |
| APP_LOGO_MAIL | vatsca-email.png | The logo of your subdivision, located in `public/images/logos` |

To change the colors of your Control Center, change the following variables and run `npm run prod` in the container to rebuild.

| Variable | Default value | Explanation |
| ------- | --- | --- |
| BOOTSTRAP_COLOR_PRIMARY | #1a475f | Primary color of your theme |
| BOOTSTRAP_COLOR_SECONDARY | #484b4c | Secondary color of your theme |
| BOOTSTRAP_COLOR_TERTIARY | #011328 | Tertiary color of your theme |
| BOOTSTRAP_COLOR_INFO | #17a2b8 | Info color of your theme |
| BOOTSTRAP_COLOR_SUCCESS | #41826e | Success color of your theme |
| BOOTSTRAP_COLOR_WARNING | #ff9800 | Warning color of your theme |
| BOOTSTRAP_COLOR_DANGER | #b63f3f | Danger color of your theme |
| BOOTSTRAP_BORDER_RADIUS | 2px | Border radius of your theme |


### Optional: Extras

| Variable | Default value | Explanation |
| ------- | --- | --- 
| APP_DEBUG | false | Toggle debug mode of your Control Center |
| DEBUGBAR_ENABLED | false | Toggle debug bar of your Control Center |
| SESSION_LIFETIME | 120 | Session lifetime in minutes, forces a new login when passed |
| SENTRY_LARAVEL_DSN | null | The Sentry DSN |
| SENTRY_TRACES_SAMPLE_RATE | 0.1 | The Sentry sample rate |