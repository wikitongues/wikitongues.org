$ = jQuery;

// reveal select elements on js load
function revealElement(a) {
	var $hiddenElement = a;

	$hiddenElement.addClass('visible'); 
}

// close popups
function closeOverlay(a,b) {
	var $body = $('body'),
		$targetWindow = a,
		$closeButton = $('#wt_closeoverlay');

	// close popup using esc key
	$(document).on('keyup', function(e) {
	  if (e.which == 27) {
	  	$body.removeAttr('data-ui','no-scroll');
	  	$targetWindow.removeClass('wt_visible');
	  }
	});

	// close popup using button
	$closeButton.click(function(){
		$body.removeAttr('data-ui','no-scroll');
		$targetWindow.removeClass('wt_visible');
	});
}

// language search popup
function languageSearch() {
	var $body = $('body'),
		$search = $('#wt_actions__search'),
		$results = $('#wt_search');

	// when user clicks 'search' button, show search window
	$search.click(function(){
		$body.attr('data-ui','no-scroll');
		$results.addClass('wt_visible');
	});

	// when user clicks 'esc' or clicks button, close window
	closeOverlay($results);
}

// run all general UX/UI functions
$(window).on('load', function(){
	languageSearch();
});

// run after JS loads
$(document).ready(function(){
	// wait 1 second after JS has loaded
	setTimeout(function(){
			// reveal menu items after JS load
			revealElement($('#wt_header__nav--menu'));
		}, 1000
	);	
});

// masonry
$(window).load(function(){
	$('.wt_thumbnails').masonry({
	  // options
	  itemSelector: '.wt_masonry',
	  horizontalOrder: true
	});
});