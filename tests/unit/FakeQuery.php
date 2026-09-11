<?php
class FakeQuery {
	public $is_search         = true;
	public $query_vars        = array( 'post_type' => '' );
	public $calls             = array();
	public $main_query        = true;
	public $archive_post_type = '';

	public function set( $key, $value ) {
		$this->calls[ $key ] = $value;
	}

	public function is_main_query() {
		return $this->main_query;
	}

	public function is_post_type_archive( $post_types = '' ) {
		return $post_types === $this->archive_post_type;
	}
}
