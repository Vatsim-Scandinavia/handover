# Customising the theme

You can change the logo, the colours, the header text, and the simplified Data Protection Policy.

## Logo

Bind your logo files to `/app/public/images/logos` and set [`APP_LOGO`](../reference/environment-variables.md#optional-theming) to the file name.

## Colours

Handover compiles its colours into the frontend at build time.
Set the `VITE_THEME_*` [environment variables](../reference/environment-variables.md#optional-theming), then rebuild the frontend inside the container:

```sh
docker exec -it handover sh container/theme/build.sh
```

If the build fails with an error like `"#5f271a" is not a color`, remove the `"` quotes around the value of your environment variable.

!!! note
    - The script installs Node.js inside the container, builds the frontend, and removes Node.js again. The container still grows by about 200 MB.
    - Run the script again each time you recreate the container.

!!! tip "Renamed variables"
    Older versions of this guide used `BOOTSTRAP_COLOR_*` names.
    Handover reads `VITE_THEME_*` only, so rename any old variables.

## Header text

To change the header text, bind your own Blade **file** to `/app/resources/views/layouts/header.blade.php`.
Start from a copy of the original file and edit it.

## Simplified Data Protection Policy

Before a member's data is stored, Handover shows a short summary of your Data Protection Policy as icons and bullet points.
To change it, bind your own Blade **file** to `/app/resources/views/parts/dpp-bullets.blade.php`.
Start from a copy of the original file and edit it.
