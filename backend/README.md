# Sheet Music Vault — Backend (PHP 8 + MySQL)

A clean, framework-free REST API for the Sheet Music Vault frontend. Built on a
small custom core (autoloading, router, request/response objects) and a
middleware pipeline. No Composer strictly required (it works with either
Composer's autoloader or the built-in PSR-4 autoloader).

## Requirements

- PHP 8.1+ with `pdo_mysql`, `json`, `zip` and `simplexml` (zip + simplexml are
  needed for the Excel import)
- MySQL 5.7+ / MariaDB 10+
- Optional: Apache with `mod_rewrite`, or Composer for `composer install`

## Project layout

```
backend/
├── .env                    # DB + CORS config (copy .env.example)
├── composer.json           # Optional PSR-4 autoload (App\ => src/)
├── bootstrap/
│   └── app.php             # Bootstraps autoload, config, routes, middleware
├── database/
│   └── schema.sql          # Creates DB + table + sample rows
├── public/
│   ├── index.php           # Entry point
│   ├── router.php          # Router for the PHP built-in server
│   └── .htaccess           # Apache rewrite rules + CORS
└── src/                    # PSR-4 namespace: App\
    ├── Core/
    │   ├── Application.php         # Middleware pipeline
    │   ├── Autoloader.php          # Built-in PSR-4 autoloader
    │   ├── Config.php              # .env loader
    │   ├── Database.php            # PDO singleton
    │   ├── Request.php             # HTTP request value object
    │   ├── Response.php            # HTTP response value object
    │   ├── Router.php              # Route registry + dispatcher ({param})
    │   └── Exceptions/             # HttpException, NotFound, MethodNotAllowed
    ├── Middleware/
    │   ├── MiddlewareInterface.php
    │   ├── CorsMiddleware.php      # CORS headers + OPTIONS short-circuit
    │   ├── ErrorHandlerMiddleware.php
    │   └── JsonBodyMiddleware.php  # Decodes JSON request bodies
    ├── Controllers/
    │   └── SheetMusicController.php
    ├── Models/
    │   └── SheetMusic.php
    └── Utils/
        └── XlsxReader.php        # Dependency-free .xlsx parser (zip + simplexml)
```

## Setup

1. Copy `.env.example` to `.env` and set DB credentials:

   ```ini
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=sheet_music_db
   DB_USERNAME=root
   DB_PASSWORD=your_password
   CORS_ALLOWED_ORIGINS=http://localhost:5173
   ```

2. Create the database:

   ```bash
   mysql -u root -p < database/schema.sql
   ```

3. Run the API:

   ```bash
   # Built-in server (no Apache needed)
   php -S 127.0.0.1:8000 -t public public/router.php

   # or via composer
   composer serve
   ```

   For Apache/XAMPP/WAMP point the vhost document root at `backend/public/`;
   the included `.htaccess` rewrites all requests to `index.php`.

## Middleware pipeline

Middleware runs in the order registered in `bootstrap/app.php`:

1. `CorsMiddleware` (outermost) — sets CORS headers on every response (including
   errors) and returns `204` for preflight `OPTIONS` requests.
2. `ErrorHandlerMiddleware` — converts exceptions to JSON `500`/`404`/`405`.
3. `JsonBodyMiddleware` (innermost) — decodes `application/json` bodies onto the
   request object.

To add middleware: create a class implementing `MiddlewareInterface`, then
`$app->addMiddleware(new YourMiddleware())` in `bootstrap/app.php`.

## API

| Method   | URL                            | Description                      |
|----------|--------------------------------|----------------------------------|
| GET      | `/api/sheet-music`                 | List all pieces (newest first)   |
| POST     | `/api/sheet-music`                 | Create a piece (HTTP 201)        |
| POST     | `/api/sheet-music/import-excel`    | Bulk import from an `.xlsx` file |
| GET      | `/api/sheet-music/{id}`            | Fetch a single piece             |
| PUT/PATCH| `/api/sheet-music/{id}`            | Update a piece                   |
| DELETE   | `/api/sheet-music/{id}`            | Delete a piece                   |
| POST     | `/api/uploads/score-img`           | Upload a score sheet image       |
| GET      | `/api/uploads/score-img/{filename}`| Serve an uploaded score image    |

Example:

```bash
curl -X POST http://127.0.0.1:8000/api/sheet-music \
  -H "Content-Type: application/json" \
  -d '{"title":"Gymnopedie No. 1","composer":"Erik Satie","year":1888,"genre":"Classical (1750 - 1820)"}'
```

Response (camelCase, matching the frontend store):

```json
{
  "id": 7,
  "location": null,
  "shelfId": null,
  "title": "Gymnopedie No. 1",
  "subtitle": null,
  "composer": "Erik Satie",
  "arranger": null,
  "year": 1888,
  "genre": "Classical (1750 - 1820)",
  "category": null,
  "publisher": null,
  "scoreImg": null,
  "createdAt": "2026-08-03 15:36:24",
  "updatedAt": "2026-08-03 15:36:24"
}
```

Validation: `title`, `year`, `genre` are required; `composer`, `subtitle`,
`arranger`, `location`, `shelf_id`, `category`, `publisher` are optional.
Failures return `422` with a `fields` object.

## Bulk import from Excel

`POST /api/sheet-music/import-excel` accepts an `.xlsx` file (multipart field
`file`, max 10 MB). The first row must be the header row; each following row is
one record. The `id` is always auto-incremented by the database.

Supported columns (header names are matched case-insensitively, ignoring
spaces/underscores, e.g. `Shelf ID`, `shelf_id` and `ShelfID` all work):

| Column      | Required | Notes                                        |
|-------------|----------|----------------------------------------------|
| `location`  | optional | Where the piece is stored                    |
| `shelf_id`  | optional | Shelf / box identifier                       |
| `title`     | yes      |                                              |
| `subtitle`  | optional |                                              |
| `composer`  | optional |                                              |
| `arranger`  | optional |                                              |
| `genre`     | no       | Defaults to `Common` when left empty         |
| `category`  | optional | Repertoire, Etudes, Recital, ...             |
| `publisher` | optional |                                              |
| `year`      | no       | Defaults to the current year when empty      |

If any row fails validation the whole import is rolled back (`422`) and the
response lists each problem with its spreadsheet row number, e.g.:

```bash
curl -X POST http://127.0.0.1:8000/api/sheet-music/import-excel \
  -H "Authorization: Bearer <admin-token>" \
  -F "file=@pieces.xlsx"
```

```json
{
  "error": "Import failed. Fix the highlighted rows and try again.",
  "imported": 0,
  "errors": [
    { "row": 4, "field": "title", "message": "Title is required." }
  ]
}
```

On success all rows are inserted inside a single transaction:

```json
{ "message": "Imported 12 sheet music records.", "imported": 12 }
```

Only admins can import (same access rules as `POST /api/sheet-music`).

## Frontend integration

`frontend/src/stores/sheetMusic.js` already calls this API. Set the base URL in
`frontend/.env`:

```
VITE_API_BASE_URL=http://localhost:8000
```