# MVS (Modern Vendor System)

MVS is a Laravel and Livewire-based POS and order management system tailored for a small Chinese grocery business. It is designed to help with product listing and pricing, employee management, delivery assignment, order tracking, and day-to-day store operations.

## What This System Is For

This project is mainly tailored for:

- Small grocery or mini-mart stores
- Chinese grocery businesses that need a simple POS workflow
- Order tracking and delivery coordination
- Staff management and operational logging
- A Laravel and Livewire learning project for improving full-stack development skills

## Requirements

Before installing, make sure you have the following:

- XAMPP installed as the default local web server stack
- PHP 8.2 or newer
- Composer
- Node.js and npm
- Laravel dependencies installed through Composer

If you prefer another local stack, you can use it, but XAMPP is the recommended default for this project.

## Installation

1. Copy or clone this project into your web server directory.
   - Example: `C:/xampp/htdocs/MVS`

2. Open a terminal in the project folder.

3. Run the setup command:

```bash
composer run setup
```

This command will:

- Install PHP dependencies
- Create the `.env` file if it does not exist
- Generate the application key
- Run database migrations
- Create the storage link
- Install frontend dependencies
- Build the frontend assets

4. Start your local server.
   - If you are using XAMPP, start Apache and MySQL from the XAMPP Control Panel.

5. Run XAMPP with Administrator permission.
	- Right-click `xampp-control.exe` and select the "Compatibility" tab then check **Run as administrator**.
    - Click Apply then OK then Close
	- This helps Apache and MySQL start correctly, especially when editing hosts files, binding to port 80, or updating Apache config.

6. Open the application in your browser.
   - If you are using the default XAMPP setup, you can usually access it through your configured virtual host or local domain.

## Basic Usage

After installation, the system can be used for:

- Adding and managing products
- Viewing and updating product prices
- Managing employees
- Creating and tracking orders
- Assigning deliveries
- Monitoring store activity and logs

## Environment Configuration (.env)

After running `composer run setup`, open your `.env` file and review these key settings.

### App Settings

- `APP_NAME`: Display name of your application
- `APP_ENV`: Environment mode (`production`, `local`, etc.)
- `APP_DEBUG`: Should be `false` in production, `true` during local debugging
- `APP_URL`: Base URL of the app
	- Example for vhost: `http://mgm.store`
	- Example for LAN access: `http://192.168.1.20`

### Database Settings

- `DB_CONNECTION`: Database driver (`mysql` by default)
- `DB_HOST`: Database host (usually `127.0.0.1` for XAMPP)
- `DB_PORT`: MySQL port (usually `3306`)
- `DB_DATABASE`: Database name you created for MVS
- `DB_USERNAME`: MySQL username (often `root` in local XAMPP)
- `DB_PASSWORD`: MySQL password (blank by default on many local XAMPP installs)

### Session / Queue / Cache

- `SESSION_DRIVER=database`: Stores session data in DB tables
- `QUEUE_CONNECTION=database`: Uses DB queue driver
- `CACHE_STORE=database`: Uses DB-backed cache

If these are set to `database`, make sure migrations run successfully so required tables exist.

### Store Config (Project-Specific)

From `.env.example`, these values control business defaults:

- `STORE_NAME`: Primary store name
- `STORE_NAME_ALT`: Alternate store name
- `STORE_ADDRESS`: Store address shown in the system
- `STORE_DEFAULT_ORDER_TYPE`: Default order type (`walk_in` or `deliver`)
- `STORE_DEFAULT_PAYMENT_TYPE`: Default payment type (`cash` or `gcash`)
- `ORDER_EDIT_LOCK_STATUS`: Comma-separated order statuses where editing should be locked
	- Example: `in_transit,delivered,completed,cancelled`

### Recommended First `.env` Edits

1. Set `APP_URL` to your virtual host or LAN URL.
2. Set `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` to your local DB credentials.
3. Set `STORE_NAME`, `STORE_NAME_ALT`, and `STORE_ADDRESS` to your actual store values.
4. Re-run migrations if needed:

```bash
php artisan migrate --force
```

## LAN Hosting Guide

If you want to host MVS on your local network, configure Apache virtual hosts in XAMPP.

### 1. Open Apache Configuration

Navigate to:

```text
C:\xampp\apache\conf\httpd.conf
```

Search for:

```text
Listen 80
```

Add this line directly below it:

```text
NameVirtualHost *:80
```

### 2. Add Virtual Hosts

Open:

```text
C:\xampp\apache\conf\extra\httpd-vhosts.conf
```

Add the following configuration and update the project path and LAN IP address to match your machine:

```apache
<VirtualHost *:80>
	ServerName mgm.store
	DocumentRoot "C:/xampp/htdocs/MVS/public"

	<Directory "C:/xampp/htdocs/MVS/public">
		AllowOverride All
		Require all granted
	</Directory>
</VirtualHost>

<VirtualHost *:80>
	ServerName localhost
	DocumentRoot "C:/xampp/htdocs"

	<Directory "C:/xampp/htdocs">
		AllowOverride All
		Require all granted
	</Directory>
</VirtualHost>

<VirtualHost *:80>
	ServerName 192.168.1.20
	DocumentRoot "C:/xampp/htdocs/MVS/public"

	<Directory "C:/xampp/htdocs/MVS/public">
		AllowOverride All
		Require all granted
	</Directory>
</VirtualHost>
```

### 3. Make Sure Apache Can Read Virtual Hosts

In `httpd.conf`, make sure this line is enabled:

```text
Include conf/extra/httpd-vhosts.conf
```

### 4. Restart XAMPP

Restart Apache from the XAMPP Control Panel after saving the configuration.

### 5. Access the App Over LAN

- From the host PC, open `http://mgm.store` or the configured local address.
- From another device on the same network, use the LAN IP address shown on your PC, such as `http://192.168.1.20`.

If you want to use `mgm.store` on other devices, add a hosts entry or configure local DNS so the domain points to your server PC.

## Notes

- If migrations fail, confirm your database credentials in `.env`.
- If assets are missing, rerun `npm install` and `npm run build`.
- If the site is not reachable on LAN, check Windows Firewall and Apache port 80 access.
