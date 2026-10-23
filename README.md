# Janahitha Bake House

Daily ordering, billing and credit tracking for the bakery's delivery routes. Each route has shops; each day the
salesman enters every shop's order, bills are generated from the orders, returns and payments are recorded on the
bills, and reports show sales, collections and outstanding credit.

The app is a Laravel 12 application with a MySQL database. Everything is stored on the server, so every authorised
phone, tablet and computer sees the same data.

## How it fits together

| Part | Where |
| --- | --- |
| The app (dashboard, orders, bills, reports, admin) | `/` — [resources/views/bakehouse.blade.php](resources/views/bakehouse.blade.php), [resources/js/standalone/app.js](resources/js/standalone/app.js), [resources/css/standalone/](resources/css/standalone/) |
| JSON API used by the app | `/api/...` — [routes/web.php](routes/web.php), [app/Http/Controllers/Api/](app/Http/Controllers/Api/) |
| Translation between the app's records and the database tables | [app/Documents/](app/Documents/) |
| Database tables | [database/migrations/](database/migrations/), or [database/schema.sql](database/schema.sql) for phpMyAdmin |
| Older Livewire admin pages | `/login`, `/dashboard`, `/admin/*` |
| The old offline version (kept only to export old browser data) | [index.html](index.html) in the project folder |

The app talks to the API with the normal Laravel session cookie and CSRF token. Open pages refresh their data every
15 seconds, and straight away when the browser tab is shown again, so changes made on one device appear on the others.
Small preferences such as the last route or report range chosen stay in the browser; all business data is in MySQL.

### API

All endpoints return JSON. Everything except `GET /api/session`, `POST /api/session` and `POST /setup` requires a
signed-in, active account. Write requests need the `X-XSRF-TOKEN` header (the app sends it automatically).

| Method and path | What it does | Permission |
| --- | --- | --- |
| `GET /api/session` | Signed-in account, and whether first-run setup is still needed | — |
| `POST /api/session` | Sign in (`username`, `password`, `remember`); 5 failed attempts per minute | — |
| `DELETE /api/session` | Sign out | — |
| `POST /setup` | Create the first owner account (only while there are no accounts) | — |
| `GET /api/routes` · `PUT/PATCH/DELETE /api/routes/{id}` | Routes and drivers | `routes` to change |
| `GET /api/shops` · `PUT/PATCH/DELETE /api/shops/{id}` | Shops, their usual items and own prices | `shops` to change |
| `GET /api/config` · `PUT /api/config/catalog` | Items and default prices | `items` to change |
| `GET /api/users` · `PUT/PATCH/DELETE /api/users/{id}` | Accounts and permissions | `users` |
| `GET /api/days?date=&from=&to=&routeId=` · `PATCH /api/days/{date}_{route}` | Orders per route and day | `orders` to change |
| `GET /api/bills?date=&from=&to=&routeId=` · `PATCH /api/bills/{date}_{route}` | Bills, returns and payments | `bills_generate`, `bills_returns`, `bills_prices` or `bills_payments`, depending on the change |
| `POST /api/imports` | Import a backup of the offline app's data | owner only |

People who work on only some routes can only read and change orders and bills of those routes.

## Requirements

- PHP **8.4.1 or newer** (some locked packages require it) with the `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`,
  `xml`, `ctype`, `fileinfo` and `bcmath` extensions
- MySQL 8 or MariaDB 10.6 or newer
- Composer 2, and Node.js 20 or newer to build the front-end files (on your computer only, not on the server)

## Local development

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Create an empty database, put its details in `.env` (`DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`,
`DB_USERNAME`, `DB_PASSWORD`), then:

```bash
php artisan migrate --seed   # tables, plus the default routes, shops and items from data/seed.json
composer run dev             # Laravel on http://localhost:8000 and the Vite dev server
```

Open http://localhost:8000 and create the admin account. Run the tests with `php artisan test`.

If the page shows no styles and you are not running `npm run dev`, delete `public/hot` and run `npm run build`.

## Database setup

Create an empty database with the `utf8mb4` character set, then use **one** of these:

- **With a terminal:** `php artisan migrate --force`. Add `php artisan db:seed --force` for the default routes,
  shops and items.
- **Without a terminal (phpMyAdmin):** import [database/schema.sql](database/schema.sql). It creates every table,
  key and index and marks the migrations as run. It contains no routes or shops; those come from your backup
  (next section) or you add them in Admin.

Main tables: `users` (+ `route_user` for route access), `routes`, `shops`, `items`, `shop_items` (each shop's items
and own prices), `orders` + `order_lines`, `bills` + `bill_lines`, `payments`. Laravel also uses `sessions`,
`cache`, `cache_locks`, `jobs` and `migrations`.

After changing migrations, regenerate the SQL file with `php artisan schema:dump --path=database/schema.sql`.

## Moving data from the old offline app

The old version kept everything in the browser (`localStorage`), separately for each web address. Move it once:

1. **Export from the old app.** On the computer that has the data, open the old app at the **same address you used
   before**, because the browser keeps data per address. For example, in the project folder run
   `php -S 127.0.0.1:8001` and open http://127.0.0.1:8001. Sign in as the owner, open
   **Admin → Backup & import** and press **Download backup**. Repeat for any other address or browser that has
   data. The file holds your sales and the sign-in details of the old accounts, so keep it private.
2. **Import on the server.** Sign in to the live site as the owner, open **Admin → Backup & import**, choose the
   file and press **Import**. The server keeps an exact copy of every upload in `storage/app/private/imports/`
   before changing anything, and imports everything in a single transaction.
3. **Check the result.** The result lists, for routes, shops, items, users, orders and bills, how many records were
   in the backup and how many were found in the database afterwards. **All records checked** means they all match.
4. **Remove the old browser data (optional).** Only after a successful check: in the old app, **Admin → Backup &
   import → Remove data from this browser**. If the old data was on the same address as the live site, the import
   screen offers the same button after a successful import.

What the import does:

- Records are matched by their original ids, so importing the same file again adds no duplicates. For an order or
  bill that already exists on the server, the imported values replace the server's for that shop and day, and
  payments are added only once.
- Old accounts keep their passwords. On their first sign-in the password is converted to a standard secure hash.
  If a username already exists on the server, the server account is kept and the old one is reported.
- Items mentioned by old orders or bills but missing from the item list are created, so no order line is lost.
  Orders for shops that no longer exist are skipped and listed as warnings.

## Deploying to cPanel

### 1. Build the upload package on your computer

```bash
npm ci && npm run build
rm -rf ../bakehouse-deploy
rsync -a ./ ../bakehouse-deploy/ \
  --exclude .git --exclude node_modules --exclude vendor --exclude .env \
  --exclude .claude --exclude .mcp.json --exclude boost.json --exclude .phpunit.result.cache \
  --exclude 'database/*.sqlite' --exclude 'bootstrap/cache/*.php' --exclude 'storage/logs/*.log' \
  --exclude 'storage/framework/sessions/*' --exclude 'storage/framework/views/*' \
  --exclude 'storage/framework/cache/data/*' --exclude storage/app/private/imports \
  --exclude public/hot --exclude public/storage
cd ../bakehouse-deploy
composer install --no-dev --optimize-autoloader --no-interaction
zip -qr ../bakehouse-deploy.zip .
```

This leaves your own working folder untouched. The package contains `vendor/` and the built files in
`public/build/`, so the server needs neither Composer nor Node.js.

### 2. Create the database

1. cPanel → **MySQL Database Wizard**: create a database (for example `cpuser_bakehouse`) and a user with a strong
   password, and give the user **All Privileges** on the database.
2. cPanel → **phpMyAdmin**: select the new database → **Import** → choose `database/schema.sql` → **Go**.
   (If you have **Terminal**, you can run `php artisan migrate --force` in step 5 instead.)

### 3. Upload the files

1. cPanel → **File Manager**: upload `bakehouse-deploy.zip` to your home folder (**not** inside `public_html`) and
   **Extract** it into a folder such as `bakehouse-app`.
2. cPanel → **Domains** → your domain → **Manage**: set the **Document Root** to `bakehouse-app/public`.
   This keeps `.env`, the code and the backups out of the website.

   If your cPanel cannot change the document root of the main domain: copy (do not move) everything inside
   `bakehouse-app/public`, including `.htaccess`, into `public_html`. Then edit `public_html/index.php` and change
   the three `__DIR__.'/../` paths to `__DIR__.'/../bakehouse-app/` (the `maintenance.php`, `vendor/autoload.php`
   and `bootstrap/app.php` lines). Repeat the copy of `public/build` after each update.

### 4. PHP settings

cPanel → **MultiPHP Manager**: choose **PHP 8.4** or newer for the domain. In **Select PHP Version** (or MultiPHP
INI Editor), make sure the extensions listed under Requirements are on.

### 5. Settings file (`.env`)

In `bakehouse-app`, copy `.env.example` to `.env` and set:

```dotenv
APP_NAME="Janahitha Bake House"
APP_ENV=production
APP_KEY=           # run `php artisan key:generate --show` on your computer and paste the result
APP_DEBUG=false
APP_URL=https://your-domain.com
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=cpuser_bakehouse
DB_USERNAME=cpuser_bakeuser
DB_PASSWORD=the-database-password

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Never set `APP_DEBUG=true` on the live site. If you have **Terminal**, also run in `bakehouse-app`:

```bash
php artisan migrate --force    # only if you did not import schema.sql
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Run the cache commands again after every change to `.env`.

### 6. Folder permissions

`storage/` and `bootstrap/cache/` must be writable by PHP. On most cPanel servers the default 755 folders and
644 files work; use 775 on those two folders if you see "permission denied" errors in
`storage/logs/laravel.log`.

### 7. HTTPS

cPanel → **SSL/TLS Status**: run **AutoSSL** for the domain. Then cPanel → **Domains**: turn on
**Force HTTPS Redirect**. Keep `APP_URL` on `https://` and `SESSION_SECURE_COOKIE=true`.

### 8. First visit

Open the site. On a new database it asks you to create the admin (owner) account. Then import your old data
(see "Moving data from the old offline app") or add routes, items and shops in **Admin**, and create an account
for each person in **Admin → Users**.

The app keeps all its screens on the one page, so no extra rewrite rules are needed: Laravel's `public/.htaccess`
already sends every request that is not a real file to `index.php`.

### Updating the live site later

Build a new package as in step 1 and upload it over the old folder, keeping `.env` and `storage/`. If the update
contains new migrations, run `php artisan migrate --force` in Terminal (or import the new tables with phpMyAdmin),
then run the cache commands again.

## Security notes

- Passwords are hashed with bcrypt on the server. The browser never receives password hashes.
- Every change is checked on the server against the account's permissions and route access, whatever the browser
  sends. Blocking an account signs it out on all devices.
- Sign-in is limited to 5 failed attempts per minute for each username and address.
- Backup files and the copies in `storage/app/private/imports/` contain the old accounts' password hashes. Keep
  them private and delete them once you no longer need them.
