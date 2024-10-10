function manageNav() {
  const header = document.querySelector(".wt_header");
  const logoLight = document.querySelector(".wt_header__logo--light");
  const logoDark = document.querySelector(".wt_header__logo--dark");
  const navContainer = document.querySelector(".wt_header__nav");
  const elements = [header, logoLight, logoDark, navContainer]

  function toggleNavTransparency(method) {
    for (let i = 0; i < elements.length; i++) {
      elements[i].classList[method]("transparent-background");
    }
  }
  if (window.scrollY < 50) {
    toggleNavTransparency("add")
  } else {
    toggleNavTransparency("remove")
  }

}

function changeHeaderClass() {
  window.addEventListener("scroll", function () {
    manageNav()
  });
}

function carouselScroll() {
  const rightScrollList = document.querySelectorAll(
    ".wt_carousel__right-scroll"
  );
  const leftScrollList = document.querySelectorAll(".wt_carousel__left-scroll");
  const sectionList = document.querySelectorAll(".wt_carousel__wrapper");

  sectionList.forEach((section, index) => {
    const rightScroll = rightScrollList[index];
    const leftScroll = leftScrollList[index];
    const ul = section.querySelector("ul");
    const liWidth = ul.querySelector("li").offsetWidth;
    let containerWidth = section.offsetWidth;
    let visibleItems = Math.floor(containerWidth / liWidth);
    let scrollAmount = liWidth * visibleItems;

    function updateScrollButtons() {
      if (ul.scrollLeft <= 0) {
        leftScroll.style.display = "none";
        rightScroll.style.display = "block";
      } else if (ul.scrollLeft + ul.clientWidth >= ul.scrollWidth) {
        rightScroll.style.display = "none";
        leftScroll.style.display = "block";
      } else {
        leftScroll.style.display = "block";
        rightScroll.style.display = "block";
      }
    }

    updateScrollButtons();

    rightScroll.addEventListener("click", function (event) {
      event.preventDefault();

      ul.scrollTo({
        left: ul.scrollLeft + scrollAmount,
        behavior: "smooth",
      });
    });

    leftScroll.addEventListener("click", function (event) {
      event.preventDefault();

      ul.scrollTo({
        left: ul.scrollLeft - scrollAmount,
        behavior: "smooth",
      });
    });

    ul.addEventListener("scroll", updateScrollButtons);

    window.addEventListener("resize", function () {
      updateScrollButtons();
      let newContainerWidth = section.offsetWidth;
      if (newContainerWidth !== containerWidth) {
        containerWidth = newContainerWidth;
        visibleItems = Math.floor(containerWidth / liWidth);
        scrollAmount = liWidth * visibleItems;
      }
      if (window.innerWidth < 768) {
        rightScroll.style.display = "none";
      }
    });
  });
}

function mobileTrigger() {
  var mobileNavOpen = document.getElementById("mobile-nav-open");
  var mobileNavClose = document.getElementById("mobile-nav-close");
  var mobileNav = document.querySelector(".wt_header__nav--mobile");
  var body = document.body;

  mobileNavOpen.addEventListener("click", function () {
    body.classList.add("no-scroll");
    this.style.display = "none";
    mobileNav.classList.add("expand");
    mobileNavClose.style.display = "block";

    mobileNavClose.addEventListener("click", function () {
      this.style.display = "none";
      mobileNav.classList.remove("expand");
      mobileNavOpen.style.display = "block";
      body.classList.remove("no-scroll");
    });
  });
}

window.addEventListener("load", function () {
  if (document.body.classList.contains("home")) {
    changeHeaderClass();
    manageNav()
  }
  carouselScroll();
  mobileTrigger();
});

document.addEventListener("DOMContentLoaded", function () {
  setActiveLinks();
  toggleDarkOverlay();
});

function setActiveLinks() {
  let currentPath = window.location.pathname;

  // List of language paths to match
  let languagePaths = ['/wikitongues/languages/','/wikitongues/videos/']; // Add your language paths here

  // Check if current path matches any language paths
  let isLanguagePath = languagePaths.some(function(path) {
      return currentPath.startsWith(path);
  });

  if (isLanguagePath) {
      // Add active class to the archive nav item
      let archiveNavItem = document.querySelector('nav li#menu-item-15090');
      if (archiveNavItem) {
          archiveNavItem.classList.add('current_page_item');
      }
  }
}

function toggleDarkOverlay() {
  const nav = document.querySelector(".wt_header__nav");
  const darkBackground = document.createElement("div");
  darkBackground.id = "dark";
  nav.appendChild(darkBackground);

  const navItems = document.querySelectorAll(".wt_header__nav .menu-item");
  function showDarkOverlay() {
    darkBackground.classList.add("visible");
  }

  function hideDarkOverlay() {
    darkBackground.classList.remove("visible");
  }

  navItems.forEach((item) => {
    item.addEventListener("mouseenter", function() {
      if (!item.classList.contains("current_page_item")) {
        showDarkOverlay();
      }
    });
    item.addEventListener("mouseleave", hideDarkOverlay);
  });
}