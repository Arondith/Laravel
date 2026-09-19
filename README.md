# PulseDesk

**PulseDesk** is a full-stack helpdesk and SLA management platform built as a software-engineering portfolio project. It demonstrates a production-style Laravel architecture instead of a basic CRUD tutorial: REST endpoints, request validation, API resources, PHP enums, a service layer, relational data, internal support notes, server-side search, SLA calculations, automated tests, a Vue 3 + TypeScript interface, Docker services, and GitHub Actions CI.

## Why this project exists

Support systems are a useful engineering problem because they combine business rules with real operational workflows. PulseDesk tracks incoming requests, prioritizes incidents, calculates response deadlines, highlights SLA breaches, stores troubleshooting notes, and exposes operational metrics through a clean API.

This repository is designed to demonstrate skills relevant to **Laravel/PHP development, full-stack development, backend engineering, web development, technical-support tooling, and software QA**.

## Tech stack

- **Backend:** PHP 8.3+, Laravel 13
- **Frontend:** Vue 3, TypeScript, Vite, CSS
- **Database:** SQLite for zero-config local development; MySQL 8.4 in Docker
- **Infrastructure:** Docker Compose, Redis-ready cache/queue configuration
- **Quality:** PHPUnit feature tests, Laravel Pint, GitHub Actions CI
- **Architecture:** REST API, Form Requests, API Resources, Eloquent relationships, enums, service layer

## Features

- Ticket creation with validation
- Priorities: low, medium, high, critical
- SLA deadlines calculated from ticket priority
- Ticket lifecycle: open → in progress → resolved → closed
- SLA breach detection
- Server-side ticket search by reference, subject, requester name, or email
- Internal troubleshooting notes / ticket activity context
- Operational dashboard metrics including unassigned workload and average resolution time
- Filterable support queue
- Seeded demo data
- Responsive Vue dashboard and ticket workspace
- SQLite and MySQL configurations
- Redis-ready caching / queue setup
- Feature tests for API behavior
- Automated frontend build and backend tests in CI

## Architecture

```text
Vue 3 + TypeScript
        │
        ▼
Laravel REST API
        │
 ┌──────┼──────────┬──────────┐
 ▼      ▼          ▼          ▼
Requests Resources Services  Controllers
        │          │          │
        └──────────┼──────────┘
                   ▼
             Eloquent ORM
              │        │
           Tickets   Ticket Notes
              │
         SQLite / MySQL
```

The SLA rule is separated into `TicketSlaService` so business logic is reusable and independently testable. HTTP validation lives in Form Requests, API representation lives in Resources, enum-backed status/priority values reduce invalid state, and the `Ticket` → `TicketNote` relationship demonstrates a real one-to-many workflow.

## Quick start

Requirements: **PHP 8.3+**, **Composer**, **Node.js 22+**, and **npm**.

```bash
git clone https://github.com/Arondith/Laravel.git
cd Laravel
composer run setup
composer run dev
```

Then open **http://localhost:8000**.

The setup command installs dependencies, creates the local environment file and SQLite database, generates an application key, runs migrations with seed data, and builds the frontend.

## Run manually

```bash
composer install
cp .env.example .env
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan key:generate
php artisan migrate --seed

npm install
npm run dev
php artisan serve
```

## Docker

The included Compose stack demonstrates a Laravel + MySQL + Redis deployment topology:

```bash
docker compose up --build
docker compose exec app php artisan migrate --seed
```

## API

| Method | Endpoint | Purpose |
| --- | --- | --- |
| GET | `/api/health` | API health check |
| GET | `/api/dashboard` | Support/SLA metrics |
| GET | `/api/tickets` | Paginated ticket queue |
| GET | `/api/tickets?search=vpn` | Search the support queue |
| POST | `/api/tickets` | Create a ticket |
| GET | `/api/tickets/{ticket}` | View a ticket with internal notes |
| PATCH | `/api/tickets/{ticket}` | Update status, priority, or assignment |
| POST | `/api/tickets/{ticket}/notes` | Add an internal troubleshooting note |

Example ticket request:

```json
{
  "requester_name": "Jordan Reyes",
  "requester_email": "jordan@example.com",
  "subject": "Production login issue",
  "description": "Users receive an error after submitting valid credentials.",
  "priority": "critical"
}
```

Example internal note:

```json
{
  "author_name": "Support Agent",
  "body": "Reproduced the issue and isolated the failure to the authentication API."
}
```

## SLA rules

| Priority | Target |
| --- | ---: |
| Critical | 2 hours |
| High | 8 hours |
| Medium | 24 hours |
| Low | 72 hours |

These values live in the `TicketPriority` enum, keeping the domain rule close to the data type that owns it.

## Tests and code quality

```bash
composer test
vendor/bin/pint --test
npm run build
```

The feature tests cover ticket creation, status filtering, search behavior, SLA assignment, dashboard metrics, and internal note persistence.

## Portfolio talking points

Use this project in interviews to explain:

- why business logic belongs outside controllers;
- how Laravel Form Requests and API Resources keep HTTP code clean;
- how enum-backed states prevent inconsistent string values;
- how one-to-many Eloquent relationships model real support workflows;
- how server-side search differs from client-only filtering;
- how Vue and TypeScript consume and type a Laravel REST API;
- how feature tests protect API behavior;
- how SQLite simplifies onboarding while MySQL represents production infrastructure;
- how CI catches backend, frontend, and formatting failures before merge;
- how Docker separates application, database, and cache services.

## Roadmap

Future improvements can include authentication and roles, file attachments, email notifications through queues, audit events, WebSocket updates, API rate limiting, OpenAPI documentation, and production deployment.

## License

MIT
