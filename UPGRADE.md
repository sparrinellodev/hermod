# Upgrade Guide: v1.x to v2.0.0

Laravel-WAMP v2.0.0 includes a breaking change regarding the naming convention of the configuration file to better adhere to Laravel best practices.

---

## Breaking Changes Summary

1. **Configuration File Rename:** `config/hermod.php` is now `config/wamp.php`.
2. **Publish Tag Rename:** `--tag=hermod-config` is now `--tag=wamp-config`.
3. **Helper/Config Access:** Accessing configuration via `config('hermod.*')` now uses `config('wamp.*')`.

---

## Migration Steps

### Step 1: Rename or Re-publish Config File
In your Laravel application, either rename the existing configuration file:

```bash
mv config/hermod.php config/wamp.php
```

Or re-publish the new configuration file:

```bash
php artisan vendor:publish --tag=wamp-config
```

**Note:** If you choose to re-publish, remember to transfer your custom connection settings from config/hermod.php to config/wamp.php and then delete config/hermod.php.

### Step 2: Update Code Calls (If Applicable)
If your application code explicitly reads configuration values via the config() helper:

```php
// Old (v1.x)
$host = config('hermod.connections.default.url');

// New (v2.0.0)
$host = config('wamp.connections.default.url');
```

### Step 3: Clear Configuration Cache
Clear the configuration cache to ensure Laravel loads the new file:

```bash
php artisan config:clear
```
