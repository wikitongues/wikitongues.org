// $ = jQuery;

// function changeHeaderClass() {
// 	var $header = $('.wt_header'),
// 		$logoLight = $('.wt_header__logo--light'),
// 		$logoDark = $('.wt_header__logo--dark'),
// 		$navContainer = $('.wt_header__nav');

// 	$(window).scroll(function () {
// 		if ($(this).scrollTop() > 50) {
// 			$header.removeClass('transparent-background');
// 			$logoLight.removeClass('transparent-background');
// 			$logoDark.removeClass('transparent-background');
// 			$navContainer.removeClass('transparent-background');
// 		} else {
// 			$header.addClass('transparent-background');
// 			$logoLight.addClass('transparent-background');
// 			$logoDark.addClass('transparent-background');
// 			$navContainer.addClass('transparent-background');
// 		}
// 	});
// }

// function carouselScroll() {
// 	// clean up scroll animation to go by thirds
// 	$('.wt_carousel__right-scroll').click(function () {
// 		event.preventDefault();

// 		$(this).hide();
// 		$('.wt_carousel__left-scroll').show();

// 		$(this).parents('section').find('ul').animate({
// 			scrollLeft: "+=1500px"
// 		}, "slow");

// 		$(window).resize(function () {
// 			var $scrollWidth = $('.wt_carousel__right-scroll').parents('section').find('ul').width();
// 		})
// 	});

// 	$('.wt_carousel__left-scroll').click(function () {
// 		event.preventDefault();

// 		$(this).hide();
// 		$('.wt_carousel__right-scroll').show();

// 		$(this).parents('section').find('ul').animate({
// 			scrollLeft: "-=1500px"
// 		}, "slow");

// 		$(window).resize(function () {
// 			var $scrollWidth = $('.wt_carousel__left-scroll').parents('section').find('ul').width();
// 		})
// 	});
// }

// function mobileTrigger() {
// 	var $mobileNavOpen = $('#mobile-nav-open'),
// 		$mobileNavClose = $('#mobile-nav-close'),
// 		$mobileNav = $('.wt_header__nav--mobile'),
// 		$body = $('body');

// 	$mobileNavOpen.on('click', function () {
// 		$body.addClass('no-scroll');
// 		$(this).hide();
// 		$mobileNav.addClass('expand');
// 		$mobileNavClose.show();

// 		$mobileNavClose.on('click', function () {
// 			$(this).hide();
// 			$mobileNav.removeClass('expand');
// 			$mobileNavOpen.show();
// 			$body.removeClass('no-scroll');
// 		});
// 	});
// }

// // run all general UX/UI functions
// $(window).on('load', function () {

// 	if ($('body').hasClass('home')) {
// 		changeHeaderClass();
// 	}

// 	carouselScroll();

// 	mobileTrigger();
// });



//Here is the Vanilla Javascript version of the jquery
function changeHeaderClass() {
	var header = document.querySelector('.wt_header');
	var logoLight = document.querySelector('.wt_header__logo--light');
	var logoDark = document.querySelector('.wt_header__logo--dark');
	var navContainer = document.querySelector('.wt_header__nav');

	window.addEventListener('scroll', function () {
		if (window.pageYOffset > 50) {
			header.classList.remove('transparent-background');
			logoLight.classList.remove('transparent-background');
			logoDark.classList.remove('transparent-background');
			navContainer.classList.remove('transparent-background');
		} else {
			header.classList.add('transparent-background');
			logoLight.classList.add('transparent-background');
			logoDark.classList.add('transparent-background');
			navContainer.classList.add('transparent-background');
		}
	});
}

function carouselScroll() {
	var rightScroll = document.querySelector('.wt_carousel__right-scroll');
	var leftScroll = document.querySelector('.wt_carousel__left-scroll');
	var carouselList = document.querySelector('ul.wt_carousel__list');

	rightScroll.addEventListener('click', function (event) {
		event.preventDefault();
		this.style.display = 'none';
		leftScroll.style.display = 'block';

		carouselList.scrollTo({
			left: carouselList.scrollLeft + 1500,
			behavior: 'smooth'
		});
	});

	leftScroll.addEventListener('click', function (event) {
		event.preventDefault();
		this.style.display = 'none';
		rightScroll.style.display = 'block';

		carouselList.scrollTo({
			left: carouselList.scrollLeft - 1500,
			behavior: 'smooth'
		});
	});

	window.addEventListener('resize', function () {
		var scrollWidth = carouselList.clientWidth;
	});
}

function mobileTrigger() {
	var mobileNavOpen = document.getElementById('mobile-nav-open');
	var mobileNavClose = document.getElementById('mobile-nav-close');
	var mobileNav = document.querySelector('.wt_header__nav--mobile');
	var body = document.body;

	mobileNavOpen.addEventListener('click', function () {
		body.classList.add('no-scroll');
		this.style.display = 'none';
		mobileNav.classList.add('expand');
		mobileNavClose.style.display = 'block';

		mobileNavClose.addEventListener('click', function () {
			this.style.display = 'none';
			mobileNav.classList.remove('expand');
			mobileNavOpen.style.display = 'block';
			body.classList.remove('no-scroll');
		});
	});
}

window.addEventListener('load', function () {
	if (document.body.classList.contains('home')) {
		changeHeaderClass();
	}
	carouselScroll();
	mobileTrigger();
});
