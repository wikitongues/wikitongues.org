# Theme Structure

* primary functions are stored in functions.php
* secondary/plug-in functions are stored in /includes
* recurring UI elements are stored as unique php files in /modules
* css is broken down into separate files that correspond to php files
* php files named by wp page/template type or module/element type with -- modifiers
* ACF organized into groups by corresponding post type, page template, or global group, with post type or page template name as prefix

# PHP style guidelines

* we use { } for continguous php and :/else:/endif; for PHP broken up by html
* https://make.wordpress.org/core/handbook/best-practices/coding-standards/php/
* in-line php lines up with previous html element (if statements)
* line-breaks between php and in-line html

# Continuous Integration and Deployment (CI/CD)

This project follows a structured Continuous Integration and Continuous Deployment (CI/CD) process, utilizing four primary environments to ensure seamless development, testing, and production deployment.

## Environments

1. **Feature**:
   - Used for developing and testing new features in isolated branches.
   - Feature branches should be regularly pushed to GitHub for visibility and collaboration.

2. **Main**:
   - Serves as the integration branch where feature branches are merged.
   - Ensures that features are tested and stable before further deployment.

3. **Staging**:
   - URL: [staging.wikitongues.org](https://staging.wikitongues.org)
   - A live testing environment where integrated features from `main` are deployed.
   - Used for end-to-end testing in a production-like setting before going live.

4. **Production**:
   - URL: [wikitongues.org](https://wikitongues.org)
   - The public-facing environment where fully tested and approved features are deployed.
   - Direct pushes to `production` are prohibited to maintain stability.

## Workflow

1. **Feature Development**:
   - Begin by branching off from `main` to develop new features.
   - Regularly push updates to the feature branch on GitHub for peer review and testing.

2. **Integration**:
   - Once a feature is ready, submit a pull request to merge the feature branch into `main`.
   - Resolve any conflicts, run tests, and ensure the integration is smooth.

3. **Staging Deployment**:
   - After merging into `main`, create a pull request to merge `main` into `staging`.
   - Automatic deployment to the Staging environment allows for live testing.
   - Conduct thorough testing and verification in the Staging environment.

4. **Production Deployment**:
   - Following successful testing in Staging, create a pull request to merge `staging` into `production`.
   - The merge triggers an automatic deployment to the Production environment.
   - Monitor the deployment to ensure stability and functionality.

## Automatic Deployment

- **Deployment Branches**:
  - `staging`: Automatically deploys to the Staging environment upon merge.
  - `production`: Automatically deploys to the Production environment upon merge.

- **Branch Protection**:
  - The `main` and `production` branches are protected and can only be modified via pull requests from other branches.
  - This ensures that all changes are reviewed, tested, and approved before affecting live environments.


# Setting up new Staging

- Read instructions [here](https://www.greengeeks.com/tutorials/how-to-set-up-a-wordpress-development-environment-and-why/)

# CSS and Compiling Stylus

This project uses [Stylus](https://stylus-lang.com/), a CSS pre-processor.
Stylus needs to be compiled into CSS before it is usable in HTML.
To run the compiler, run `stylus -w stylus` in the terminal.

# Plugins

- **Typeahead Search**:

   This project uses a React search component maintained in a [separate repository](https://github.com/wikitongues/typeahead/tree/main).
   To update the component in this wordpress project, you'll have to update the /build/ directory from the component into the plugin directory here. This applies separately for integration, staging and production environments. Consider using `rsync` to facilitate the distribution. Additionally, the plugin has a PHP file that is not currently tracked on Git due to this repository being only a subset of the full installation. We may want to change this later.

- **Custom Gallery**:

   This plugin handles all galleries for this project.

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
- [] track entire wordpress instance in git to capture plugin-specific (typeahead) changes
- [] add alert banner and display only if user hasn't visited the site in a week
- [] build captions post type
- [] build single page template for partners post type
- [] add: "about" drop down to header (footer only for launch)
- [x] backwards compatibility evaluation
- [] ADA accessibility evaluation
- [] blog integration
- [] browser notifications opt-in

## home

- [] make homepage banner a carousel
- [x] make testimonial carousel a true carousel

## search results

- [] sort results by language first, then video, then lexicons, then resources - or, alternatively, divide results into sections with language videos, language pages, etc - to make it easier on the eyes

## team member post type

- [] add: historical interns, other secondary team data

## languages single

- [] inlcude more clarity for external resources
- [x] related languages carousel
- [] add continent of origin

## video single

- [] toggle metadata view for for more than 1 language
- [] toggle all metadata view on mobile
- [] once captions post type is live, add download feature
- [x] figure out navigation path from single videos back to the language in question
- [] figure out embeds for Dropbox files (not on YouTube)
- [x] related videos carousel

## archive

- [] language collection pages - probably page templates with customized for-loops baased on ACF fields  (need to define what we want to sort by)

## fellows single

- [] micro-blogging feature
- [x] other fellows carousel

## revitalization toolkit

- [] toolkit newsletter propt
- [] toolkit language prompt
- [] toolkit donate prompt