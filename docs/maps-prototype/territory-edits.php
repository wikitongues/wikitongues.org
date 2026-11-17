<?php
/**
 * Maps launch territory edits (plan.md 3.2, "Content changes that ship with the plugin").
 * Run: wp eval-file territory-edits.php [execute] --allow-root
 * Without "execute" it only prints what it would do. Idempotent: safe to run twice.
 */
$execute = in_array( 'execute', $args ?? array(), true );
$log     = function ( $msg ) use ( $execute ) {
	WP_CLI::log( ( $execute ? '' : '[dry run] ' ) . $msg );
};

$lang = function ( $iso ) {
	$p = get_page_by_path( $iso, OBJECT, 'languages' );
	if ( ! $p ) {
		WP_CLI::error( "No language $iso" );
	}
	return $p->ID;
};
$terr = function ( $slug ) {
	$p = get_page_by_path( $slug, OBJECT, 'territories' );
	return $p ? $p->ID : 0;
};
$ids = function ( $post_id, $field ) {
	return array_map( 'intval', (array) ( get_field( $field, $post_id, false ) ?: array() ) );
};
$set_territories = function ( $lang_id, array $territory_ids ) use ( $execute, $log, $ids ) {
	$before = $ids( $lang_id, 'territories' );
	$after  = array_values( array_unique( array_map( 'intval', $territory_ids ) ) );
	$name   = fn( $list ) => implode( ', ', array_map( 'get_the_title', $list ) ) ?: '(none)';
	if ( $before === $after ) {
		$log( get_the_title( $lang_id ) . ': territories already ' . $name( $after ) );
		return;
	}
	$log( get_the_title( $lang_id ) . ': territories ' . $name( $before ) . ' -> ' . $name( $after ) );
	if ( $execute ) {
		update_field( 'territories', $after, $lang_id ); // Bidirectional: updates each territory's languages too.
	}
};
$create_territory = function ( $title, $slug, $region, array $official ) use ( $execute, $log, $terr ) {
	if ( $terr( $slug ) ) {
		$log( "$title: territory exists" );
		return $terr( $slug );
	}
	$log( "$title: create territory /$slug/ in region $region, official languages " . implode( ', ', array_map( 'get_the_title', $official ) ) );
	if ( ! $execute ) {
		return -1;
	}
	$id = wp_insert_post(
		array(
			'post_type'   => 'territories',
			'post_title'  => $title,
			'post_name'   => $slug,
			'post_status' => 'publish',
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		WP_CLI::error( $id->get_error_message() );
	}
	wp_set_object_terms( $id, $region, 'region' );
	update_field( 'official_languages', $official, $id );
	return $id;
};

$hye = $lang( 'hye' );
$kal = $lang( 'kal' );
$cha = $lang( 'cha' );
$cal = $lang( 'cal' );
$eng = $lang( 'eng' );

// 1. Fold Nagorno-Karabakh.
$nk = $terr( 'nagorno-karabakh' );
if ( $nk ) {
	$others = $ids( $nk, 'languages' );
	if ( array_diff( $others, array( $hye ) ) ) {
		WP_CLI::warning( 'Nagorno-Karabakh lists languages besides Armenian: ' . implode( ', ', array_map( 'get_the_title', $others ) ) );
	}
	$set_territories( $hye, array_diff( $ids( $hye, 'territories' ), array( $nk ) ) );
	if ( 'publish' === get_post_status( $nk ) ) {
		$log( 'Nagorno-Karabakh: unpublish (draft)' );
		if ( $execute ) {
			wp_update_post( array( 'ID' => $nk, 'post_status' => 'draft' ) );
		}
	} else {
		$log( 'Nagorno-Karabakh: already unpublished' );
	}
}

// 2. Greenland, Guam, Northern Mariana Islands.
$grl = $create_territory( 'Greenland', 'greenland', 'northern-america', array( $kal ) );
$gum = $create_territory( 'Guam', 'guam', 'micronesia', array( $eng, $cha ) );
$mnp = $create_territory( 'Northern Mariana Islands', 'northern-mariana-islands', 'micronesia', array( $eng, $cha, $cal ) );

$dk = $terr( 'denmark' );
$set_territories( $kal, array_merge( array_diff( $ids( $kal, 'territories' ), array( $dk ) ), array( $grl ) ) );
$set_territories( $cha, array_merge( $ids( $cha, 'territories' ), array( $gum, $mnp ) ) );
$set_territories( $cal, array_merge( $ids( $cal, 'territories' ), array( $mnp ) ) );

// 3. Renames. Changing a published post's slug records the old one in _wp_old_slug.
$renames = array(
	'sahrawi-arab-democratic-republic' => array( 'Western Sahara', 'western-sahara' ),
	'cape-verde-islands'               => array( 'Cabo Verde', null ),
);
foreach ( $renames as $old => list( $title, $slug ) ) {
	$id = $terr( $old ) ?: ( $slug ? $terr( $slug ) : 0 );
	if ( ! $id ) {
		WP_CLI::warning( "No territory $old" );
		continue;
	}
	if ( get_the_title( $id ) === $title && ( ! $slug || get_post_field( 'post_name', $id ) === $slug ) ) {
		$log( "$title: already renamed" );
		continue;
	}
	$log( get_the_title( $id ) . " -> $title" . ( $slug ? ", slug $old -> $slug" : ', slug unchanged' ) );
	if ( $execute ) {
		$update = array( 'ID' => $id, 'post_title' => $title );
		if ( $slug ) {
			$update['post_name'] = $slug;
		}
		wp_update_post( $update );
	}
}
