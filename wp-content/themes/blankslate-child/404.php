<?php get_header(); ?>
<article id="page-404" class="post not-found">
  <h4>Page not found</h4>
  <p>This page was lost in translation. <a href="<?php echo home_url('archive', 'relative'); ?>">Try a search instead?</a></p>
</article>
<?php include( 'modules/newsletter.php' ); ?>
<?php get_footer(); ?>