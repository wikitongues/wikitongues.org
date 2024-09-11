<?php /* Template name: Revitalization Fellows */

get_header();

$page_banner = get_field('revitalization_fellows_banner');

include( 'modules/banner--main.php' );

$selected_year = isset($_GET['fellow_year']) ? sanitize_text_field($_GET['fellow_year']) : '';

?>

<div class="custom-gallery-fellows-navigation">
  <h2>Fellowship Cohorts</h2>
  <ul>
    <li><button class="<?php echo ($selected_year === '2023' || $selected_year === '') ? 'active' : ''; ?>" data-year="2023" onclick="updateGallery('2023')">2023</button></li>
    <li><button class="<?php echo $selected_year === '2022' ? 'active' : ''; ?>" data-year="2022" onclick="updateGallery('2022')">2022</button></li>
    <li><button class="<?php echo $selected_year === '2021' ? 'active' : ''; ?>" data-year="2021" onclick="updateGallery('2021')">2021</button></li>
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
  $custom_meta_value = isset($_GET['fellow_year']) ? sanitize_text_field($_GET['fellow_year']) : '2023';
  $custom_selected_posts = '';
  echo do_shortcode('[custom_gallery title="'.$custom_title.'" custom_class="'.$custom_class.'" post_type="'.$custom_post_type.'" columns="'.$custom_columns.'" posts_per_page="'.$custom_posts_per_page.'" orderby="'.$custom_orderby.'" order="'.$custom_order.'" pagination="'.$custom_pagination.'" meta_key="'.$custom_meta_key.'" meta_value="'.$custom_meta_value.'" selected_posts="'.$custom_selected_posts.'"]');
  get_footer();
?>

<script>
function updateGallery(year) {
    window.location.href = '<?php echo get_permalink(); ?>?fellow_year=' + year;
}
</script>