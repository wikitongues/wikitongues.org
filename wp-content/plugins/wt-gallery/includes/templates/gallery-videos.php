<li class="gallery-item">
  <?php
    $url = get_permalink();
    $public_status = get_field('public_status');
    $thumbnail_url = get_custom_image('videos');
    $metadata = [
      'height' => get_field('metadata_height'),
      'width' => get_field('metadata_width')
    ];

    if (empty($metadata['height']) || empty($metadata['width'])) {
      $thumbnail_url = home_url('/wp-content/uploads/2025/04/wikitongues_audio_thumbnail.png');
    }

    // Public status check is the most important one. If not public, then processing.
    if ($public_status !== 'Public') {
      $thumbnail_url = home_url('/wp-content/uploads/2025/04/wikitongues_processing_thumbnail.png');
    }

    $thumbnail_object = $thumbnail_url
      ? '<div class="thumbnail" style="background-image:url('.esc_url($thumbnail_url).');" alt="' . get_the_title() . '"></div>'
      : '<div class="no-thumbnail"><p>Thumbnail unavailable</p></div>';

    echo '<a href="' . esc_url($url) . '">';
    echo $thumbnail_object;
    echo '<div><h6>' . $title . '</h6></div>';
    echo '</a>';
  ?>
</li>
