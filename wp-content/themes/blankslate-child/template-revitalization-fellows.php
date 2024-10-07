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
  $custom_title = '';
  $custom_post_type = 'fellows';
  $custom_class = 'full';
  $custom_columns = 4;
  $custom_posts_per_page = 60;
  $custom_orderby = 'date';
  $custom_order = 'asc';
  $custom_pagination = 'true';
  $custom_meta_key = 'fellow_year';
  $custom_meta_value = isset($_GET['fellow_year']) ? sanitize_text_field($_GET['fellow_year']) : $cohorts[0];
  $custom_selected_posts = '';
  echo do_shortcode('[custom_gallery title="'.$custom_title.'" custom_class="'.$custom_class.'" post_type="'.$custom_post_type.'" columns="'.$custom_columns.'" posts_per_page="'.$custom_posts_per_page.'" orderby="'.$custom_orderby.'" order="'.$custom_order.'" pagination="'.$custom_pagination.'" meta_key="'.$custom_meta_key.'" meta_value="'.$custom_meta_value.'" selected_posts="'.$custom_selected_posts.'"]');

  include( 'modules/newsletter.php' );

  get_footer();
?>

<script>
function updateGallery(year) {
    window.location.href = '<?php echo get_permalink(); ?>?fellow_year=' + year;
}
</script>