<li class="gallery-item">
  <?php
    $url = get_permalink();
    $thumbnail_url = get_custom_image('videos');
    $thumbnail_object = '';

    if ($thumbnail_url) {
      $thumbnail_object= '<div class="thumbnail" style="background-image:url('.esc_url($thumbnail_url).');" alt="' . get_the_title() . '"></div>';
    } else {
      $thumbnail_object= '<div class="no-thumbnail"><p>Thumbnail unavailable</p></div>';
    }

    echo '<a href="' . esc_url($url) . '">';
    echo $thumbnail_object;
    echo '<div><h6>' . $title . '</h6></div>';
    echo '</a>';
  ?>
</li>
