# Customising the theme

You can change the logo, the colours, the front page heading, and the simplified Data Protection Policy.

## Themes

Handover ships a light and a dark theme.
By default it follows the operating system.
A signed-in member can switch between system, light and dark with the toggle next to their name.
The choice is kept in the browser only, so it does not follow the member to another device.

Both themes are built from the same set of CSS custom properties, so anything you override below applies in either mode.

## Logo

Bind your logo files to `/app/public/images/logos` and set [`APP_LOGO`](../reference/environment-variables.md#optional-theming) to the file name.

## Colours

To brand your instance, put your overrides in `resources/sass/themes/_custom.scss`.
That file is git-ignored, so your styles stay out of version control while still compiling into the regular front-end build.

### 1. Create the override file

Copy the example to get started:

```sh
cp resources/sass/themes/_custom.scss.example resources/sass/themes/_custom.scss
```

### 2. Edit your colours

Edit `resources/sass/themes/_custom.scss`. Override only what you need.
Anything left out keeps its default.

Reference for the available values:

- `resources/sass/themes/_light.scss`: CSS custom properties for the light theme
- `resources/sass/themes/_dark.scss`: CSS custom properties for the dark theme

### 3. Rebuild the assets

The CSS is compiled ahead of time, so the override only takes effect once the front-end is rebuilt.
How you do that depends on how you run Handover.

=== "Without a container"

    ```sh
    npm run build
    ```

=== "With a container"

    Build your own image with the override baked in.
    See [custom container image](custom.md#custom-theme).

!!! warning "Don't build inside a running container"
    The published image contains no Node toolchain, and anything compiled into a running container is lost the moment it is recreated.
    Either bake the theme into an image you build yourself, or run Handover without a container.

## Front page heading

The front page heading always says Handover.
Set [`APP_TAGLINE`](../reference/environment-variables.md#optional-theming) to describe your instance beneath it.

The footer says your Handover is run by [`APP_OWNER`](../reference/environment-variables.md#required), and credits Handover to VATSIM Scandinavia.
The Handover version shows on the management pages only.

To change more than the text, bind your own Blade **file** to `/app/resources/views/layouts/header.blade.php`.
Start from a copy of the original file and edit it.

## Simplified Data Protection Policy

Before a member's data is stored, Handover shows a short summary of your Data Protection Policy as icons and bullet points.
To change it, bind your own Blade **file** to `/app/resources/views/parts/dpp-bullets.blade.php`.
Start from a copy of the original file and edit it.

## Troubleshooting

If the colours don't change, rebuild the assets and reload the page without the cache.
Check that you set each colour in both the light and the dark block of `_custom.scss`, so it applies in either mode.
