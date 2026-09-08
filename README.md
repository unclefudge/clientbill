# ClientBill

ClientBill is an internal business application used by Open Hands to manage clients, projects, time entries, invoices, hosting and domain renewals, and bookkeeping records.

## Technology

- PHP 8.2 or later
- Laravel 12
- Filament 4
- Pest
- Laravel Pint
- MySQL
- Node.js and npm

## Local development

ClientBill is developed locally using Laravel Herd on macOS.

Clone the repository and enter the project directory:

```bash
git clone git@github.com:unclefudge/clientbill.git
cd clientbill
```

Install the PHP and JavaScript dependencies:

```bash
composer install
npm install
```

Create the local environment file and application key:

```bash
cp .env.example .env
php artisan key:generate
```
Update the application and database settings in `.env`:

```dotenv
APP_NAME=ClientBill
APP_URL=http://clientbill.test
DB_DATABASE=clientbill
```

Adjust the database username and password if your local MySQL configuration requires them.

Create a local MySQL database, then configure the database connection in `.env`.

Run the database migrations:

```bash
php artisan migrate
```

Build the frontend assets:

```bash
npm run build
```

When using Herd, the application is available through the local Herd site configured for this directory.

For active frontend development, run:

```bash
npm run dev
```

If queued jobs need to be processed locally, run:

```bash
php artisan queue:work
```

## Tests

Run the full Pest test suite:

```bash
php artisan test
```

Run a specific test file:

```bash
php artisan test tests/Unit/Services/Books/BookBasCalculatorTest.php
```

## Code formatting

Check the formatting of changed files:

```bash
vendor/bin/pint --test
```

Apply Pint formatting:

```bash
vendor/bin/pint
```

## Deployment

Production deployment is managed through Laravel Forge. Deployment should only occur after the relevant branch has been reviewed and merged into `main`.
