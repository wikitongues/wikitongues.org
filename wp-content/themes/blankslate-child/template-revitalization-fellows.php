<?php /* Template name: Revitalization Fellows */

$page_banner = get_field('revitalization_fellows_banner');
$selected_year = isset($_GET['fellow_year']) ? sanitize_text_field($_GET['fellow_year']) : '';
$cohorts = '';

get_header();

include( 'modules/banner--main.php' );

$fellow = get_posts ([
  'post_type'      => 'fellows',
  'posts_per_page' => 1,
  'fields'         => 'ids', // Only fetch IDs (cheapest query)
]);

$fellow_id = $fellow ? $fellow[0] : null;
if ($fellow_id) {
  $field_object = get_field_object('fellow_year', $fellow_id);

  if ($field_object) {
      $cohorts = $field_object['choices']; // List of available options
      rsort($cohorts);
  } else {
    echo "Field object not found.";
  }
} else {
  echo "No fellows found in the database.";
}
?>

<!-- <div class="custom-gallery-fellows-navigation-wrapper"> -->
  <div class="custom-gallery-fellows-navigation nav">
    <strong>Fellowship Cohorts</strong>
    <ul>
      <?php
      foreach ($cohorts as $index => $cohort) {
        $class = ($selected_year === $cohort || ($selected_year === '' && $index === 0)) ? 'active' : '';
        echo '<li class="'.$class.'"><a data-year="'.$cohort.'" onclick="updateGallery(\''.$cohort.'\')">'.$cohort.'</a></li>';
      }
      ?>
    </ul>
    <!-- <p><a href="https://abdbdjge.donorsupport.co/-/XTRAFEBU">Support language revitalization.</a></p> -->
    <strong><a href="<?php echo home_url('/fellow-category/wikimedia/', 'relative')?>">Browse by category</a></strong>
  </div>

<?php
  // Gallery
  $params = [
    'title' => '',
    'subtitle' => '',
    'post_type' => 'fellows',
    'custom_class' => 'full',
    'columns' => 4,
    'posts_per_page' => 60,
    'orderby' => 'rand',
    'order' => '',
    'pagination' => 'true',
    'meta_key' => 'fellow_year',
    'meta_value' => isset($_GET['fellow_year']) ? sanitize_text_field($_GET['fellow_year']) : $cohorts[0],
    'selected_posts' => '',
    'display_blank' => 'false',
    'exclude_self' => 'true',
    'taxonomy' => '',
    'term' => '',
  ];
  echo create_gallery_instance($params);
  // echo '</div>';

  include( 'modules/newsletter.php' );

  get_footer();
?>

<script>
function updateGallery(year) {
    window.location.href = '<?php echo get_permalink(); ?>?fellow_year=' + year;
}
</script>