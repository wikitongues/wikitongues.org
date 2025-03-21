jQuery(document).ready(function($) {
	let sections = $('h4[id]');
	let navigationLinks = $('.faq-navigation a');

	$(window).on('scroll', function() {
			let currentScroll = $(this).scrollTop();
			let currentSection;

			sections.each(function() {
					let sectionTop = $(this).offset().top - 120; // Adjust offset as needed
					if (currentScroll >= sectionTop) {
							currentSection = $(this);
					}
			});

			if (currentSection) {
					let id = currentSection.attr('id');
					navigationLinks.removeClass('active');
					$('.faq-navigation a[href="#' + id + '"]').addClass('active');
			}
	});
});