# Pehlione (pehlione_symfony)

One-line: A Symfony-based PHP web application (store/support platform) with frontend assets managed by Webpack Encore and Tailwind, database migrations, and MailHog for email testing.

## Overview

Pehlione is a full-stack PHP web application built on Symfony 7.4 components. The repository contains backend code (controllers, services, entities), Twig templates, Stimulus/Turbo frontend assets, Doctrine ORM migrations, and developer tooling for asset building and local mail testing.

## Features

- Symfony-based MVC application with Twig templates and services
- Doctrine ORM with migrations included
- Frontend asset pipeline using Webpack Encore, Stimulus, Turbo and TailwindCSS
- Mail testing via MailHog (docker-compose)
- PHPUnit test configuration

## Tech Stack

- PHP >= 8.2
- Symfony 7.4 components (framework, security, twig, etc.)
- Doctrine ORM & Migrations
- Webpack / Encore, TailwindCSS, PostCSS, Stimulus, Turbo
- MailHog (mail testing)
- PHPUnit for tests

## Repository Structure (key files/folders)

- `pehlione/` — main Symfony project
  - `pehlione/bin/console` — Symfony console entry
  - `pehlione/public/index.php` — front controller
  - `pehlione/src/` — PHP source (controllers, services, entities)
  - `pehlione/templates/` — Twig templates
  - `pehlione/assets/` — JS/CSS sources and Stimulus controllers
  - `pehlione/migrations/` — Doctrine migration files
  - `pehlione/config/services.yaml` — service wiring & env bindings
  - `pehlione/composer.json` — PHP dependencies
  - `pehlione/package.json` — JS build scripts
  - `pehlione/docker-compose.yml` — docker services (MailHog)
- `LICENSE` — MIT license

Small top-level tree (truncated):

```
pehlione/
├─ bin/
├─ config/
├─ public/
├─ src/
├─ templates/
├─ assets/
├─ migrations/
├─ composer.json
├─ package.json
├─ docker-compose.yml
└─ phpunit.dist.xml
```

## Prerequisites

- PHP >= 8.2
- Composer
- Node.js + npm (for frontend assets)
- A supported database for Doctrine (DSN not provided in repo)
- Docker (optional, for MailHog)

## Quickstart (development)

1. Install PHP dependencies:

```bash
cd pehlione
composer install
```

2. Install JS dependencies and build assets (development):

```bash
npm install
npm run dev
```

3. Start MailHog for email testing (optional):

```bash
docker-compose -f pehlione/docker-compose.yml up -d
# MailHog UI: http://localhost:8025  (SMTP: localhost:1025)
```

4. Run the application (simple PHP server example):

```bash
# from repository root
php -S 127.0.0.1:8000 -t pehlione/public
```

5. Run Doctrine migrations:

```bash
cd pehlione
php bin/console doctrine:migrations:migrate
```

## Local Development

### Install

- PHP deps: `composer install` (run from `pehlione/`)
- JS deps: `npm install` (run from `pehlione/`)

### Run

- Build assets for development: `npm run dev`
- Watch assets: `npm run watch`
- Build production assets: `npm run build`
- Start PHP server: see Quickstart; optionally use Symfony CLI if installed.

### Environment Variables / Configuration

- Development/test env files included:
  - `pehlione/.env.dev`
  - `pehlione/.env.test`

Key env variables referenced in config:

- `MAILER_FROM` — used by mail-related services (`config/services.yaml`)
- `APP_SECRET` — Symfony secret (present in `.env.dev` and `.env.test`)

Note: There is no `.env.example` in the repository. See Assumptions / TODO.

## Testing

- PHPUnit is configured via `pehlione/phpunit.dist.xml`.
- Run tests:

```bash
cd pehlione
vendor/bin/phpunit
```

## Linting / Formatting

- No dedicated PHP or JS linter/formatter configuration is present in the repository (no PHPStan, PHP CS Fixer, ESLint, or Prettier configs found).

## Docker

- A minimal `docker-compose.yml` is present for MailHog (mail testing):
  - Service: `mailhog` (image `mailhog/mailhog:latest`)
  - Ports: SMTP `1025:1025`, Web UI `8025:8025` (`pehlione/docker-compose.yml`)

There is no application `Dockerfile` included.

## Kubernetes / OpenShift

- No Kubernetes, Helm, or OpenShift manifests detected.

## CI/CD

- A GitHub Actions workflow is included at `.github/workflows/ci.yml`. The workflow installs PHP and Node dependencies, caches composer/npm, optionally starts a Postgres service for tests, builds frontend assets, and runs the test suite with PHPUnit. See `.github/workflows/CI.md` for details and configuration notes.

## API Documentation

- No OpenAPI/Swagger or other API docs detected.

## Troubleshooting

- If vendor dependencies are missing, run `composer install` in `pehlione/`.
- If assets are missing, run `npm install` then `npm run dev` from `pehlione/`.
- For mail testing, start MailHog: `docker-compose -f pehlione/docker-compose.yml up -d` and visit http://localhost:8025.

## Contributing

- No `CONTRIBUTING.md` present. Follow standard GitHub PR workflow; open issues/PRs for changes.

## Security

- No `SECURITY.md` present. Keep `APP_SECRET` and credentials out of VCS; use secret management in deployments.

## License

This repository is licensed under the MIT License — see `LICENSE`.

## Assumptions / TODO

- Missing or unclear items discovered while inspecting the repo:
  - No `.env.example` or central documentation listing required environment variables.
  - Database DSN and credentials are not present — the app requires a configured database at runtime.
  - No `Dockerfile` for the application; only MailHog is present in `docker-compose.yml`.
  - A GitHub Actions workflow is present at `.github/workflows/ci.yml`.
  - No linting or static analysis configs (PHPStan, PHP CS Fixer, ESLint) were found.
  - No explicit run command using the Symfony CLI is documented in repo files; example uses PHP built-in server.

Where I checked: `pehlione/composer.json`, `pehlione/package.json`, `pehlione/docker-compose.yml`, `pehlione/phpunit.dist.xml`, `pehlione/config/services.yaml`, `pehlione/.env.dev`, `pehlione/.env.test`, `pehlione/bin/console`, `pehlione/public/index.php`, `.github/workflows/ci.yml`, `.github/workflows/CI.md`.

If you'd like, I can add a minimal `pehlione/.env.example` and a short `CONTRIBUTING.md` next.
