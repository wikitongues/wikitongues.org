<?php
// Portrait plus the shared detail block. Nothing here can assume a populated
// profile picture — the gallery renders every person a query returns.
$person_variant = 'wide';
?>
<article class="wt_person--wide">
	<aside class="wt_person--wide__img" role="img" aria-label="<?php echo esc_attr( $profile_picture['alt'] ?? '' ); ?>" style="background-image:url(<?php echo esc_url( $profile_picture['url'] ?? '' ); ?>);"></aside>

	<?php require __DIR__ . '/person--meta.php'; ?>
</article>
