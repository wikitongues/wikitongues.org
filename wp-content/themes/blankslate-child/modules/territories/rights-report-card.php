<?php
/**
 * A territory's Language Rights report, as a card. Set $wt_rights_report (a
 * published rights_reports post) before requiring this file.
 */
$wt_rights_report = $wt_rights_report ?? null;
if ( ! $wt_rights_report instanceof WP_Post ) {
	return;
}
$wt_rights_data    = wt_rights_report( $wt_rights_report->ID );
$wt_rights_answers = (array) ( $wt_rights_data['answers'] ?? array() );
?>
<aside class="wt-rights-card">
	<p class="wt-rights__kicker"><?php echo esc_html( wt_rights_report_title() ); ?></p>
	<h2><a href="<?php echo esc_url( get_permalink( $wt_rights_report ) ); ?>">Language rights in <?php echo esc_html( wt_prefix_the( get_the_title( $wt_rights_report ) ) ); ?></a></h2>
	<p><?php echo esc_html( (string) ( $wt_rights_data['content']['rating']['headline'] ?? '' ) ); ?></p>
	<ul>
		<?php foreach ( wt_rights_structure()['indicators'] as $wt_rights_item ) : ?>
			<li><?php echo esc_html( $wt_rights_item['label'] ); ?> <?php echo wt_rights_answer_pill( (string) ( $wt_rights_answers[ $wt_rights_item['key'] ] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the function. ?></li>
		<?php endforeach; ?>
	</ul>
	<a class="wt-rights-card__link" href="<?php echo esc_url( get_permalink( $wt_rights_report ) ); ?>">Read the report →</a>
</aside>
