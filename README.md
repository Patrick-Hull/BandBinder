# BandBinder

A self-hosted web application for managing a band's music library — charts, setlists, instrument PDFs, and audio files — with per-user permissions and a personal notes/ratings system.

---

## Features

- **Charts** — Store sheet music with metadata (BPM, key, duration, artist, arranger). Upload a master PDF and split it into per-instrument parts by assigning pages visually.
- **Audio files** — Attach an audio recording (MP3, WAV, OGG, M4A, FLAC, AAC) to any chart. Built-in player with seek bar, skip controls, and volume.
- **Setlists** — Build multi-set setlists with drag-and-drop. Generate a printable PDF with duration totals.
- **My Charts** — Each musician sees only the PDFs relevant to their instrument(s). Add private notes, instrument-section notes, and a star rating per chart.
- **User management** — Role-based permission system via user types. Assign instruments to users.
- **Dashboard** — At-a-glance stats, recently added charts, upcoming setlists, top artists/arrangers.
- **Dark mode** — Toggle with localStorage persistence.
- **Mobile-responsive** — Bootstrap 5 with DataTables responsive extension.

---

## Tech Stack

| Layer      | Technology                          |
|------------|-------------------------------------|
| Runtime    | PHP 8.5 (Apache)                    |
| Database   | MySQL 8.0                           |
| Frontend   | Bootstrap 5.3, jQuery, DataTables 2 |
| PDF import | FPDI / FPDF (via Composer)          |
| PDF render | GhostScript (re-encoding), PDF.js   |
| Containers | Docker / Docker Compose             |

---

## Prerequisites

- [Docker](https://docs.docker.com/get-docker/) and [Docker Compose](https://docs.docker.com/compose/)

---

## Quick Start

1. **Clone the repository**

   ```bash
   git clone <repo-url>
   cd BandBinder
   ```

2. **Create your environment file**

   ```bash
   cp .env.example .env
   ```

   Edit `.env` and set secure values for all variables (see [Environment Variables](#environment-variables)).

3. **Build and start the containers**

   ```bash
   docker compose up -d --build
   ```

   On first start the entrypoint will:
   - Install Composer dependencies
   - Wait for MySQL to be ready
   - Run all pending database migrations automatically

4. **Open in your browser**

   ```
   http://localhost:8080
   ```

   Default admin credentials are created by the initial migration — login with username: admin and password: admin, then **change them immediately** after first login.

---

## Environment Variables

Copy `.env.example` to `.env` and fill in the values:

| Variable       | Description                          | Example              |
|----------------|--------------------------------------|----------------------|
| `DB_HOST`      | MySQL hostname (service name)        | `db`                 |
| `DB_NAME`      | Database name                        | `bandbinder`         |
| `DB_USER`      | MySQL application user               | `bandbinder`         |
| `DB_PASS`      | MySQL application user password      | *(strong password)*  |
| `DB_ROOT_PASS` | MySQL root password                  | *(strong password)*  |
| `SITE_TYPE`    | `dev` enables verbose PHP errors     | `dev` / `prod`       |

---

## Database Migrations

Migrations live in `src/db/migration/` as numbered SQL files (`1-initial-schema.sql`, `2-users-management.sql`, …).

**They run automatically every time the Docker container starts.** The migration runner only executes files that have not been applied yet, so it is safe to run on every boot.

To run migrations manually (e.g. outside Docker):

```bash
php src/db/migrate.php
```

To add a new migration, create the next numbered file:

```
src/db/migration/7-my-change.sql
```

The runner will pick it up on the next container start (or manual run).

---

## Project Structure

```
BandBinder/
├── docker/
│   ├── php/
│   │   ├── Dockerfile              # PHP 8.5 + Apache image
│   │   └── docker-entrypoint.sh    # Runs composer, migrations, then Apache
│   └── mysql-init/                 # SQL run by MySQL on first volume creation
├── src/
│   ├── db/
│   │   ├── migrate.php             # Migration runner
│   │   ├── schema-latest.sql       # Auto-generated schema snapshot
│   │   └── migration/              # Numbered SQL migration files
│   ├── lib/
│   │   ├── class/                  # PHP classes (Chart, User, Setlist, …)
│   │   ├── html_header/            # Shared <head> includes (Bootstrap, etc.)
│   │   └── navbar.php              # Navigation bar
│   └── public/                     # Apache document root
│       ├── index.php               # Dashboard
│       ├── login.php
│       ├── logout.php
│       ├── charts/
│       ├── setlists/
│       ├── artists/
│       ├── arrangers/
│       ├── instruments/
│       ├── users/
│       └── uploads/                # Uploaded PDFs and audio files (gitignored)
├── docker-compose.yml
├── docker-compose.override.yml     # Local dev overrides (port mappings, etc.)
├── docker-compose.prod.yml         # Production overrides
├── .env.example
└── README.md
```

---

## Development

**Rebuild after Dockerfile changes:**

```bash
docker compose build app && docker compose up -d app
```

**View application logs:**

```bash
docker compose logs -f app
```

**Access the database directly:**

```bash
docker exec -it bandbinder-db mysql -u bandbinder -p bandbinder
```

**phpMyAdmin** is available (if included in your override file) at `http://localhost:8081`.

---

## Permissions System

Permissions are grouped and assigned to *User Types*. Each user is assigned a User Type which determines what they can see and do. Key permission keys:

| Key                    | Description                          |
|------------------------|--------------------------------------|
| `charts.view`          | View own instrument's charts         |
| `charts.viewAll`       | View all charts                      |
| `charts.create/edit/delete` | Manage charts                   |
| `setlists.view`        | View setlists and download PDFs      |
| `setlists.create/edit/delete` | Manage setlists               |
| `artists.view/create`  | View and create artists              |
| `arrangers.view/create`| View and create arrangers            |
| `instruments.view`     | View instruments                     |
| `users.view/create/edit/delete` | Manage users                |
| `users.editPermissions`| Manage user types and permissions    |
