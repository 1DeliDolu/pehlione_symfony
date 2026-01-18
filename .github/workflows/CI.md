# CI for Pehlione (Symfony)

This document describes a recommended continuous integration (CI) setup for the Pehlione Symfony application and includes an example GitHub Actions workflow. The steps and commands below are derived from this repository's tooling (`composer`, `vendor/bin/phpunit`, `npm`/`package.json`, `docker-compose.yml`, etc.).

## Goals

- Install PHP and JS dependencies
- Build frontend assets if required
- Run the test suite with PHPUnit
- Cache dependencies for faster runs

## Supported runtime

- PHP: >= 8.2 (see `pehlione/composer.json`)
- Node.js / npm for frontend tasks

## What CI should run (high level)

1. Checkout repository
2. Set `APP_ENV=test` and other required secrets (see **Secrets / Env**)
3. Install PHP dependencies via Composer
4. Install Node dependencies (`npm ci` or `npm install`) and optionally build assets (`npm run build`) if tests rely on assets
5. Run test suite: `vendor/bin/phpunit`
6. Optionally collect artifacts (coverage) and upload test results

## Example GitHub Actions workflow

Save the following as `.github/workflows/ci.yml` to run CI on push and pull requests. This is an example that uses actions and commands compatible with this repository; adapt DB services and secrets to your environment.

```yaml
name: CI

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

jobs:
  ci:
    runs-on: ubuntu-latest
    services: {}
    steps:
      - name: Checkout
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v4
        with:
          php-version: "8.2"
          extensions: mbstring, intl, xml

      - name: Validate composer.json
        run: composer validate --strict

      - name: Install Composer dependencies
        run: composer install --no-progress --no-suggest --prefer-dist --optimize-autoloader

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: "18"

      - name: Install Node dependencies
        working-directory: pehlione
        run: npm ci

      - name: Build assets (optional)
        working-directory: pehlione
        run: npm run build

      - name: Run PHPUnit
        working-directory: pehlione
        run: vendor/bin/phpunit --configuration phpunit.dist.xml

      # Optionally add cache steps and database service startup here
```

Notes:

- The example uses `pehlione/phpunit.dist.xml` and runs PHPUnit from the `pehlione/` directory.
- Adjust `node-version` and PHP extensions as needed.

## Secrets / Env variables required in CI

- `APP_SECRET` — Symfony secret (exists in `pehlione/.env.dev` / `pehlione/.env.test` locally)
- `DATABASE_URL` — required if tests or migrations need a database connection
- `MAILER_DSN` / `MAILER_FROM` — configure if mail-related tests rely on a real mailer; MailHog is available locally via `pehlione/docker-compose.yml` for development

Configure repository secrets in GitHub Settings → Secrets and set any values required by tests.

## Database & services

- This repository does not include a specific production database image or DB configuration in CI. If your tests require a database, add an appropriate service to the workflow (MySQL/Postgres) and set `DATABASE_URL` accordingly.

Example service snippet (Postgres) to include under `services` in the workflow job:

```yaml
services:
  postgres:
    image: postgres:15
    env:
      POSTGRES_DB: symfony
      POSTGRES_USER: symfony
      POSTGRES_PASSWORD: symfony
    options: >-
      --health-cmd "pg_isready -U symfony -d symfony"
      --health-interval 10s
      --health-timeout 5s
      --health-retries 5
```

Then set `DATABASE_URL` in the job's `env`:

```yaml
env:
  DATABASE_URL: "postgresql://symfony:symfony@localhost:5432/symfony"
  APP_ENV: test
  APP_SECRET: ${{ secrets.APP_SECRET }}
```

## Caching recommendations

- Cache Composer cache (`~/.composer/cache`) and `pehlione/node_modules` between runs to speed up CI.
- Use GitHub Actions `actions/cache` for Composer and npm caches.

## Troubleshooting & Assumptions

- The repository provides `pehlione/.env.dev` and `pehlione/.env.test` but does not include a `.env.example`. CI must supply required secrets via repository secrets.
- No application `Dockerfile` is included; only a `docker-compose.yml` with MailHog is present for local mail testing.
- If tests depend on compiled frontend assets, the `npm run build` step is included in the example. If not required, you can omit it to speed up CI.

## Next steps

- If you want, I can add the example workflow file `.github/workflows/ci.yml` for you, or extend the example to include caching and DB services tailored to your preferred DB engine.
