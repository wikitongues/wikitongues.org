(function ($) {
  function createCarousel() {
    const carousels = document.querySelectorAll('.wt_carousel__wrapper');

    carousels.forEach((carousel) => {
      const ul = carousel.querySelector('ul');
      const li = ul.querySelector('li');
      const liWidth = li ? li.offsetWidth : 0;
      const totalItems = ul.children.length;
      let containerWidth = carousel.offsetWidth;
      let visibleItems = Math.floor(containerWidth / liWidth);
      let scrollAmount = liWidth * visibleItems;

      // Clone items for infinite scroll effect
      const cloneItems = () => {
        const items = Array.from(ul.children);
        items.forEach(item => ul.appendChild(item.cloneNode(true)));
        items.reverse().forEach(item => ul.insertBefore(item.cloneNode(true), ul.firstChild));
      };
      cloneItems();

      // Set initial scroll position
      ul.scrollLeft = liWidth * totalItems;

      // Create navigation buttons
      const leftScroll = document.createElement('button');
      leftScroll.className = 'wt_carousel__left-scroll';
      leftScroll.innerText = '<';
      carousel.appendChild(leftScroll);

      const rightScroll = document.createElement('button');
      rightScroll.className = 'wt_carousel__right-scroll';
      rightScroll.innerText = '>';
      carousel.appendChild(rightScroll);

      // Create pagination dots
      const pagination = document.createElement('div');
      pagination.className = 'wt_carousel__pagination';
      for (let i = 0; i < totalItems; i++) {
        const dot = document.createElement('button');
        dot.className = 'wt_carousel__dot';
        dot.dataset.index = i;
        pagination.appendChild(dot);
      }
      carousel.appendChild(pagination);

      const updatePagination = () => {
        const dots = pagination.querySelectorAll('.wt_carousel__dot');
        dots.forEach(dot => dot.classList.remove('active'));
        const activeIndex = Math.floor((ul.scrollLeft % (liWidth * totalItems)) / liWidth);
        dots[activeIndex].classList.add('active');
      };

      // Event listeners for buttons and pagination
      rightScroll.addEventListener('click', function (event) {
        event.preventDefault();
        ul.scrollTo({
          left: ul.scrollLeft + scrollAmount,
          behavior: 'smooth',
        });
        setTimeout(() => {
          if (ul.scrollLeft >= ul.scrollWidth - (liWidth * totalItems)) {
            ul.scrollLeft = liWidth * totalItems;
          }
          updatePagination();
        }, 500);
      });

      leftScroll.addEventListener('click', function (event) {
        event.preventDefault();
        ul.scrollTo({
          left: ul.scrollLeft - scrollAmount,
          behavior: 'smooth',
        });
        setTimeout(() => {
          if (ul.scrollLeft <= 0) {
            ul.scrollLeft = ul.scrollWidth - (2 * liWidth * totalItems);
          }
          updatePagination();
        }, 500);
      });

      pagination.addEventListener('click', function (event) {
        if (event.target.classList.contains('wt_carousel__dot')) {
          const index = parseInt(event.target.dataset.index, 10);
          ul.scrollTo({
            left: liWidth * (index + totalItems),
            behavior: 'smooth',
          });
          setTimeout(updatePagination, 500);
        }
      });

      ul.addEventListener('scroll', updatePagination);

      window.addEventListener('resize', function () {
        containerWidth = carousel.offsetWidth;
        visibleItems = Math.floor(containerWidth / liWidth);
        scrollAmount = liWidth * visibleItems;
        ul.scrollLeft = liWidth * totalItems; // Reset to start of original items
      });

      // Initial pagination update
      updatePagination();
    });
  }

  // Use WordPress's jQuery document ready function if available
  $(document).ready(function () {
    createCarousel();
  });
})(jQuery);
