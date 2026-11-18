# Contribute

Contributions help everyone move Handover forward with fixes and features.
Fork the [repository](https://github.com/Vatsim-Scandinavia/handover) on GitHub, so you can open pull requests back to the main project.

## Conventions

- Name branches `feat/name-here` for features and `fix/name-here` for fixes, for example `feat/new-api` or `fix/login-bug`.
- The service language is UK English. This applies to the interface and to this documentation. Keep established technical terms as they are, such as the OAuth "authorization code" grant.

## Documentation

This site is built with [MkDocs](https://www.mkdocs.org/) and [Material for MkDocs](https://squidfunk.github.io/mkdocs-material/), and versioned with [mike](https://github.com/jimporter/mike).
The sources live in the `docs/` folder, and CI publishes them to the `gh-pages` branch.

Install [PDM](https://pdm-project.org/), for example the version pinned in `mise.toml` with `mise install`, then install the dependencies:

```sh
cd docs
pdm install
```

### Live preview

For quick edits, serve the current sources with live reload on [`localhost:8000`](http://localhost:8000/):

```sh
pdm run docs:live
```

### Versioned preview

To see the site as it is published, with the version selector, use two terminals in the `docs/` folder.
In the first, start mike's server on [`localhost:8000/dev/`](http://localhost:8000/dev/):

```sh
pdm run docs:serve
```

In the second, build the `dev` version into your local `gh-pages` branch:

```sh
pdm run docs:build
```

Re-run `pdm run docs:build` after each change. It only updates `/dev/`, and it never pushes.

### Check before you push

CI builds the site in strict mode, which fails on any warning. Run the same check locally:

```sh
pdm run docs:check
```

The output goes to `docs_site/`, which is ignored.
