<?php
$section_header = get_sub_field('section_header');
$block_type = get_sub_field('block_type');
$block_style = get_sub_field('block_style');

$class = ($block_type === 'Card') ? 'thirds' : (($block_type === 'Block') ? 'wide' : '');
$class .= ' '.$block_style;
echo '<main class="wrapper ' . esc_attr($class) . '">';

if ($section_header) {
    echo '<h4>' . esc_html($section_header) . '</h4>';
}

while (have_rows('block_group')) :
    the_row();

    // Get all fields
    $header = get_sub_field('header');
    $image = get_sub_field('thumbnail');
    $text = get_sub_field('text');
    $link_type = get_sub_field('link_type'); // 'Link' or 'Download'
    $link = get_sub_field('link');
    $display_secondary = get_sub_field('display_secondary');
    $display_caption = get_sub_field('display_caption');

    // Resolve image data
    $image_url = '';
    $image_alt = '';
    $image_caption = '';


    if ($image) {
        if (is_numeric($image)) {
            $image_url = wp_get_attachment_url($image);
            $image_alt = get_post_meta($image, '_wp_attachment_image_alt', true);
            $image_caption = get_post($image)->post_excerpt;
        } elseif (is_array($image)) {
            $image_url = $image['url'] ?? '';
            $image_alt = $image['alt'] ?? '';
        }
    }

    // Handle linked post + file
    $anchor = '';
    $linked_post_id = !empty($link) ? url_to_postid($link['url']) : null;
    $linked_post_type = $linked_post_id ? get_post_type($linked_post_id) : '';
    $selected_file = ($linked_post_type === 'documents') ? get_field('selected_file', $linked_post_id) : null;
    $file_field = $selected_file ? get_field('file', $selected_file->ID) : '';

    // Primary CTA logic
    if (!empty($link)) {
        if ($link_type === 'link') {
            $anchor .= '<a href="' . esc_url($link['url']) . '">' . esc_html($link['title']) . '</a>';
        }
        if ($link_type === 'download' && $file_field) {
            $anchor .= '<a href="' . esc_url($file_field) . '">Download</a>';
        }
    }

    // Secondary CTA logic
    if ($display_secondary === 'Yes' && $linked_post_type === 'documents') {
        if ($link_type === 'link' && $file_field) {
            $anchor .= '<a class="secondary" href="' . esc_url($file_field) . '">Download</a>';
        }
        if ($link_type === 'download') {
            $anchor .= '<a class="secondary" href="' . esc_url($link['url']) . '">' . esc_html($link['title']) . '</a>';
        }
    } else {
    }

    // Render Block
    echo '<section class="block">';

    // Thumbnail
    if ($image_url) :
        ?>
        <div class="thumbnail"
             role="img"
             aria-label="<?php echo esc_attr($image_alt); ?>"
             style="background-image: url('<?php echo esc_url($image_url); ?>');">
            <?php if (!empty($image_caption) && $display_caption === 'Yes') :?>
                <span><?php echo esc_html($image_caption); ?></span>
            <?php endif; ?>
        </div>
    <?php
    endif;

    // Copy Section
    echo '<aside class="copy">';
    echo $block_type === 'Block' ? '<h1>' . esc_html($header) . '</h1>' : '<strong>' . esc_html($header) . '</strong>';
    echo $text ? '<p>' . esc_html($text) . '</p>' : '';
    echo $anchor ? '<div>' . $anchor . '</div>' : '';
    echo '</aside>';

    echo '</section>';
endwhile;

echo '</main>';