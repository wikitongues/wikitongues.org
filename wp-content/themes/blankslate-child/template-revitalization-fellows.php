<?php /* Template name: Revitalization Fellows */

$page_banner = get_field('revitalization_fellows_banner');
$selected_year = isset($_GET['fellow_year']) ? sanitize_text_field($_GET['fellow_year']) : '';
$cohorts = array('2024', '2023', '2022', '2021');

get_header();

include( 'modules/banner--main.php' );

?>

<div class="custom-gallery-fellows-navigation">
  <h2>Fellowship Cohorts</h2>
  <ul>
    <?php
    foreach ($cohorts as $index => $cohort) {
      $active_class = ($selected_year === $cohort || ($selected_year === '' && $index === 0)) ? 'active' : '';
      echo '<li><button class="'.$active_class.'" data-year="'.$cohort.'" onclick="updateGallery(\''.$cohort.'\')">'.$cohort.'</button></li>';
    }
    ?>
  </ul>
  <p><a href="https://abdbdjge.donorsupport.co/-/XTRAFEBU">Support language revitalization.</a></p>
</div>

<?php
  // Gallery
  $params = [
    'title' => '',
    'post_type' => 'fellows',
    'custom_class' => 'full',
    'columns' => 4,
    'posts_per_page' => 60,
    'orderby' => 'date',
    'order' => 'asc',
    'pagination' => 'true',
    'meta_key' => 'fellow_year',
    'meta_value' => isset($_GET['fellow_year']) ? sanitize_text_field($_GET['fellow_year']) : $cohorts[0],
    'selected_posts' => '',
    'display_blank' => '',
    'taxonomy' => '',
    'term' => '',
  ];
  echo create_gallery_instance($params);

  include( 'modules/newsletter.php' );

  get_footer();
?>

<script>
function updateGallery(year) {
    window.location.href = '<?php echo get_permalink(); ?>?fellow_year=' + year;
}
</script>