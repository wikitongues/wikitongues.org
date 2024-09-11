jQuery(document).on('click', '.gallery-pagination a.page-numbers', function(e) {
  e.preventDefault();  // Prevent default anchor behavior

  var page = jQuery(this).data('page');
  var galleryId = jQuery(this).data('gallery-id');

  var galleryContainer = jQuery('#' + galleryId);
  var galleryAtts = galleryContainer.data('attributes'); // Retrieve data attributes for gallery


  jQuery.ajax({
    type: 'POST',
    url: custom_gallery_ajax_params.ajax_url,
    data: {
      action: 'load_custom_gallery',
      page: page,
      gallery_id: galleryId,
      gallery_atts: JSON.stringify(galleryAtts), // Send gallery attributes as JSON string
      nonce: custom_gallery_ajax_params.nonce
    },
    success: function(response) {
      galleryContainer.html(response); // Replace the gallery content
    },
    error: function(xhr, status, error) {
      console.error('AJAX Error:', error);
    }
  });
});
