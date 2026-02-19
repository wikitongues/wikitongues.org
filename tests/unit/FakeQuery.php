<?php
class FakeQuery {
	public $is_search  = true;
	public $query_vars = array( 'post_type' => '' );
	public $calls      = array();

	public function set( $key, $value ) {
		$this->calls[ $key ] = $value;
	}
}
