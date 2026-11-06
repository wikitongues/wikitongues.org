<?php
// Numbered Columns. The first one on a page is the Donation Hero's #where target.
$row   = wt_donate_row();
$items = (array) ( $row['items'] ?? array() );

// Included in editorial-content.php's scope, so the count carries across rows.
$numbered_list_index = ( $numbered_list_index ?? 0 ) + 1;
$section_id          = 1 === $numbered_list_index ? 'where' : 'numbered-' . $numbered_list_index;
$language_count      = number_format_i18n( wt_donate_stat( 'languages' ) );
?>
<section id="<?php echo esc_attr( $section_id ); ?>" class="wt-donate" aria-labelledby="<?php echo esc_attr( $section_id ); ?>-title">
	<div class="wt-donate__wrap wt-donate__section">
		<div class="wt-donate__heading">
			<p class="wt-donate__label"><?php echo esc_html( $row['section_eyebrow'] ?? '' ); ?></p>
			<h2 id="<?php echo esc_attr( $section_id ); ?>-title"><?php echo esc_html( $row['section_header'] ?? '' ); ?></h2>
			<?php if ( ! empty( $row['section_copy'] ) ) : ?>
				<p><?php echo esc_html( $row['section_copy'] ); ?></p>
			<?php endif; ?>
		</div>
		<?php if ( $items ) : ?>
			<ol class="wt-donate__grid wt-donate__programs">
				<?php foreach ( array_values( $items ) as $i => $item ) : ?>
					<li>
						<p class="wt-donate__number"><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></p>
						<h3><?php echo esc_html( $item['item_header'] ?? '' ); ?></h3>
						<p><?php echo esc_html( str_replace( '{languages}', $language_count, (string) ( $item['item_copy'] ?? '' ) ) ); ?></p>
					</li>
				<?php endforeach; ?>
			</ol>
		<?php endif; ?>
	</div>
</section>
