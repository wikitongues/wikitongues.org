<?php
// Name and role, nothing else — no portrait, languages, links or bio. Used
// wherever people are listed rather than profiled: advisors, former board
// members. Many people carry an affiliation rather than a role in
// leadership_title, and some carry neither, so the role is optional.
?>
<article class="wt_person--list">
	<h4><?php echo $name; ?></h4>
	<?php if ( $title ) : ?>
		<strong><?php echo $title; ?></strong>
	<?php endif; ?>
</article>
