<?php
/**
 * The sticky "On this page" bar on Language Rights report pages.
 *
 * @var array<string, mixed> $args `links`: anchor ID → label.
 */

$wt_links = (array) ( $args['links'] ?? array() );
if ( ! $wt_links ) {
	return;
}
?>
<nav class="wt-rights__jumpnav" aria-label="On this page">
	<div class="wt-rights__wrap">
		<strong>On this page</strong>
		<?php foreach ( $wt_links as $wt_id => $wt_label ) : ?>
			<a href="#<?php echo esc_attr( (string) $wt_id ); ?>"><?php echo esc_html( (string) $wt_label ); ?></a>
		<?php endforeach; ?>
	</div>
</nav>
