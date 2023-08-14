$ = jQuery;

function changeHeaderClass() {
	var $header = $('.wt_header'),
		$logoLight = $('.wt_header__logo--light'),
		$logoDark = $('.wt_header__logo--dark'),
		$navContainer = $('.wt_header__nav');

	$(window).scroll(function(){
		if($(this).scrollTop()>50){
			$header.removeClass('transparent-background');
			$logoLight.removeClass('transparent-background');
			$logoDark.removeClass('transparent-background');
			$navContainer.removeClass('transparent-background');
		} else {
			$header.addClass('transparent-background');
			$logoLight.addClass('transparent-background');
			$logoDark.addClass('transparent-background');
			$navContainer.addClass('transparent-background');
		}
	});
}

// run all general UX/UI functions
$(window).on('load', function(){

	if($('body').hasClass('home')){
		changeHeaderClass(); 
	}
});