# Nepal Disaster Archive

Nepal Disaster Archive is a PHP and MySQL web application for documenting Nepal's natural-disaster history. It provides a public archive of published stories and an authenticated editorial workspace for managing stories, categories, and user accounts.

## Features

- Public archive of published disaster stories
- Search by title, location, year, summary, or content
- Filter stories by hazard category
- Story fields for dates, locations, impacts, casualties, magnitude, and sources
- Draft and published story states
- Featured stories on the public archive
- Admin/editor authentication with password hashing and CSRF protection
- Category management
- Admin-only user management and story deletion
- About Us page with archive sources, coverage, methodology, disclaimer, and update information
- Emergency resource directory, preparedness guides, and English/Nepali safety content
- Cover images, map coordinates, district/province browsing, and an interactive timeline
- Advanced filtering by category, location, date range, and sort order
- Draft, needs-review, and published editorial workflow with revision history

## Requirements

- XAMPP with Apache and MySQL
- PHP 8.0 or later
- PHP extensions: PDO MySQL, mbstring, and GD with FreeType support
- A modern web browser

In XAMPP, enable `extension=gd` in `php.ini` and restart Apache to generate story-sharing preview images.

## Cloud deployment

The Docker image uses Apache's `/var/www/html` document root. Configure these environment variables in the cloud service instead of putting production database credentials in source:

Public pages use clean URLs (for example, `/about` and `/preparedness`), and story pages use `/story/<slug>`. Existing `.php` page URLs redirect to their clean versions. The Docker image enables Apache `mod_rewrite`, permits the app's rewrite rules, and installs GD/FreeType for 1200×630 Facebook story preview images. Rebuild and redeploy the image after changing these Apache settings.

Public and admin pages check for content changes every 20 seconds and refresh automatically when no form has unsaved changes. If a form has been edited, the page shows a refresh prompt instead of discarding the work.

- `DB_HOST`: managed MySQL hostname
- `DB_NAME`: application database name
- `DB_USER`: database username
- `DB_PASS`: database password
- `BASE_URL`: optional URL path prefix, such as `/archive`; leave unset to detect the app subfolder from the request path, or set it to an empty value when the site is served from the domain root
- `APP_URL`: public site origin, for example `https://archive.example.org` (scheme and hostname only, with no path). Set this in production so shared links use the canonical HTTPS domain.
- `DONATION_URL`: optional full HTTPS URL for the footer's archive-support button. Until configured, the footer says the donation link is coming soon.

Import the SQL schema into the managed database before starting the app. If the cloud platform uses replaceable containers or a read-only application filesystem, attach persistent writable storage for `/var/www/html/uploads` so submitted photos and story images survive deployments. Serve the site over HTTPS and keep database credentials in the platform's secret/environment configuration.

## Installation with XAMPP

1. Place this project in the XAMPP web root:

   ```text
   C:\xampp\htdocs\nepal-disaster-archive
   ```

2. Start **Apache** and **MySQL** from the XAMPP Control Panel.

3. Create the database by importing [`database/schema.sql`](database/schema.sql) in phpMyAdmin, or run it with the MySQL client. The script creates the `nepal_disaster_archive` database, tables, default categories, and a starter story.

4. Check the database settings in [`config/config.php`](config/config.php):

   ```php
   const DB_HOST = '127.0.0.1';
   const DB_NAME = 'nepal_disaster_archive';
   const DB_USER = 'root';
   const DB_PASS = '';
   const BASE_URL = '/nepal-disaster-archive';
   ```

   Update these values if your MySQL credentials or project folder differ.

5. Open the one-time administrator setup page:

   ```text
   http://localhost/nepal-disaster-archive/create-admin.php
   ```

6. Create the first administrator account, sign in, and then delete or rename `create-admin.php` from the server.

### Upgrade an existing installation

If the database was created before the explorer features were added, import [`database/upgrade-features.sql`](database/upgrade-features.sql) once in phpMyAdmin. This adds story images, district/province and map coordinates, the review status, and revision history.

For the complete feature set, import [`database/upgrade-all-features.sql`](database/upgrade-all-features.sql) after that migration. It adds gallery images, structured sources, correction reports, analytics events, and admin activity logs.

## Import starter stories in bulk

To add a curated starter dataset across all 13 default hazard categories, import [`database/seed-stories.sql`](database/seed-stories.sql) in phpMyAdmin after importing the main schema:

1. Open `http://localhost/phpmyadmin`.
2. Select the `nepal_disaster_archive` database.
3. Open the **Import** tab and choose `database/seed-stories.sql`.
4. Click **Import**.

The seed file is safe to run more than once because each story has a unique slug and uses `INSERT IGNORE`. It adds representative historical entries, not a complete record of every disaster in Nepal. Verify and expand the articles with authoritative sources before treating them as a definitive historical dataset.

## URLs

- Public archive: `http://localhost/nepal-disaster-archive/`
- Story page: `http://localhost/nepal-disaster-archive/story/story-slug`
- Staff login: `http://localhost/nepal-disaster-archive/admin/login`
- Admin dashboard: `http://localhost/nepal-disaster-archive/admin/`

## Editorial workflow

1. Sign in through the staff login page.
2. Create a story from the dashboard or Stories page.
3. Assign a category and add verified historical sources.
4. Save the story as a draft while it is being reviewed.
5. Change the status to `PUBLISHED` when it is ready for the public archive.
6. Mark important stories as featured when appropriate.

Historical dates, casualty figures, and magnitudes may vary between sources. Add source notes and clearly communicate uncertainty where appropriate.

## Project structure

```text
.
├── index.php              Public archive and search
├── story.php              Public story detail page
├── create-admin.php       One-time administrator setup
├── admin/                 Authenticated editorial interface
├── config/                Database and authentication helpers
├── database/schema.sql    Database schema and starter data
└── uploads/stories/       Story media upload directory
```


pratikkoiralazz (github & linkedin)