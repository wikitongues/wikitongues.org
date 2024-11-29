<?php
/* Template Name: Careers */
get_header();
$page_banner = get_field('banner');
include( 'modules/banner--main.php' );
?>

<div class="careers-page">
    <h1>Careers</h1>

    <?php
    $args = [
        'post_type'      => 'careers',
        'posts_per_page' => 10,
    ];
    $query = new WP_Query($args);

    if ($query->have_posts()) :
        echo '<ul>';
        while ($query->have_posts()) : $query->the_post();
            ?>
            <li class="career-item">
                <a href="<?php the_permalink(); ?>">
                    <h2><?php the_title(); ?></h2>
                    <p><?php echo esc_html(get_field('location')); ?></p>
                </a>
            </li>
            <?php
        endwhile;
        echo '</ul>';
        // Pagination
        echo paginate_links([
            'total' => $query->max_num_pages,
        ]);
    else :
        echo '<p>No careers found.</p>';
    endif;

    wp_reset_postdata();
    ?>
</div>

<?php
include('modules/newsletter.php');
get_footer();
