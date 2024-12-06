<?php
/* Template Name: Careers */
get_header();
$page_banner = get_field('banner');
include( 'modules/banner--main.php' );
?>

<div class="careers-page">
    <h1>Careers</h1>

    <?php
    // Define the categories
    $career_categories = ['Staff', 'Intern', 'Volunteer'];

    foreach ($career_categories as $category) :
        // Get the term object
        $term = get_term_by('name', $category, 'career_type');
        ?>

        <div class="career-section">
            <h2><?php echo esc_html($category); ?></h2>
            <ul>
                <?php
                if ($term) {
                    // Query posts in this category
                    $args = [
                        'post_type'      => 'careers',
                        'posts_per_page' => -1,
                        'tax_query'      => [
                            [
                                'taxonomy' => 'career_type',
                                'field'    => 'term_id',
                                'terms'    => $term->term_id,
                            ],
                        ],
                    ];
                    $query = new WP_Query($args);

                    if ($query->have_posts()) :
                        while ($query->have_posts()) : $query->the_post();
                            ?>
                                <li>
                                    <a href="<?php the_permalink(); ?>">
                                        <h3><?php the_title(); ?></h3>
                                        <p class="location">&nbsp;— <?php echo esc_html(get_field('location')); ?></p>
                                    </a>
                                </li>
                            <?php
                        endwhile;
                    else :
                        // No posts found
                        echo '<li class="empty"><p>No open positions at the moment</p></li>';
                    endif;

                    wp_reset_postdata();
                } else {
                    // Term does not exist
                    echo '<li class="empty"><p>No open positions at the moment</p></li>';
                }
                ?>
            </ul>
        </div>

    <?php endforeach; ?>
    <p class="alternative">Not seeing the right opportunity? Pitch us at <a href="mailto:hello@wikitongues.org">hello@wikitongues.org</a>.</p>
</div>

<?php
include('modules/newsletter.php');
get_footer();
