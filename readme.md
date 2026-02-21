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
* You have now installed Wordpress! With MAMP running, you may now visit [localhost:8888/wikitongues](localhost:8888/wikitongues).
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

* In admin ([localhost:8888/wikitongues/wp-admin/](localhost:8888/wikitongues/wp-admin/)) navigate to /plugins.
* Install [Advanced Custom Fields Pro](https://www.advancedcustomfields.com/resources/upgrade-guide-acf-pro/) by putting the plugin folder provided by the maintainer in `/wp-content/plugins/`.
* In the sidebar, click on `Add New Plugin`. Add the following plugins.
	* [Classic editor](https://wordpress.org/plugins/classic-editor/)
	* [Make Connector](https://wordpress.org/plugins/integromat-connector/)
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
- Following successful testing in Staging, create a pull request to merge `staging` into `production`.
- The merge triggers an automatic deployment to the Production environment.
- Monitor the deployment to ensure stability and functionality.

## Automatic Deployment

**Deployment Branches**
- `staging`: Automatically deploys to the Staging environment upon merge.
- `production`: Automatically deploys to the Production environment upon merge.

**Branch Protection**
- The `main` and `production` branches are protected and can only be modified via pull requests from other branches.
- This ensures that all changes are reviewed, tested, and approved before affecting live environments.

## DBs
Local database is postgres
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

2. Grant permissions to the database user (`wikitong` in this example) to allow local and remote access:
   ```sql
   GRANT ALL PRIVILEGES ON your_database_name.* TO 'wikitong'@'localhost' IDENTIFIED BY 'yourpassword';
   GRANT ALL PRIVILEGES ON your_database_name.* TO 'wikitong'@'%' IDENTIFIED BY 'yourpassword';
   FLUSH PRIVILEGES;
   ```
   Replace `your_database_name` and `yourpassword` with the appropriate values.

#### Step 3: Set Up Beekeeper Studio Connection
With the SSH tunnel running, configure Beekeeper Studio to connect to your database.

1. **Add New Connection**:
   - **Host**: `localhost`
   - **Port**: `3307` (or whichever local port you specified in the SSH tunnel command)
   - **Username**: Your database username (e.g., `wikitong`).
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
- **Access Denied Errors**: Double-check the database username and password. Ensure the user permissions are properly granted for remote access.
- **Connection Terminated Unexpectedly**: Verify the SSH tunnel is still running and that you are using the correct port (`3307` in this example).
- **Database User Not Allowed**: Use the MariaDB console to grant appropriate privileges (`GRANT ALL PRIVILEGES` commands) as described in Step 2.

## Database Sync
Work is underway to syncronize databases across environments. To sync your local database up with Prod, run `bash tool-sync-db-from-prod.sh` from your local terminal.

## Working with data
When importing data, there are 2 general approaches based on the objective:

1. bulk import and
1. pipeline import

For **bulk import**, write an importer for the target data, and prepare a csv of the bulk dataset. Import it with wp-cli.

For **pipeline import**, work with Make.com to create a scenario that keeps the dataset up to date with Airtable.
> **Important note:** For importing values that are post objects in airtable, 2 things are required:
> The post type needs to have access to the rest controller class WT_REST_Posts_Controller 'rest_controller_class' => 'WT_REST_Posts_Controller'
> AND the field keys on Make.com need to be prefixed with _WT_TMP_. This enables wordpress to intercept the update and handle associating the import with the appropriate post records.

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
- **PHPStan** performs type-safety analysis at **level 5** using [`szepeviktor/phpstan-wordpress`](https://github.com/szepeviktor/phpstan-wordpress) stubs for WordPress core functions. A baseline of pre-existing violations is maintained in `phpstan-baseline.neon`; CI fails only on _new_ violations. Run with `composer analyse`. Scope: `wp-content/themes/blankslate-child`, `wp-content/plugins/wt-gallery`, `wp-content/plugins/typeahead/typeahead.php`, and `tests/`.

## Layer 2 — Unit Tests

**Tools:** PHPUnit 9.6 + WP_Mock 1.1
**Runs:** on every PR (`test.yml`)
**Current count:** 58 tests, 91 assertions

Test files live in `tests/unit/`; bootstrap at `tests/bootstrap.php`. Covers isolated business logic — URL encoding, meta value fallbacks, search routing regex, pagination math:

- `import-captions.php` → `safe_dropbox_url()`, `get_safe_value()`
- `acf-helpers.php` → `wt_meta_value()`
- `search-filter.php` → `searchfilter()` regex routing
- `render_gallery_items.php` → `generate_gallery_pagination()`
- `wt-gallery/helpers.php` → `getDomainFromUrl()`
- `template-helpers.php` → `get_environment()`, `wt_prefix_the()`
- `events-filter.php` → `format_event_date_with_proximity()`
- `wt-gallery/includes/queries.php` → `build_gallery_query_args()`

> **Note:** WP_Mock 1.x is locked to PHPUnit ^9.6 due to a Patchwork incompatibility with PHPUnit 10+. The forward path is to push WP API calls to function edges so the logic core needs no mocking, then migrate to PHPUnit 10+ incrementally. See [plan.md](plan.md) for details.

## Security Scanning

**Tool:** TruffleHog
**Runs:** on every PR (`security.yml`)

Scans each PR diff for verified secrets (API keys, credentials). The action is pinned to a specific commit SHA for supply-chain safety. GitHub native secret scanning and push protection are also enabled on the repository.

## Future Layers

| Layer | Tools | Status |
|-------|-------|--------|
| Layer 3 — Integration Tests | PHPUnit + `WP_UnitTestCase`, MySQL in Docker | Planned (Phase 5) |
| Layer 4 — End-to-End & Visual Regression | Playwright | Planned (Phase 6) |
| Layer 5 — Data Integrity | WP-CLI custom command | Planned (Phase 7) |

See [plan.md](plan.md) for the full specification of each future layer.

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
   To update the component in this wordpress project, you'll have to update the /build/ directory from the component into the plugin directory here. This applies separately for integration, staging and production environments.

   Consider using `rsync` to facilitate the distribution.
   ``` bash
   rsync -avz --delete -e 'ssh -o StrictHostKeyChecking=no' ./build/ USERNAME@HOSTNAME:PATH/TO/plugins/typeahead/build/ && echo 'Done'
   ```

- ## **Custom Gallery**:

   This plugin handles all galleries for this project. It presently handles galleries for the following post types:

   - Languages
   - Videos
   - Resources
   - Fellows

   It lives in `/wp-content/plugins/wt-gallery`, and is organized as follows:
   ```
   wt-gallery.php
   /js/custom-gallery-ajax.js
   /includes/queries.php
   /includes/render_gallery_items.php
   /includes/templates/*
   ```

   ### Gallery instances

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

# Dependencies

## Font Awesome

We use [FontAwewsome](https://fontawesome.com/) to render social icons.

# Errors

Localhost db view error: `The user specified as a definer ('wikitong_master'@'localhost') does not exist`
1. `DROP VIEW IF EXISTS languages_view;`
1. Recreate the View

# Dependencies
We are still developing the best method to get this project set up.
Plugins
Blankslate theme

# To-Do

## Code structure and styles

- [] later - simplify if statement syntax ( a ? b : c); e.g.
`wp_nav_menu( array(
	'theme_location' => is_user_logged_in() ? 'logged-in-menu' : 'logged-out-menu'
) );`
- [] clean up template/modules hierarchy on video single and language single
- [] convert jquery to vanilla javascript

## global

- [] bug on search page title - title has first matching language iso (`Wikitongues | niv`) despite being search route (`?s=russian`).
- [] add alert banner and display only if user hasn't visited the site in a week
- [] build captions post type
- [] build single page template for partners post type
- [] add "about" drop down to header (footer only for launch)
- [] ADA accessibility evaluation
- [] blog integration
- [] browser notifications opt-in
- [x] track entire wordpress instance in git to capture plugin-specific (typeahead) changes
- [x] backwards compatibility evaluation

## search results

- [] sort results by language first, then video, then lexicons, then resources - or, alternatively, divide results into sections with language videos, language pages, etc - to make it easier on the eyes

## team member post type

- [] add: historical interns, other secondary team data

## languages single

- [] inlcude more clarity for external resources
- [] add continent of origin

## video single

- [] toggle metadata view for for more than 1 language
- [] toggle all metadata view on mobile
- [] once captions post type is live, add download feature
- [] figure out embeds for Dropbox files (not on YouTube)

## archive

- [] language collection pages - probably page templates with customized for-loops baased on ACF fields  (need to define what we want to sort by)

## fellows single

- [] micro-blogging feature

## revitalization toolkit

- [] toolkit newsletter propt
- [] toolkit language prompt
- [] toolkit donate prompt
