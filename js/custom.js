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
    const rightScrollList = document.querySelectorAll('.wt_carousel__right-scroll');
    const leftScrollList = document.querySelectorAll('.wt_carousel__left-scroll');
    const sectionList = document.querySelectorAll('.wt_carousel__wrapper');

    sectionList.forEach((section, index) => {
        const rightScroll = rightScrollList[index];
        const leftScroll = leftScrollList[index];
        const ul = section.querySelector('ul');
		const liWidth = ul.querySelector('li').offsetWidth;
        let containerWidth = section.offsetWidth;
    	let visibleItems = Math.floor(containerWidth / liWidth);
        let scrollAmount = liWidth * visibleItems;

		function updateScrollButtons() {
			if (ul.scrollLeft <= 0) {
				leftScroll.style.display = 'none';
				rightScroll.style.display = 'block';
			} else if (ul.scrollLeft + ul.clientWidth >= ul.scrollWidth) {
				rightScroll.style.display = 'none';
				leftScroll.style.display = 'block';
			} else {
				leftScroll.style.display = 'block';
				rightScroll.style.display = 'block';
			}
		}
		
		updateScrollButtons();		

        rightScroll.addEventListener('click', function (event) {
            event.preventDefault();

            ul.scrollTo({
                left: ul.scrollLeft + scrollAmount,
                behavior: 'smooth'
            });
        });

        leftScroll.addEventListener('click', function (event) {
            event.preventDefault();

            ul.scrollTo({
                left: ul.scrollLeft - scrollAmount,
                behavior: 'smooth'
            });
        });

		ul.addEventListener('scroll', updateScrollButtons);

        window.addEventListener('resize', function () {
			updateScrollButtons();
            let newContainerWidth = section.offsetWidth;
            if (newContainerWidth !== containerWidth) {
                containerWidth = newContainerWidth;
                visibleItems = Math.floor(containerWidth / liWidth);
                scrollAmount = liWidth * visibleItems;
            }
			if (window.innerWidth < 768 ) {
				rightScroll.style.display = 'none';
			}
        });
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