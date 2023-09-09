# theme structure

* primary functions are stored in functions.php
* secondary/plug-in functions are stored in /includes
* recurring UI elements are stored as unique php files in /modules
* css is broken down into separate files that correspond to php files
* php files named by wp page/template type or module/element type with -- modifiers
* ACF organized into groups by corresponding post type, page template, or global group, with post type or page template name as prefix 

# php style guidelines

* we use { } for continguous php and :/else:/endif; for PHP broken up by html
* https://make.wordpress.org/core/handbook/best-practices/coding-standards/php/
* in-line php lines up with previous html element (if statements)
* line-breaks between php and in-line html

# to-do

* write out pseudo code for all php files, including modules
* connect all relevant php files with includes
* revise/creat ACF fields and populate with data
* write html/php markup
* desktop css
* mobile/responsive css
* soft launch
* build about and team pages
* build template for url redirects
* later - simplify if statement syntax ( a ? b : c); e.g.
`wp_nav_menu( array(
	'theme_location' => is_user_logged_in() ? 'logged-in-menu' : 'logged-out-menu'
) );`
* add alert banner and display only if user hasn't visited the site in a week
* consider consolidating "link" ACF fields
* build captions post type and download options
* add "primary language" field to video post types
* make testimonial carousel a true carousel
* search results: sort results by language first, then video, then lexicons, then resources - or, alternatively, divide results into sections with language videos, language pages, etc - to make it easier on the eyes
* clean up template/modules hierarchy on video single and language single
* think about primary v secondary languages as part of "featured languages" video single
* figure out navigation path from single videos back to the language in question
* add: historical interns, other secondary team data
* add: "about" drop down to header (footer only for launch)
* partner single page with information about partnership
* inlcude more clarify for external resources