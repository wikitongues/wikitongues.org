<li class="gallery-item">
  <?php
  $url = get_permalink();
  $thumbnail_object = '';
  $iso_code = get_the_title();
  $iso_code_element = '<aside>'.esc_html($iso_code).'</aside>';
  $video_query = get_videos_by_featured_language($iso_code);

  if ($video_query->have_posts()) {
    while ($video_query->have_posts()) {
      $video_query->the_post();
      $thumbnail = get_custom_image('videos');
      $video_title = get_custom_title('videos');
      $thumbnail_object .= '<div class="thumbnail" style="background-image:url('.esc_url($thumbnail).');" alt="' . get_the_title() . '" title=""> <p>' . $video_title . '</p></div>';
    }
  } else {
    $thumbnail_object .= '<div class="no-thumbnail"><p>No resources found to display – Yet.</p></div>';
  }

  echo '<a href="' . esc_url($url) . '">';
  echo '<div class="metadata"><h6>' . $title . '</h6>' . $iso_code_element . '</div>';
  echo $thumbnail_object;
  echo '</a>';
  ?>
</li>
