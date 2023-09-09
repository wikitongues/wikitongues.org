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

* code structure and styles
	* later - simplify if statement syntax ( a ? b : c); e.g.
	`wp_nav_menu( array(
		'theme_location' => is_user_logged_in() ? 'logged-in-menu' : 'logged-out-menu'
	) );`
	* clean up template/modules hierarchy on video single and language single
* global
	* add alert banner and display only if user hasn't visited the site in a week
	* build captions post type
	* build single page template for partners post type
	* add: "about" drop down to header (footer only for launch)
* home
	* make testimonial carousel a true carousel
* search results
	* sort results by language first, then video, then lexicons, then resources - or, alternatively, divide results into sections with language videos, language pages, etc - to make it easier on the eyes
* team member post type
	* add: historical interns, other secondary team data
* languages single
	* inlcude more clarity for external resources
* video single
	* toggle metadata view for for more than 1 language
	* toggle all metadata view on mobile
	* once captions post type is live, add download feature
	* figure out navigation path from single videos back to the language in question