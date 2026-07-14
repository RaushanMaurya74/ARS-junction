# ARS JUNCTION

Online food ordering platform built with HTML, CSS, JavaScript, PHP, and MySQL.

## Setup

1. Copy this folder into your PHP web root, for example `xampp/htdocs/ars-junction`.
2. Create/import the MySQL database from `database/ars_junction.sql`.
3. Update database settings with environment variables if needed:
   - `DB_HOST`
   - `DB_PORT`
   - `DB_NAME`
   - `DB_USER`
   - `DB_PASS`
4. Open the site in your browser, for example `http://localhost/ars-junction/`.

Default local database values are:

- Host: `127.0.0.1`
- Port: `3306`
- Database: `ars_junction`
- User: `root`
- Password: empty

## Demo Login

Admin panel:

- URL: `admin/login.php`
- Email: `maurya@arsjunction.com`
- Password: `Maurya1055@`

Demo customer:

- Email: `customer@arsjunction.com`
- Password: `password`

## Social Login

Facebook and Google buttons are wired to `api/social_login.php`. Add real provider keys on the server:

- `FACEBOOK_APP_ID`
- `GOOGLE_CLIENT_ID`

Without real keys, provider authentication will not complete.
