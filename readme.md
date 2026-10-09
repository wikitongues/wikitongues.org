# Setup
**1. Installing The Server**
* Download and install [MAMP](https://www.mamp.info/). The free version is enough.
* On a Mac, MAMP will create a directory called MAMP in your Applications directory. In there, you will find a directory called `htdocs`.

**2. Installing Wordpress**
* Request the database name `$name` from the maintainer.
* Initialize a [Wordpress installation](https://documentation.mamp.info/en/MAMP-Mac/FAQ/How-do-I-install-WordPress/).
	* In step 5, name your database according to the database `$name` provided by the maintainer.
	* In step 6 item 3, be sure to use `$name` as the database name as well.
* Optionally rename the newly created `Wordpress` directory to `wikitongues`.
* You have now installed Wordpress! With MAMP running, you may now visit [localhost:8888/wikitongues](http://localhost:8888/wikitongues).
> _(**Note:** The address will match your directory name)_

**3. Setting Up Version Control**
* Within your project directory, initialize Github and add this repository as the origin remote. Pull default branch `main`.
	* ``` bash
	   git init
	   git remote add "origin" git@github.com:wikitongues/wikitongues.git
	   git pull main
	   git co main
	   git branch -d master```
> _(**Note:** The project's primary branch is called '**main**', not 'master'.)_

**4. Installing Dev Tooling**

Install [Composer](https://getcomposer.org/) if you don't have it:
```bash
brew install composer
```

From the project root, install PHP and JS dev dependencies:
```bash
composer install
npm install
```

This provides:
- `composer lint` — PHPCS with WordPress Coding Standards (PHP linting)
- `composer lint:fix` — auto-fix PHPCS violations
- `composer analyse` — PHPStan type-safety analysis
- `composer test` — PHPUnit unit tests
- `npm run lint:js` — ESLint on custom JS files
- `npm run stylus:watch` — compile CSS on file change
- `npm run stylus:build` — compile CSS once

**5. Setting Up Plugins**

* In admin ([localhost:8888/wikitongues/wp-admin/](http://localhost:8888/wikitongues/wp-admin/)) navigate to /plugins.
* Install [Advanced Custom Fields Pro](https://www.advancedcustomfields.com/resources/upgrade-guide-acf-pro/) by putting the plugin folder provided by the maintainer in `/wp-content/plugins/`.
* In the sidebar, click on `Add New Plugin`. Add the following plugins.
	* [Classic editor](https://wordpress.org/plugins/classic-editor/)
	* [Make Connector](https://wordpress.org/plugins/integromat-connector/) (legacy; not needed locally, since the Airtable sync now runs through the `wt-airtable-sync` plugin)
	* [WPS Hide Login](https://wordpress.org/plugins/wps-hide-login/) provides increased security to the site by masking the admin url.
* After adding each plugin, it needs to be activated. This includes the plugins already available via Github.

**6. Populating The Database**
* Install [wp-cli](https://wp-cli.org/)
* Talk to the maintainer to get a version of the prod sync script `tool-sync-db-from-prod.sh`.
* Confirm tool is executable.
  ``` bash
  chmod +x tool-sync-db-from-prod.sh
  ```
* Make sure local path is correctly configured.
* Pull prod db (mamp has to be running). Check prod connection. run `bash tool-sync-db-from-prod.sh`.

**7. Configurations**
* Add logging to your `wp-config.php`
	``` php
	define('WP_DEBUG', true);
	define('WP_DEBUG_LOG', true);
	define('WP_DEBUG_DISPLAY', true);
	```
 This will make it such that all system logs get printed to `wp-content/debug.log`.
* Confirm other wp-config parameters with the maintainer.

> _**Note:** We dont have direct experience on Windows. Please let us know if you wish to try that out and submit your steps to this readme via PR._

# Theme Structure
* primary functions are stored in functions.php
* secondary/plug-in functions are stored in /includes
* recurring UI elements are stored as unique php files in /modules
* css is broken down into separate files that correspond to php files
* php files named by wp page/template type or module/element type with -- modifiers
* ACF organized into groups by corresponding post type, page template, or global group, with post type or page template name as prefix

# Code Style Guidelines
*PHP*
* we use { } for continguous php and :/else:/endif; for PHP broken up by html
* https://make.wordpress.org/core/handbook/best-practices/coding-standards/php/
* in-line php lines up with previous html element (if statements)
* line-breaks between php and in-line html

*CSS*
* Use tabs, not spaces for Stylus files.

# Continuous Integration and Deployment
This project follows a structured Continuous Integration and Continuous Deployment (CI/CD) process, utilizing four primary environments to ensure seamless development, testing, and production deployment.

### Environments
**Local**
- MySQL via MAMP (local)
- Serves as the integration branch where feature branches are merged.
- Ensures that features are tested and stable before further deployment.

**Staging**
- MariaDB hosted on GreenGeeks
- URL: [staging.wikitongues.org](https://staging.wikitongues.org)
- A live testing environment where integrated features from `main` are deployed.
- Used for end-to-end testing in a production-like setting before going live.

**Production**
- MariaDB hosted on GreenGeeks
- URL: [wikitongues.org](https://wikitongues.org)
- The public-facing environment where fully tested and approved features are deployed.
- Direct pushes to `production` are prohibited to maintain stability.

**Note:** [Instructions for setting up a new Staging Environment on Greengeeks](https://www.greengeeks.com/tutorials/how-to-set-up-a-wordpress-development-environment-and-why/)

## Workflow
### Branches:

**Feature Development**
- Begin by branching off from `main` to develop new features.
- Regularly push updates to the feature branch on GitHub for peer review and testing.
> **Notes:** Branches should start with one of: `feature/` or `fix/` based on the purpose. Branches starting with `_` are considered inactive.

**Main**
- Once a feature is ready, submit a pull request to merge the feature branch into `main`.
- Resolve any conflicts, run tests, and ensure the integration is smooth.

**Staging**
- After merging into `main`, create a pull request to merge `main` into `staging`.
- Automatic deployment to the Staging environment allows for live testing.
- Conduct thorough testing and verification in the Staging environment.

**Production**
- Following successful testing in Staging, create a pull request to merge `main` into `production`.
- The merge triggers an automatic deployment to the Production environment.
- Monitor the deployment to ensure stability and functionality.

## Automatic Deployment

**Deployment Branches**
- `staging`: Automatically deploys to the Staging environment upon merge.
- `production`: Automatically deploys to the Production environment upon merge.

What a deploy does, and the manual steps some changes need afterwards, are in [docs/deployment.md](docs/deployment.md).

**Branch Protection**
- The `main`, `staging` and `production` branches are protected and can only be modified via pull requests from other branches.
- This ensures that all changes are reviewed, tested, and approved before affecting live environments.

## DBs
Local database is MySQL (via MAMP)
Staging and Production databases are MariaDB
Set up Beekeeper Studio or Equivalent DB client to interact with databases programmatically.

### Setting Up Remote Access to MariaDB via SSH Tunnel

#### Overview
This guide provides step-by-step instructions for setting up and accessing a remote MariaDB database using an SSH tunnel. The steps outlined here ensure that you can connect securely to the database, such as for using database management tools like Beekeeper Studio.

#### Prerequisites
- SSH access to your server (e.g., GreenGeeks hosting).
- Database credentials (username, password) found in your `wp-config.php` file.
- A database management tool, like **Beekeeper Studio**.

#### Step 1: Set Up SSH Tunnel
To create a secure SSH tunnel between your local machine and the server, run the following command:

```bash
ssh -L 3307:127.0.0.1:3306 yourusername@yourserver.com
```

- **3307**: The port on your local machine that will be used for accessing the remote database.
- **127.0.0.1:3306**: The remote server's address and port for MariaDB.
- **yourusername@yourserver.com**: Your SSH username and server domain or IP address.

Make sure to leave the terminal window with this command running. Closing it will terminate the tunnel.

#### Step 2: Verify Database User Permissions
Once you have SSH access, log into the MariaDB console to ensure that the database user has the proper permissions.

1. Log in to the MariaDB console:
   ```bash
   mysql -u root -p
   ```
   Enter your root password when prompted.

2. Grant permissions to the database user (`db_user` in this example). The tunnel connects from the server itself, so a `'localhost'` grant is all it needs:
   ```sql
   GRANT ALL PRIVILEGES ON your_database_name.* TO 'db_user'@'localhost' IDENTIFIED BY 'yourpassword';
   FLUSH PRIVILEGES;
   ```
   Replace `your_database_name` and `yourpassword` with the appropriate values. Don't grant `'%'` (any host): the tunnel doesn't need it, and it opens the database to remote logins.

#### Step 3: Set Up Beekeeper Studio Connection
With the SSH tunnel running, configure Beekeeper Studio to connect to your database.

1. **Add New Connection**:
   - **Host**: `localhost`
   - **Port**: `3307` (or whichever local port you specified in the SSH tunnel command)
   - **Username**: Your database username (e.g., `db_user`).
   - **Password**: Your database password (from `wp-config.php`).
   - **Database**: The name of your WordPress database.

2. **Save and Test**: Save the connection and click "Test Connection" to verify that everything is set up correctly.

#### How to Access the Database in the Future
- **Run the SSH Tunnel Command**: Before accessing the database through Beekeeper Studio, you need to set up the SSH tunnel:
  ```bash
  ssh -L 3307:127.0.0.1:3306 yourusername@yourserver.com
  ```
  Ensure the terminal session remains open.

- **Open Beekeeper Studio**: Use the saved connection details to access your database.

##### Reminder: SSH Tunnel Must Be Running
Database access through Beekeeper Studio is only possible while the SSH tunnel is running. Make sure the terminal session stays active throughout your database session.

#### Troubleshooting
- **Access Denied Errors**: Double-check the database username and password. Ensure the user has the `'localhost'` grant from Step 2.
- **Connection Terminated Unexpectedly**: Verify the SSH tunnel is still running and that you are using the correct port (`3307` in this example).
- **Database User Not Allowed**: Use the MariaDB console to grant appropriate privileges (`GRANT ALL PRIVILEGES` commands) as described in Step 2.

## Database Sync
To sync your local database with production, run `bash tool-sync-db-from-prod.sh` from your local terminal. Staging syncs from production weekly. Before any migration or bulk write, refresh both from production first; see [docs/staging-sync.md](docs/staging-sync.md).

## Working with data
When importing data, there are 2 general approaches based on the objective:

1. bulk import and
1. pipeline import

For **bulk import**, write an importer for the target data, and prepare a csv of the bulk dataset. Import it with wp-cli.

For **pipeline import**, Make.com posts each changed Airtable record to the `wt-airtable-sync` plugin, which maps the fields and resolves relationships in code. See [docs/airtable-sync.md](docs/airtable-sync.md). (The old `_WT_TMP_` and `WT_REST_Posts_Controller` pattern was retired in March 2026.)

# Testing

The project uses a layered testing strategy. All checks run automatically on every pull request targeting `main` via GitHub Actions.

## Commands

| Command | What it runs |
|---------|-------------|
| `composer lint` | PHPCS — WordPress Coding Standards (PHP style + security sniffs) |
| `composer lint:fix` | PHPCBF — auto-fix PHPCS violations |
| `composer analyse` | PHPStan — PHP type-safety analysis |
| `composer test` | PHPUnit — unit tests |
| `npm run lint:js` | ESLint — JavaScript style |

## Layer 1 — Static Analysis

**Tools:** PHPCS + WordPress Coding Standards, ESLint, PHPStan
**Runs:** on every PR (`lint.yml`)

- **PHPCS** enforces WordPress coding standards and catches basic security anti-patterns (unescaped output, direct DB queries). Run with `composer lint`; auto-fix with `composer lint:fix`.
- **ESLint** checks custom JavaScript files. Run with `npm run lint:js`.
- **PHPStan** performs type-safety analysis at **level 5** using [`szepeviktor/phpstan-wordpress`](https://github.com/szepeviktor/phpstan-wordpress) stubs for WordPress core functions. A baseline of pre-existing violations is maintained in `phpstan-baseline.neon`; CI fails only on _new_ violations. Run with `composer analyse`. Scope: `wp-content/themes/blankslate-child`, `wp-content/plugins/wt-gallery`, `wp-content/plugins/wt-airtable-sync`, `wp-content/plugins/download-gateway`, `wp-content/plugins/typeahead/typeahead.php`, and `tests/`.

## Layer 2 — Unit Tests

**Tools:** PHPUnit 9.6 + WP_Mock 1.1
**Runs:** on every PR (`test.yml`)
**Current count:** 223 tests, 328 assertions (2026-09-13)

Test files live in `tests/unit/`, with the download gateway's in `tests/unit/download-gateway/`; the bootstrap is `tests/bootstrap.php`. They cover isolated business logic: theme helpers, gallery query building, and the gateway's controllers, resolvers and repositories. [docs/testing-strategy.md](docs/testing-strategy.md) lists what each test class covers.

> **Note:** WP_Mock 1.x is locked to PHPUnit ^9.6 due to a Patchwork incompatibility with PHPUnit 10+. The forward path is to push WP API calls to function edges so the logic core needs no mocking, then migrate to PHPUnit 10+ incrementally. See [docs/testing-strategy.md](docs/testing-strategy.md) for details.

## Security Scanning

**Tool:** TruffleHog
**Runs:** on every PR (`security.yml`)

Scans each PR diff for verified secrets (API keys, credentials). The action is pinned to a specific commit SHA for supply-chain safety. GitHub native secret scanning and push protection are also enabled on the repository.

## Future Layers

| Layer | Tools | Status |
|-------|-------|--------|
| Layer 3 — Integration Tests | PHPUnit + `WP_UnitTestCase`, MySQL in Docker | Planned |
| Layer 4 — End-to-End & Visual Regression | Playwright | Planned |
| Layer 5 — Data Integrity | WP-CLI custom command | Planned |

See [docs/testing-strategy.md](docs/testing-strategy.md) for each layer's scope, and [plan.md](plan.md) (Engineering foundations) for where it sits in the queue.

# CSS and Compiling Stylus
This project uses [Stylus](https://stylus-lang.com/), a CSS pre-processor.
Stylus needs to be compiled into CSS before it is usable in HTML.
Stylus is included as a dev dependency — install it along with all other dev tooling by running `npm install` from the project root.

To watch and recompile on change during local development:
```bash
npm run stylus:watch
```

To compile once:
```bash
npm run stylus:build
```

# Plugins

Some of our advanced features are maintained as custom plugins. At present, we have:

- ## **Typeahead Search**:

   This project uses a React search component maintained in a [separate repository](https://github.com/wikitongues/typeahead/tree/main).
   To update the component, copy its `build/` directory into `wp-content/plugins/typeahead/build/` and commit it. The normal deploy ships it to staging and production and removes stale build files ([docs/deployment.md](docs/deployment.md)).

- ## **Custom Gallery**:

   This plugin handles all galleries for this project, for any post type with a template in `includes/templates/` (languages, videos, fellows, people, territories, lexicons, resources, careers, faq). Full reference: [docs/gallery.md](docs/gallery.md).

   It lives in `/wp-content/plugins/wt-gallery`, and is organized as follows:
   ```
   wt-gallery.php
   /js/custom-gallery-ajax.js
   /includes/queries.php
   /includes/render_gallery_items.php
   /includes/templates/*
   ```

   ### Gallery instances

   _This table is a March 2026 snapshot and is no longer maintained; some of its templates have since been removed. [docs/gallery.md](docs/gallery.md#where-galleries-are-used) explains how to find current call sites._

   Every call to `create_gallery_instance()` across the theme. The `link_out` field must be present in every params array (set to `''` when unused); the plugin renders the "See all" button only when `link_out` is non-empty **and** `found_posts > posts_per_page`.

   | File | Gallery title | Post type | `link_out` |
   |------|--------------|-----------|------------|
   | `single-territories.php` | Fellows from [territory] | fellows | `?territory=<slug>` → archive-fellows.php |
   | `single-territories.php` | Languages of [territory] | languages | `?territory=<slug>` → archive-languages.php |
   | `single-languages.php` | Other languages from [territory] | languages | `?territory=<slug>` → archive-languages.php |
   | `modules/languages/single-languages__videos.php` | [Language] videos | videos | `?language=<slug>` → archive-videos.php |
   | `modules/languages/single-languages__fellows.php` | [Language] Revitalization Projects | fellows | — |
   | `modules/languages/single-languages__lexicons.php` | [Language] to other languages | lexicons | — |
   | `modules/languages/single-languages__lexicons.php` | Other languages to [language] | lexicons | — |
   | `modules/languages/single-languages__resources.php` | [Language] external resources | resources | — |
   | `archive-fellows.php` | Fellows from [territory] (filtered) | fellows | — |
   | `archive-fellows.php` | Fellows from [region] (filtered) | fellows | — |
   | `archive-fellows.php` | Fellows (unfiltered) | fellows | `/revitalization/fellows` |
   | `archive-languages.php` | Languages of [territory] | languages | — |
   | `archive-languages.php` | [Genealogy] languages | languages | — |
   | `archive-languages.php` | [Writing system] languages | languages | — |
   | `archive-languages.php` | Languages (unfiltered) | languages | — |
   | `archive-videos.php` | [Language] videos (filtered) | videos | — |
   | `archive-videos.php` | Videos (unfiltered) | videos | — |
   | `single-videos.php` | Other videos of [language] | videos | — |
   | `single-fellows.php` | Other fellows from [year] | fellows | — |
   | `taxonomy-region.php` | Fellows from [region] | fellows | `?region=<slug>` → archive-fellows.php |
   | `taxonomy-region.php` | Territories in [region] | territories | — |
   | `template-revitalization-fellows.php` | Fellows by cohort year | fellows | — |
   | `taxonomy-fellow-category.php` | Fellows by category | fellows | — |
   | `template-about-staff.php` | Explore careers | careers | — |
   | `template-about-staff.php` | Explore other opportunities | careers | — |
   | `template-giving-campaign-24.php` | [ACF: custom_gallery_title] | fellows | — |
   | `modules/flexible-content/gallery-layout.php` | [ACF: custom_gallery_title] | [ACF type] | — |

- ## **Download Gateway**:

   Logs every download of a video, caption or document, can ask for a name and email first, and forwards contacts and downloads to Airtable through Make.com. It lives in `/wp-content/plugins/download-gateway`; the full reference is [docs/download-gateway.md](docs/download-gateway.md).

- ## **Airtable Sync**:

   Receives Airtable changes from Make.com and writes languages, videos, captions and lexicons to WordPress. It lives in `/wp-content/plugins/wt-airtable-sync`; the full reference is [docs/airtable-sync.md](docs/airtable-sync.md).

# Dependencies

## Icons

Icons are inline SVGs returned by `wt_icon()`. Font Awesome was removed in February 2026.

# Errors

Localhost db view error: `The user specified as a definer ('<production db user>'@'localhost') does not exist`
1. `DROP VIEW IF EXISTS languages_view;`
1. Recreate the View

# Dependencies
We are still developing the best method to get this project set up.
Plugins
Blankslate theme

# To-Do

Planned work lives in [plan.md](plan.md). The ideas that used to be listed here were moved to its Ideas list on 2026-09-13.
