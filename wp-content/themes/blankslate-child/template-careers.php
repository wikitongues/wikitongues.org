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
            <strong><?php echo esc_html($category); ?></strong>
            <ul>
                <?php
                if ($term) {
                    $today = date('Ymd');
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
                        'meta_query' => [
                            'relation' => 'OR',
                            [
                                'key'     => 'deadline',
                                'value' => '',
                                'compare' => '=',
                            ],
                            [
                                'key'     => 'deadline',
                                'value'   => $today,
                                'compare' => '>=',
                                'type'    => 'DATE',
                            ],
                        ],
                        'orderby'  => 'meta_value',
                        'order'    => 'ASC',
                        'meta_key' => 'deadline',
                    ];
                    $query = new WP_Query($args);

                    if ($query->have_posts()) :
                        while ($query->have_posts()) : $query->the_post();

                            ?>
                                <li>
                                    <a href="<?php the_permalink(); ?>">
                                        <h4><?php the_title(); ?></h4>
                                        <p class="deadline">
                                            Application deadline:
                                            <?php
                                            $deadline = get_field('deadline');
                                            echo $deadline ? esc_html(date('F j, Y', strtotime($deadline))) : 'Rolling basis';
                                            ?>
                                        </p>
                                        <p class="location">Location: <?php echo esc_html(get_field('location')); ?></p>
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
</div>

<?php
include('modules/newsletter.php');
get_footer();
