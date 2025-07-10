<?php get_header(); ?>
<main class="site-main">
  <article id="post-<?php the_ID(); ?>" <?php post_class('blog-post'); ?>>
    <div class="breadcrumbs">
        <a href="<?php echo get_post_type_archive_link('blog'); ?>">Back to Blog</a>
    </div>
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
      <header class="entry-header">
        <h1 class="entry-title"><?php the_title(); ?></h1>
        <div class="excerpt"><?php the_excerpt(); ?></div>
        <h6 class="entry-meta"><?php echo get_the_date(); ?></h6>
      </header>

      <div class="entry-content">
        <?php the_content(); ?>
      </div>

      <section class="related-posts">
        <h4>More from the Blog</h4>
        <div class="related-posts-grid">
          <?php
          $related = new WP_Query(array(
            'post_type'    => 'blog',
            'posts_per_page' => 4,
            'post__not_in'   => array(get_the_ID()),
            'orderby'    => 'rand',
          ));
          if ($related->have_posts()) :
            while ($related->have_posts()) : $related->the_post();
          ?>
              <article class="related-post">
                <a href="<?php the_permalink(); ?>">
                  <h6><?php the_title(); ?></h6>
                  <?php if (has_post_thumbnail()) : ?>
                    <div class="thumbnail" style="background-image: url('<?php echo get_the_post_thumbnail_url(get_the_ID(), 'medium'); ?>');"></div>
                  <?php endif; ?>
                  <p class="excerpt"><?php echo wp_kses_post( wp_trim_words( get_the_excerpt(), 9, '&hellip;' )); ?></p>
                  <p class="meta"><?php echo get_the_date(); ?></p>
                </a>
              </article>
          <?php
            endwhile;
            wp_reset_postdata();
          endif;
          ?>
        </div>
      </section>
    <?php endwhile; endif; ?>
  </article>
</main>
<?php get_footer(); ?>