<p align="center">
    <a href="README.md"><img src="https://img.shields.io/badge/LANG-English-blue"></a>
    <a href="README_cn.md"><img src="https://img.shields.io/badge/语言-简体中文-red"></a>
</p>

# CS2 WeaponPaints Loadout Manager

> A bilingual, Steamless loadout manager for private CS2 community servers.

**No Steam login is required.** Players select or create a loadout with a Steam64 ID, then configure items directly from the website.

This project is intended for private servers and trusted player groups. It is not a complete public user-account system.

## Interface

<p align="center">
    <img src="./preview/img/1.png" width="45%">
    <img src="./preview/img/2.png" width="45%">
</p>

<p align="center">
    <img src="./preview/img/3.png" width="45%">
    <img src="./preview/img/4.png" width="45%">
</p>

<p align="center">
    <img src="./preview/img/5.png" width="45%">
    <img src="./preview/img/6.png" width="45%">
</p>

## Features

* Steam64 ID-based loadouts without Steam login
* Global, T-side, and CT-side editing modes
* Weapons, knives, gloves, agents, music kits, CS2 collectible pins, and weapon keychains
* Wear, pattern template, name tag, StatTrak™ status, and StatTrak™ kill count
* Five sticker slots per weapon, with fill-all, clear-all, and per-slot wear/position/scale/rotation settings
* Keychain pattern template and X/Y offset settings
* Searchable skin, sticker, keychain, music kit, and collectible pin pickers
* Experimental skin fusion for applying a paint kit to a different weapon or knife
* Optional website password and per-loadout PIN protection
* A top-right Connect to Server button that launches Steam directly or copies a password-protected console command
* Administrator mode for unrestricted loadout management, deletion, and visitor-facing site settings
* English and Simplified Chinese UI
* Browser-switchable light and dark themes with local preference persistence and fallback images

## Requirements

* PHP 8.0 or newer with Session and PDO MySQL support
* MySQL or MariaDB
* A working CS2 server with [WeaponPaints](https://github.com/Nereziel/cs2-WeaponPaints) connected to the same database

Enabling PHP cURL and mbstring is recommended. The database account should have `SELECT`, `INSERT`, `UPDATE`, `DELETE`, `CREATE`, and `ALTER` permissions.

## Installation

1. Copy the project into the document root or a website directory configured by your web server.

2. Edit `config.php`:

   ```php
   <?php
   define('DEFAULT_LANGUAGE', 'en'); // Available values: en, zh-CN
   define('DEFAULT_WEB_THEME', 'dark'); // Available values: dark, light; visitors can switch it in the browser
   define('SITE_NAME_EN', 'CS2 Loadout Manager'); // English name and fallback
   define('SITE_NAME_ZH_CN', 'CS2 饰品管理器'); // Simplified Chinese name
   define('AUTH_RATE_LIMIT_ATTEMPTS', 5); // Failed attempts allowed within the time window
   define('AUTH_RATE_LIMIT_WINDOW_SECONDS', 1800); // Failure tracking window in seconds
   define('AUTH_RATE_LIMIT_LOCK_SECONDS', 60); // Lock duration in seconds
   define('ENABLE_SKIN_FUSION', true); // Allow cross-weapon paint combinations

   define('SERVER_ADDRESS', ''); // Enter a hostname or IP with port; leave empty to hide the Connect to Server button
   define('SERVER_PASSWORD', ''); // Leave empty to launch CS2 directly; a value copies the console command instead
   define('SITE_ACCESS_PASSWORD', ''); // Set a password to enable access protection
   define('ADMIN_PASSWORD', ''); // Leave empty to disable administrator mode

   define('DB_HOST', '127.0.0.1');
   define('DB_PORT', '3306');
   define('DB_NAME', 'your_db_name');
   define('DB_USER', 'your_db_user');
   define('DB_PASS', 'your_db_password');
   ```

3. Open the website:

   ```text
   http://your-server/your-folder/
   ```

The website automatically creates its helper tables and adds missing helper columns when the configured database account has `CREATE` and `ALTER` permissions.

## Using the Website

1. Create a loadout with a Steam64 ID and an optional nickname.
2. Optionally enable a loadout PIN during creation.
3. Open the loadout and choose Global, T, or CT editing.
4. Select the desired items and use **Edit** for wear, pattern, StatTrak™, name tag, stickers, and keychain settings.
5. Use **Save** in an edit dialog to apply its settings. Sticker selection and sticker advanced settings use their own save flow.

Global mode writes supported team-based settings to both T and CT. Music kits are managed globally, while agents are selected separately for T and CT.

The creation form includes a help button beside **Steam64 ID**. A standard Steam profile URL contains the numeric Steam64 ID directly; custom profile URLs can be resolved using the third-party link shown in the help dialog. The resolver URL and label are translation fields in `class/translations.php`, so they can be replaced without changing the dialog markup.

### Skin Fusion (Experimental)

Set `ENABLE_SKIN_FUSION` to `true`, or enable fusion from the administrator's **Site Settings**, then open a weapon's **Skin** picker and choose **Fusion Finish** to apply another paint kit to the current weapon or knife. The actual result depends on the installed WeaponPaints version and CS2 behavior.

### Loadout PINs

* A protected loadout asks for its PIN before opening.
* Successful verification is remembered for the current browser session.
* The PIN can be changed or disabled from the loadout's Basic Information section.
* PINs are stored as password hashes and cannot be read back as plain text.
* An unprotected loadout can be edited, and a visitor can enable a PIN for it. Use PIN protection before sharing the website.

### Administrator Mode

Set `ADMIN_PASSWORD` in `config.php` to enable the administrator button. An administrator can bypass loadout PINs, edit any loadout, change or clear its PIN, and delete loadouts.

After entering administrator mode, open the administrator button again to change the English and Chinese site names, default language, default theme, fusion setting, server address, and server password. The validated values are written directly to the corresponding allowlisted constants in `config.php`. The default language only affects visitors without a language preference, and the default theme does not override a theme saved in the visitor's browser.

**Loadouts can only be deleted in administrator mode.**

`SITE_ACCESS_PASSWORD` remains the first protection layer and is independent of administrator mode and loadout PINs.

## Updating CS2 Data

The updater refreshes skins, gloves, agents, music kits, stickers, keychains, collectible pins, and the fusion paint-kit catalog from [ByMykel/CSGO-API](https://github.com/ByMykel/CSGO-API). The generated `data/paint_kits_en.json` and `data/paint_kits_zh-CN.json` files preserve paint-kit source names and images used by the fusion picker.

### First Run

Right-click the project folder and copy its path. Open Command Prompt or PowerShell, then change to the copied folder path:

```shell
cd "PROJECT FOLDER PATH"
```

Run the full update:

```bash
php tools/update_cs2_data.php
```

By default, the updater clears the relevant source caches and downloads a complete fresh data set. If an update is interrupted, resume it and reuse successfully downloaded cache files with:

```bash
php tools/update_cs2_data.php --resume
```

Preview without writing output files:

```bash
php tools/update_cs2_data.php --dry-run
```

Update only skins, gloves, and the fusion paint-kit catalog:

```bash
php tools/update_cs2_data.php --only=skins
```

Downloaded source files are cached in `data/.source_cache/`. A normal run clears the relevant caches before downloading; `--resume` reuses valid caches and downloads only missing or invalid sources. Existing output files are backed up in `data/backups/` before replacement.

If GitHub returns HTTP 429, wait before retrying, then use `--resume` to continue without downloading completed sources again. Failed downloads do not overwrite generated data files.

## Database

The website uses the existing WeaponPaints tables, including:

* `wp_player_skins`
* `wp_player_knife`
* `wp_player_gloves`
* `wp_player_agents`
* `wp_player_music`
* `wp_player_pins`

It also creates:

* `wp_presets` for the loadout list, nicknames, and hashed loadout PINs
* `wp_skin_settings_cache` for remembering each skin's wear, pattern, StatTrak™, name tag, stickers, and keychain settings

## Security

Use HTTPS when enabling passwords or PINs. Website-password, administrator-password, and loadout-PIN failures are rate limited by client IP. By default, five failures within 30 minutes cause a one-minute lock; these values can be changed with the `AUTH_RATE_LIMIT_*` settings in `config.php`.

State-changing requests are protected by CSRF token validation.

This project is designed for private or trusted environments. Removing Steam authentication makes access convenient, but it does not provide identity verification.

## Credits

* [Nereziel/cs2-WeaponPaints](https://github.com/Nereziel/cs2-WeaponPaints) for the WeaponPaints plugin and original web workflow
* [ByMykel/CSGO-API](https://github.com/ByMykel/CSGO-API) for the CS2 item data used by the updater
