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

- A local web server stack: **XAMPP** or **Laragon** (see below for which to choose)
- PHP 8.2 or newer
- Composer
- Node.js and npm
- Laravel dependencies installed through Composer

### Choosing Between XAMPP and Laragon

**XAMPP** (Recommended for beginners)
- Easier to understand and set up
- More widely used with more online tutorials available
- Slower and requires more manual configuration
- Better if you're just starting out

**Laragon** (Recommended for easier workflow)
- Faster and more modern
- Automatically handles many configurations
- Cleaner interface and easier to manage multiple projects
- Better if you want less headaches and faster setup

Both work great for MVS. Choose based on your preference—the installation steps for each are provided below.

## Installation

### Step 1: Copy the Project to Your Web Server

**If using XAMPP:**
- Copy or clone this project into: `C:/xampp/htdocs/MVS`

**If using Laragon:**
- Copy or clone this project into: `C:/laragon/www/MVS`

### Step 2: Set Up the Project

1. Open a terminal in the project folder (the MVS folder you just created).

2. Run the setup command:

```bash
composer run setup
```

This command will automatically:
- Install PHP dependencies
- Create the `.env` file if it doesn't exist
- Generate the application key
- Set up the database
- Create necessary storage folders
- Install and build frontend assets

### Step 3: Start Your Server

**If using XAMPP:**
1. Open the XAMPP Control Panel
2. Click **Start** next to Apache
3. Click **Start** next to MySQL
4. Right-click `xampp-control.exe` and select "Run as administrator" for best results

**If using Laragon:**
1. Open Laragon
2. Click **Start All**
3. Laragon handles everything automatically

### Step 4: Access Your Application

1. Open your web browser
2. Go to `http://localhost` or the address shown in your server control panel
3. You should see the MVS application load

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

## LAN Hosting Guide (Accessing MVS from Other Devices on Your Network)

Once you have MVS running on your computer, you might want to access it from other devices on your local network (like a tablet or another computer). This guide covers both XAMPP and Laragon.

### What is LAN?
**LAN** means "Local Area Network"—it's the Wi-Fi or wired network in your home or office. Hosting on LAN lets you access your application from phones, tablets, or other computers connected to the same network.

---

## Option 1: LAN Hosting with XAMPP

### Step 1: Find Your LAN IP Address

1. Open **Command Prompt** (search for "cmd" in Windows)
2. Type: `ipconfig`
3. Look for **IPv4 Address** under your network connection
4. You should see something like: `192.168.1.20` — **write this down**

### Step 2: Configure Apache

1. Open this file with Notepad:
   ```
   C:\xampp\apache\conf\httpd.conf
   ```

2. Search for the line: `Listen 80`

3. Add a new line directly below it:
   ```
   NameVirtualHost *:80
   ```

4. Search for this line:
   ```
   Require local
   ```

5. Replace it with:
   ```
   Require all granted
   ```
   *(This allows other devices to connect)*

6. Save the file

### Step 3: Configure Virtual Hosts

1. Open this file with Notepad:
   ```
   C:\xampp\apache\conf\extra\httpd-vhosts.conf
   ```

2. Add this configuration at the end (replace `192.168.1.20` with your actual IP from Step 1):

```apache
# MVS LAN Server
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

3. Save the file

### Step 4: Enable Firewall Access

1. Open **Windows Defender Firewall**
2. Click **Allow an app through firewall**
3. Find and check the boxes next to:
   - Apache HTTP Server
   - Anything related to XAMPP
4. Make sure **Private Network** is checked

### Step 5: Update Your Environment File

1. Open the `.env` file in your MVS project
2. Find the line: `APP_URL=http://localhost`
3. Change it to: `APP_URL=http://192.168.1.20` (use your actual IP)

### Step 6: Restart XAMPP

1. Open XAMPP Control Panel
2. Click **Stop** next to Apache
3. Wait a few seconds
4. Click **Start** next to Apache

### Step 7: Test Access

**On your computer:**
- Open browser and go to: `http://192.168.1.20`

**On another device (phone, tablet, another computer):**
- Make sure it's on the same Wi-Fi
- Open browser and go to: `http://192.168.1.20`
- MVS should load!

---

## Option 2: LAN Hosting with Laragon

Laragon makes this much simpler! Here's how:

### Step 1: Find Your LAN IP Address

1. Open **Command Prompt** (search for "cmd" in Windows)
2. Type: `ipconfig`
3. Look for **IPv4 Address** — write this down (e.g., `192.168.1.20`)

### Step 2: Configure Virtual Hosts

1. Right-click the **Laragon** icon in the system tray (bottom right)
2. Click **Menu** → **Apache** → **httpd-vhosts.conf**
3. A text editor will open

4. Add this configuration at the end (replace `192.168.1.20` with your IP from Step 1):

```apache
# MVS LAN Server
<VirtualHost *:80>
    ServerName mgm.store
    DocumentRoot "C:/laragon/www/MVS/public"

    <Directory "C:/laragon/www/MVS/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>

<VirtualHost *:80>
    ServerName localhost
    DocumentRoot "C:/laragon/www"

    <Directory "C:/laragon/www">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>

<VirtualHost *:80>
    ServerName 192.168.1.20
    DocumentRoot "C:/laragon/www/MVS/public"

    <Directory "C:/laragon/www/MVS/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

5. Save and close the file

### Step 3: Configure Apache Main Config

1. Right-click **Laragon** → **Menu** → **Apache** → **httpd.conf**
2. Search for: `Listen 80`
3. Add below it: `NameVirtualHost *:80`
4. Save and close

### Step 4: Update Your Environment File

1. Open the `.env` file in your MVS project
2. Find: `APP_URL=http://localhost`
3. Change to: `APP_URL=http://192.168.1.20` (use your actual IP)

### Step 5: Restart Laragon

1. Right-click **Laragon** in the system tray
2. Click **Stop All**
3. Wait a moment
4. Click **Start All**

### Step 6: Allow Through Windows Firewall

1. Open **Windows Defender Firewall**
2. Click **Allow an app through firewall**
3. Check boxes for:
   - Apache HTTP Server
   - Laragon
4. Make sure **Private Network** is checked

### Step 7: Test Access

**On your computer:**
- Open browser → go to: `http://192.168.1.20`

**On another device:**
- Same Wi-Fi network required
- Open browser → go to: `http://192.168.1.20`
- MVS should load!

### Optional: Auto-Start Laragon on Boot

1. Right-click **Laragon**
2. Click **Preferences**
3. Check:
   - "Start Laragon when Windows starts"
   - "Start All when Laragon starts"
4. Click **Save**

---

## Using a Custom Domain Name (Optional)

If you don't want to use IP addresses, you can use `mgm.store` instead:

### On Your Main Computer:

1. Open Notepad as Administrator (right-click → Run as administrator)
2. Go to **File** → **Open**
3. Navigate to: `C:\Windows\System32\drivers\etc\`
4. Open the file: `hosts` (no extension)
5. Add this line at the end:
   ```
   192.168.1.20    mgm.store
   ```
   *(Replace `192.168.1.20` with your actual IP)*
6. Save and close

### On Other Devices (More Complex):

This requires DNS setup on your network. For now, using the IP address is simpler.

### Access:

- On your computer: `http://mgm.store`
- On other devices: Still use `http://192.168.1.20`

## Notes

- If migrations fail, confirm your database credentials in `.env`.
- If assets are missing, rerun `npm install` and `npm run build`.
- If the site is not reachable on LAN, check Windows Firewall and Apache port 80 access.
