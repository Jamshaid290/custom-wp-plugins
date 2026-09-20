# File Monitor - Real Time Security Alert

A lightweight WordPress security plugin that detects when files are added or modified inside your WordPress core and content directories, then shows a persistent admin alert with the exact file paths.

Built for site owners and developers who want an early warning when a site gets hacked or when unexpected file changes appear.

**Version:** 1.2  
**Author:** Jamshaid Khan  
**Requires:** WordPress 5.0 or higher, PHP 7.0 or higher  
**License:** GPLv2 or later

---

## What It Does

Malware, backdoors, and injected code almost always leave a trace on the filesystem. A new `.php` file appears in `wp-content/uploads`, or an existing core file gets modified. This plugin watches for exactly that.

It builds an MD5 fingerprint of every file in your key WordPress directories, stores it, and compares it on the next admin page load. If anything changed, you get a red alert in wp-admin that does not disappear until you dismiss it manually.

## Features

- Scans `wp-content`, `wp-includes`, and `wp-admin` recursively
- Detects both newly added files and modified files
- Shows the full path of every changed file, labelled as NEW or MODIFIED
- Persistent alert that stays until the administrator clears it
- Skips cache and log directories to reduce noise
- Administrator only, no settings page, no configuration needed
- No external services, no API keys, everything stays on your server

## Monitored Directories

| Directory | Why it matters |
|---|---|
| `wp-content` | Plugins, themes, and uploads. The most common place for injected backdoors. |
| `wp-includes` | WordPress core library files. Should almost never change outside an update. |
| `wp-admin` | WordPress admin core. Changes here are a strong red flag. |

Paths containing `cache` or `logs` are ignored, since those change constantly during normal operation.

## Installation

### Manual upload

1. Download or clone this repository
2. Copy the `file-monitor` folder into `wp-content/plugins/`
3. Go to **Plugins** in your WordPress admin
4. Activate **File Monitor - Real Time Security Alert**

### ZIP upload

1. Zip the `file-monitor` folder
2. Go to **Plugins > Add New > Upload Plugin**
3. Choose the ZIP, install, and activate

## How To Use

There is nothing to configure. Once activated:

1. The first admin page load builds the baseline snapshot of your files. No alert is shown at this point.
2. From then on, every admin page load compares the current state against the saved snapshot.
3. If a file is added or modified, a red alert appears at the top of every admin screen listing the changed paths.
4. Review each listed file. If the changes are expected, for example you just updated a plugin, click **Clear Alert (I have checked files)**.
5. The alert clears and monitoring continues from the new state.

### What to do when an alert fires

Do not clear the alert before you look at the files. Open each listed path and check the contents. Signs of trouble include base64 encoded strings, `eval()` calls, obfuscated one line code, and PHP files sitting inside the uploads folder. If you find something you did not put there, take a backup, remove the file, and change all your passwords.

## How It Works

The plugin hooks into `admin_init` and runs a recursive scan using `RecursiveIteratorIterator`. Each file gets hashed with `md5_file()` and the full map of path to hash is saved in the WordPress options table under `fm_file_state`.

On the next scan, the fresh map is compared against the stored one. Paths present now but missing before are flagged as NEW. Paths present in both but with a different hash are flagged as MODIFIED. The result is written to `fm_changed_files` and a flag is set in `fm_alert_active`, which drives the admin notice.

### Options used

| Option | Purpose |
|---|---|
| `fm_file_state` | The saved map of file path to MD5 hash |
| `fm_changed_files` | List of changes detected in the last scan |
| `fm_alert_active` | Boolean flag that controls whether the alert shows |

## Notes and Limitations

- **Performance.** The scan runs on every admin page load and hashes every file in the monitored directories. On large sites this adds noticeable load. Moving the scan to a WP-Cron schedule is the recommended improvement for production sites.
- **Deleted files are not reported.** Only new and modified files are detected in the current version.
- **Plugin and theme updates will trigger alerts.** This is expected behaviour. Update, review, then clear.
- **First run sets the baseline.** Any malware already present before activation becomes part of the trusted snapshot. Install on a site you believe is clean, or clean it first.

## Roadmap

- Move scanning to WP-Cron instead of `admin_init`
- Detect deleted files
- Email notification on alert
- Settings page to choose monitored directories and ignore patterns
- Nonce verification on the clear alert action
- Whitelist for known safe paths

## Changelog

### 1.2
- Persistent admin alert with full file paths
- Manual clear alert action
- Cache and logs directories excluded from scanning

## Author

**Jamshaid Khan**  
GitHub: [@Jamshaid290](https://github.com/Jamshaid290)
