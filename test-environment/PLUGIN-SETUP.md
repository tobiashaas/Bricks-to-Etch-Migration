# Plugin & Theme Setup Guide

**Updated:** 2025-10-24 00:40

This guide explains how to provide the proprietary plugin and theme archives required by the Bricks → Etch migration test environment.

## Required ZIP Archives

Place the following files in the indicated folders *before* running `npm run dev`:

### Plugins (`test-environment/plugins/`)

1. `bricks.2.1.2.zip` – Bricks Builder (source environment)
2. `frames-1.5.11.zip` – Frames library for Bricks
3. `automatic.css-3.3.5.zip` – Automatic.css package for Bricks
4. `etch-1.0.0-alpha-5.zip` – Etch migration plugin
5. `automatic.css-4.0.0-dev-27.zip` – Automatic.css build for Etch

### Themes (`test-environment/themes/`)

1. `bricks-child.zip` – Bricks child theme (source)
2. `etch-theme-0.0.2.zip` – Etch theme (target)

> ⚠️ **File names must match exactly**. wp-env installs the ZIPs based on their filenames.

## Download Sources

- **Bricks Builder:** https://bricksbuilder.io/
- **Frames:** https://frames.bricksbuilder.io/
- **Automatic.css:** https://automaticcss.com/
- **Etch Theme & Plugin:** https://etchtheme.com/

Ensure you have valid licenses where required.

## Installation Steps

1. Copy each ZIP into the appropriate folder (`plugins/` or `themes/`).
2. Run `npm run dev` from the plugin root. wp-env automatically extracts and installs the ZIPs into the corresponding WordPress instance.
3. During setup an application password is created on the Etch site and all plugins/themes are activated.

## Verification

After `npm run dev` completes:

```bash
npm run wp plugin list
npm run wp:etch plugin list
```

Look for the Bricks to Etch plugin plus the proprietary packages in the `active` state. To check active themes:

```bash
npm run wp theme status
npm run wp:etch theme status
```

## Manual Activation

If a plugin/theme failed to activate automatically, run:

```bash
npm run activate
```

This script attempts to activate all required packages on both instances. Alternatively use the WordPress admin UI.

## License Activation

Bricks Builder and Automatic.css typically require license keys. Enter the keys in the respective admin screens after the initial setup:

- Bricks → Settings → License
- Automatic.css → Settings
- Etch Theme → License (if applicable)

## Troubleshooting

- **ZIP not found:** Confirm the filename matches the entry in `.wp-env.json` (case-sensitive).
- **Activation failed:** Run `npm run logs` to inspect wp-env output, then retry `npm run activate`.
- **Different versions:** Update `.wp-env.json` to match the filenames you placed in the folders.
- **Custom local plugins:** Add paths to `.wp-env.override.json` under the `plugins` array.

For further issues see `test-environment/README.md` or run `npm run debug` to generate a diagnostic report.
