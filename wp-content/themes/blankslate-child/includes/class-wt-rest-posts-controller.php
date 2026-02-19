<?php

// An extension of the REST API controller to handle the ACF Post Object fields

require_once 'post-object-helpers.php';

class WT_REST_Posts_Controller extends WP_REST_Posts_Controller {

	public function create_item( $request ) {
		$response = parent::create_item( $request );
		handle_post_object( $request );

		return $response;
	}

	public function update_item( $request ) {
		$response = parent::update_item( $request );
		handle_post_object( $request );

		return $response;
	}
}
