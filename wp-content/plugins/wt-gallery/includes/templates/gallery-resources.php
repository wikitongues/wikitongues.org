<li class="gallery-item">
  <?php
    $url = get_field('resource_url');
    $title = get_field('resource_title') ? get_field('resource_title') : get_the_title();
    $external_resources = get_field('external_resources');
    $resource_description = get_field('resource_description');
    $domain = getDomainFromUrl($url);

    echo '<a href="' . esc_url($url) . '">';
    echo '<h6>' . esc_html($title) . '</h6>';
    if ($resource_description) {
      echo '<p class="description">' . esc_html($resource_description) . '</p>';
    }
    echo '<p class="domain">' . esc_html($domain) . '</p>';
    echo '</a>';
  ?>
</li>
