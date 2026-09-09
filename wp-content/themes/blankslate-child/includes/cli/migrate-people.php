<?php
/**
 * WP-CLI: migrate the `team` CPT to `people`.
 *
 * Moves posts onto the renamed post type, seeds the `people-type` taxonomy,
 * derives each person's type(s) from the curated relationship fields the old
 * Board/Advisors/Staff templates used, and repoints those pages at
 * template-people.php with equivalent people sections composed in editorial
 * content.
 *
 * Nothing is deleted: the old `board_members`, `staff_members`,
 * `interns_and_volunteers` and `team_banner_*` meta is left in place so the
 * change can be reverted. Sweep it once the result is confirmed in production.
 *
 * @package Wikitongues
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * The people-type terms this migration relies on.
 *
 * Further types (donor) are added through the People Types admin screen; they
 * do not need a deploy.
 *
 * @return array<string,string> slug => name
 */
function wt_people_type_terms() {
	return array(
		'board-member' => 'Board Member',
		'staff'        => 'Staff',
		'volunteer'    => 'Volunteer',
		'advisor'      => 'Advisor',
		'former-board' => 'Former Board Member',
	);
}

/**
 * People types supplied during review rather than derived from the database.
 *
 * The retired templates never listed former board members, so those names
 * cannot be inferred from anything stored — they are recorded here to keep
 * localhost, staging and production consistent. Several of them also advise.
 *
 * Names are matched on post title, accent-insensitively, so "Przegalinska"
 * finds "Przegalińska".
 *
 * @return array<string,string[]> person name => people-type slugs
 */
function wt_review_people_types() {
	return array(
		'Alolita Sharma'          => array( 'former-board', 'advisor' ),
		'Aleksandra Przegalinska' => array( 'former-board', 'advisor' ),
		'Casson Trenor'           => array( 'former-board' ),
		'Dario Maestro'           => array( 'former-board', 'advisor' ),
		'Jamie Joyce'             => array( 'former-board' ),
		'Lindie Botes'            => array( 'former-board' ),
		'Menghis Bairu'           => array( 'former-board' ),
		'Richard Chin'            => array( 'former-board' ),
		'Wade Davis'              => array( 'former-board', 'advisor' ),
	);
}

/**
 * Spelling corrections to person records, found during review.
 *
 * @return array<string,string> stored title => corrected title
 */
function wt_people_name_fixes() {
	return array(
		'Nicholas Paul Brysiewic' => 'Nicholas Paul Brysiewicz',
	);
}

/**
 * Titles supplied at review.
 *
 * Every advisor and former board member either had no leadership_title or
 * carried the placeholder "Board of Directors", which reads as redundant under
 * a heading that already says Former board members. These were researched and
 * confirmed with the team.
 *
 * House style for this field is the organisation alone rather than a job
 * title, matching the values already stored ("Queens Library", "Hebrew Union
 * College"), except where a person is better described by what they do — the
 * field already holds "Non-Profit Executive" and "Design Volunteer" too.
 *
 * @return array<string,string> person name => leadership_title
 */
function wt_review_people_titles() {
	return array(
		'Aleksandra Przegalinska'  => 'Kozminski University',
		'Casson Trenor'            => 'Strategic advisor',
		'Dario Maestro'            => 'Surveillance Technology Oversight Project',
		'Everette Jordan'          => 'Translation expert',
		'Heather Meeker'           => 'OSS Capital',
		'Krupa Shinde-Covert'      => 'Philanthropy advisor',
		'Menghis Bairu'            => 'Biotechnology entrepreneur',
		'Myrna Cunningham'         => 'FILAC',
		'Nicholas Paul Brysiewicz' => 'Eternity Foundation',
		'Richard Chin'             => 'Ascendant Venture',
		'Satdeep Gill'             => 'Wikimedia Foundation',
	);
}

/**
 * Which page contributed which type, and how each page is rebuilt.
 *
 * `sources` maps a legacy relationship meta key to the people-type slug its
 * members should receive. `sections` describes the editorial rows that
 * reproduce the page.
 *
 * @return array<int,array<string,mixed>>
 */
function wt_people_page_map() {
	return array(
		15076 => array(
			'label'    => 'Board',
			'sources'  => array( 'board_members' => 'board-member' ),
			'sections' => array(
				array(
					'type'    => 'people',
					'term'    => 'board-member',
					'layout'  => 'wide',
					'columns' => 2,
					'title'   => '',
				),
				array(
					'type'    => 'people',
					'term'    => 'former-board',
					'layout'  => 'list',
					'columns' => 3,
					'title'   => 'Former board members',
				),
			),
		),
		15078 => array(
			'label'    => 'Advisors',
			'sources'  => array( 'board_members' => 'advisor' ),
			'sections' => array(
				array(
					'type'    => 'people',
					'term'    => 'advisor',
					'layout'  => 'list',
					'columns' => 2,
					'title'   => '',
				),
			),
		),
		15080 => array(
			'label'    => 'Staff and Volunteers',
			'sources'  => array(
				'staff_members'          => 'staff',
				'interns_and_volunteers' => 'volunteer',
			),
			'sections' => array(
				array(
					'type'    => 'people',
					'term'    => 'staff',
					'layout'  => 'wide',
					'columns' => 2,
					'title'   => 'Staff',
				),
				array(
					'type'     => 'careers',
					'term'     => 'staff',
					'columns'  => 1,
					'per_page' => 3,
					'title'    => 'Explore careers',
				),
				array(
					'type'    => 'people',
					'term'    => 'volunteer',
					'layout'  => 'grid',
					'columns' => 2,
					'title'   => 'Interns and Volunteers',
				),
				array(
					'type'     => 'careers',
					'term'     => 'intern,volunteer',
					'columns'  => 1,
					'per_page' => 3,
					'title'    => 'Explore other opportunities',
				),
			),
		),
	);
}

/**
 * Migrate the team CPT to people.
 *
 * Idempotent at every phase, so it is safe to re-run on localhost, staging and
 * production.
 *
 * ## OPTIONS
 *
 * [--execute]
 * : Write the changes. Without it the command only reports what it would do.
 *
 * ## EXAMPLES
 *
 *     wp wt migrate-people --allow-root
 *     wp wt migrate-people --execute --allow-root
 *
 * @param array<int,string>    $args       Positional arguments (unused).
 * @param array<string,string> $assoc_args Associative arguments.
 * @return void
 */
function wt_migrate_people( $args, $assoc_args ) {
	global $wpdb;

	$execute = isset( $assoc_args['execute'] );
	WP_CLI::log( $execute ? 'Migrating team -> people.' : 'Dry run — pass --execute to write.' );

	// -------------------------------------------------- 1. rename the post type
	$stale = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s", 'team' ) );
	WP_CLI::log( sprintf( '  posts    %d post(s) still typed `team`', $stale ) );

	if ( $execute && $stale ) {
		// Direct UPDATE rather than wp_update_post(): this is a bulk retype and
		// there is no reason to fire save_post for every row.
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s", 'team' ) );
		$wpdb->update( $wpdb->posts, array( 'post_type' => 'people' ), array( 'post_type' => 'team' ) );
		foreach ( $ids as $id ) {
			clean_post_cache( (int) $id );
		}
		WP_CLI::log( sprintf( '           retyped %d post(s)', count( $ids ) ) );
	}

	// ------------------------------------------------------ 2. seed the terms
	$term_ids = array();
	foreach ( wt_people_type_terms() as $slug => $name ) {
		$existing = term_exists( $slug, 'people-type' );
		if ( $existing ) {
			$term_ids[ $slug ] = (int) $existing['term_id'];
			WP_CLI::log( sprintf( '  term     %s — exists', $slug ) );
			continue;
		}

		WP_CLI::log( sprintf( '  term     %s — %s', $slug, $execute ? 'creating' : 'would create' ) );
		if ( $execute ) {
			$created = wp_insert_term( $name, 'people-type', array( 'slug' => $slug ) );
			if ( is_wp_error( $created ) ) {
				WP_CLI::error( $created->get_error_message() );
			}
			$term_ids[ $slug ] = (int) $created['term_id'];
		}
	}

	// -------------------------------------------- 3. type people from the pages
	$assignments = array();
	foreach ( wt_people_page_map() as $page_id => $page ) {
		foreach ( $page['sources'] as $meta_key => $slug ) {
			// Read raw meta: the field groups that defined these relationships
			// are retired, so get_field() can no longer resolve them.
			$members = maybe_unserialize( get_post_meta( $page_id, $meta_key, true ) );
			foreach ( (array) $members as $member_id ) {
				$member_id = (int) $member_id;
				if ( $member_id ) {
					$assignments[ $member_id ][ $slug ] = true;
				}
			}
		}
	}

	WP_CLI::log( sprintf( '  types    %d person(s) derived from the curated page lists', count( $assignments ) ) );
	foreach ( $assignments as $member_id => $slugs ) {
		$slugs = array_keys( $slugs );
		WP_CLI::log( sprintf( '           %-32s %s', get_the_title( $member_id ), implode( ', ', $slugs ) ) );
		if ( $execute ) {
			// append = true so a person on several lists keeps every type.
			wp_set_object_terms( $member_id, $slugs, 'people-type', true );
		}
	}

	// Count both types: during a dry run the posts have not been retyped yet.
	$total   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN ( %s, %s ) AND post_status = %s", 'team', 'people', 'publish' ) );
	$untyped = max( 0, $total - count( $assignments ) );
	WP_CLI::log( sprintf( '           %d of %d published person(s) get no type — they appear on no page today either', $untyped, $total ) );

	// ------------------------------------------- 3a. correct misspelt names
	foreach ( wt_people_name_fixes() as $wrong => $right ) {
		$person = get_page_by_title( $wrong, OBJECT, 'people' );
		if ( ! $person ) {
			continue;
		}

		WP_CLI::log( sprintf( '  name     %s -> %s%s', $wrong, $right, $execute ? '' : ' (would rename)' ) );
		if ( $execute ) {
			wp_update_post(
				array(
					'ID'         => $person->ID,
					'post_title' => $right,
				)
			);
		}
	}

	// ------------------------------- 3b. apply the types supplied at review
	$people = get_posts(
		array(
			'post_type'      => 'people',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		)
	);

	$by_title = array();
	foreach ( $people as $person ) {
		$by_title[ strtolower( remove_accents( $person->post_title ) ) ] = $person;
	}

	// Register corrected spellings as aliases so a dry run resolves the same
	// people an execute run would, having already renamed them above.
	foreach ( wt_people_name_fixes() as $wrong => $right ) {
		$from = strtolower( remove_accents( $wrong ) );
		$to   = strtolower( remove_accents( $right ) );
		if ( isset( $by_title[ $from ] ) && ! isset( $by_title[ $to ] ) ) {
			$by_title[ $to ] = $by_title[ $from ];
		}
	}

	foreach ( wt_review_people_types() as $name => $slugs ) {
		$key = strtolower( remove_accents( $name ) );
		if ( ! isset( $by_title[ $key ] ) ) {
			WP_CLI::warning( sprintf( 'Person "%s" has no People record — skipping.', $name ) );
			continue;
		}

		$person   = $by_title[ $key ];
		$existing = (array) wp_get_object_terms( $person->ID, 'people-type', array( 'fields' => 'slugs' ) );
		$missing  = array_values( array_diff( $slugs, $existing ) );

		if ( ! $missing ) {
			WP_CLI::log( sprintf( '  review   %s — already %s', $person->post_title, implode( ', ', $slugs ) ) );
			continue;
		}

		WP_CLI::log(
			sprintf(
				'  review   %s — %s %s%s',
				$person->post_title,
				$execute ? 'adding' : 'would add',
				implode( ', ', $missing ),
				$existing ? ' (has ' . implode( ', ', $existing ) . ')' : ''
			)
		);
		if ( $execute ) {
			wp_set_object_terms( $person->ID, $missing, 'people-type', true );
		}
	}

	// ------------------------------ 3c. apply the titles supplied at review
	foreach ( wt_review_people_titles() as $name => $title ) {
		$key = strtolower( remove_accents( $name ) );
		if ( ! isset( $by_title[ $key ] ) ) {
			WP_CLI::warning( sprintf( 'Person "%s" has no People record — skipping title.', $name ) );
			continue;
		}

		$person  = $by_title[ $key ];
		$current = (string) get_post_meta( $person->ID, 'leadership_title', true );
		if ( $current === $title ) {
			WP_CLI::log( sprintf( '  title    %s — already %s', $person->post_title, $title ) );
			continue;
		}

		WP_CLI::log(
			sprintf(
				'  title    %s — %s %s -> %s',
				$person->post_title,
				$execute ? 'setting' : 'would set',
				$current ? '"' . $current . '"' : '(empty)',
				$title
			)
		);
		if ( $execute ) {
			update_field( 'field_61548e9fbbc7f', $title, $person->ID );
		}
	}

	// ------------------------------------------- 4. repoint the people pages
	foreach ( wt_people_page_map() as $page_id => $page ) {
		wt_migrate_people_page( $page_id, $page, $term_ids, $execute );
	}

	// ------------------------------------ 5. retire the old template groups
	/*
	 * ACF keeps field groups in both acf-json and the database, and the JSON
	 * copy wins at runtime. Deleting the JSON for the retired Board/Staff
	 * groups therefore leaves the database copies live, so trash them here.
	 * Trashed rather than deleted, so this stays reversible.
	 */
	foreach ( array(
		'group_64f9fb7b7925c' => 'Template: Board and Advisors',
		'group_64fa1f1e7e4f3' => 'Template: Staff',
	) as $key => $label ) {
		$group = get_page_by_path( $key, OBJECT, 'acf-field-group' );
		if ( ! $group || 'trash' === $group->post_status ) {
			WP_CLI::log( sprintf( '  group    %s — already gone', $label ) );
			continue;
		}

		WP_CLI::log( sprintf( '  group    %s — %s', $label, $execute ? 'trashing' : 'would trash' ) );
		if ( $execute ) {
			wp_trash_post( $group->ID );
		}
	}

	WP_CLI::success( $execute ? 'Migration complete.' : 'Dry run complete — nothing written.' );
}

/**
 * Move one page onto template-people.php with equivalent editorial sections.
 *
 * @param int                  $page_id  Page ID.
 * @param array<string,mixed>  $page     Page definition from wt_people_page_map().
 * @param array<string,int>    $term_ids people-type slug => term ID.
 * @param bool                 $execute  Whether to write.
 * @return void
 */
function wt_migrate_people_page( $page_id, $page, $term_ids, $execute ) {
	if ( ! get_post( $page_id ) ) {
		WP_CLI::warning( sprintf( 'Page %d (%s) not found — skipping.', $page_id, $page['label'] ) );
		return;
	}

	// Banner: team_banner_* -> people_banner_*. The old meta is left in place.
	// Key existence, not truthiness: the Advisors banner is legitimately empty,
	// and a truthiness check would re-migrate it on every run.
	if ( metadata_exists( 'post', $page_id, 'people_banner_banner_header' ) ) {
		WP_CLI::log( sprintf( '  banner   %s — already migrated', $page['label'] ) );
	} else {
		$header = get_post_meta( $page_id, 'team_banner_banner_header', true );
		$copy   = get_post_meta( $page_id, 'team_banner_banner_copy', true );
		WP_CLI::log( sprintf( '  banner   %s — %s %s', $page['label'], $execute ? 'moving' : 'would move', wp_json_encode( $header ) ) );
		if ( $execute ) {
			update_field(
				'field_wt_people_banner',
				array(
					'banner_header' => $header,
					'banner_copy'   => $copy,
				),
				$page_id
			);
		}
	}

	// Template assignment.
	$template = get_post_meta( $page_id, '_wp_page_template', true );
	if ( 'template-people.php' === $template ) {
		WP_CLI::log( sprintf( '  template %s — already template-people.php', $page['label'] ) );
	} else {
		WP_CLI::log( sprintf( '  template %s — %s %s -> template-people.php', $page['label'], $execute ? 'setting' : 'would set', $template ) );
		if ( $execute ) {
			update_post_meta( $page_id, '_wp_page_template', 'template-people.php' );
		}
	}

	// Editorial sections. Without these the repointed page would render only a
	// banner, so seeding them is part of the migration rather than follow-up
	// content work.
	$rows = maybe_unserialize( get_post_meta( $page_id, 'main_content', true ) );
	if ( ! empty( $rows ) ) {
		WP_CLI::log( sprintf( '  sections %s — main_content already populated, leaving alone', $page['label'] ) );
		return;
	}

	$layouts = array();
	foreach ( $page['sections'] as $section ) {
		$group = array(
			'custom_gallery_type'           => $section['type'],
			'custom_gallery_columns'        => $section['columns'],
			'custom_gallery_posts_per_page' => isset( $section['per_page'] ) ? $section['per_page'] : 100,
			'custom_gallery_paginate'       => 'false',
			'custom_gallery_orderby'        => 'title',
			'custom_gallery_title'          => $section['title'],
		);

		if ( 'people' === $section['type'] ) {
			$group['custom_gallery_layout']      = $section['layout'];
			$group['custom_gallery_people_type'] = wt_people_term_ids( $section['term'], $term_ids );
		} else {
			$group['custom_gallery_orderby']     = 'rand';
			$group['custom_gallery_career_type'] = wt_career_term_ids( $section['term'] );
		}

		$layouts[] = array(
			'acf_fc_layout'        => 'gallery_layout',
			'custom_gallery_posts' => $group,
		);
	}

	WP_CLI::log( sprintf( '  sections %s — %s %d section(s)', $page['label'], $execute ? 'seeding' : 'would seed', count( $layouts ) ) );
	if ( $execute ) {
		update_field( 'field_679e98a9c94e7', $layouts, $page_id );
	}
}

/**
 * Resolve comma-separated people-type slugs to term IDs.
 *
 * @param string            $slugs    Comma-separated slugs.
 * @param array<string,int> $term_ids Known slug => term ID.
 * @return int[]
 */
function wt_people_term_ids( $slugs, $term_ids ) {
	$ids = array();
	foreach ( array_map( 'trim', explode( ',', $slugs ) ) as $slug ) {
		if ( isset( $term_ids[ $slug ] ) ) {
			$ids[] = $term_ids[ $slug ];
		}
	}
	return $ids;
}

/**
 * Resolve comma-separated career_type slugs to term IDs.
 *
 * The retired Staff template passed term *names* ('Staff', 'Intern,Volunteer')
 * where the gallery queries by slug, so both career galleries matched nothing.
 * Resolving by slug here fixes that.
 *
 * @param string $slugs Comma-separated slugs.
 * @return int[]
 */
function wt_career_term_ids( $slugs ) {
	$ids = array();
	foreach ( array_map( 'trim', explode( ',', $slugs ) ) as $slug ) {
		$term = get_term_by( 'slug', $slug, 'career_type' );
		if ( $term ) {
			$ids[] = (int) $term->term_id;
		}
	}
	return $ids;
}

WP_CLI::add_command( 'wt migrate-people', 'wt_migrate_people' );
