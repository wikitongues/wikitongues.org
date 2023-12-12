function changeHeaderClass() {
  var header = document.querySelector(".wt_header");
  var logoLight = document.querySelector(".wt_header__logo--light");
  var logoDark = document.querySelector(".wt_header__logo--dark");
  var navContainer = document.querySelector(".wt_header__nav");

  window.addEventListener("scroll", function () {
    if (window.pageYOffset > 50) {
      header.classList.remove("transparent-background");
      logoLight.classList.remove("transparent-background");
      logoDark.classList.remove("transparent-background");
      navContainer.classList.remove("transparent-background");
    } else {
      header.classList.add("transparent-background");
      logoLight.classList.add("transparent-background");
      logoDark.classList.add("transparent-background");
      navContainer.classList.add("transparent-background");
    }
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
  }
  carouselScroll();
  mobileTrigger();
});

document.addEventListener("DOMContentLoaded", function () {
  const navItems = document.querySelectorAll(".menu-item-has-children");
  const nav = document.querySelector(".wt_header__nav");
  const menu = document.querySelector("#menu-main-menu");
  const darkBackground = document.createElement("div");
  darkBackground.id = "dark";
  darkBackground.style.display = "none";
  darkBackground.style.content = "";
  darkBackground.style.position = "fixed";
  darkBackground.style.zIndex = "-1";
  darkBackground.style.top = "0";
  darkBackground.style.left = "0";
  darkBackground.style.width = "100%";
  darkBackground.style.height = "100vh";
  darkBackground.style.background = "rgba(0, 0, 0, 0.4)";

  function hideAllSubMenus() {
    const subMenus = document.querySelectorAll(".sub-menu");
    subMenus.forEach((menu) => {
      if (menu.parentElement.hasAttribute("id")) {
        menu.style.display = "none";
      }
    });
  }

  function fadeIn(element) {
    element.style.opacity = 0;
    element.style.transition = "opacity 0.2s ease";

    let opacity = 0;
    const intervalTime = 10;
    const targetOpacity = 1;
    const increment = 0.05;

    const interval = setInterval(() => {
      if (opacity >= targetOpacity) {
        clearInterval(interval);
      } else {
        opacity += increment;
        element.style.opacity = opacity;
      }
    }, intervalTime);
  }

  function addDarkOverlay() {
    darkBackground.style.display = "block";
    nav.appendChild(darkBackground);
    fadeIn(darkBackground);
  }

  function fadeOut(element) {
    element.style.transition = "opacity 0.2s ease";

    const intervalTime = 10;
    const decrement = 0.05;

    const interval = setInterval(() => {
      if (element.style.opacity <= 0) {
        clearInterval(interval);
        element.style.display = "none";
      } else {
        element.style.opacity -= decrement;
      }
    }, intervalTime);
  }

  function removeDarkOverlay() {
    const dark = document.querySelector("#dark");
    if (dark) {
      fadeOut(dark);
    }
  }

  navItems.forEach((item) => {
    if (item.parentElement.id == "menu-main-menu") {
      const subMenu = item.querySelector(".sub-menu");
      const currItem =
        item.classList.contains("current_page_parent") ||
        item.classList.contains("current_page_item");
      if (currItem) {
        item.addEventListener("mouseover", () => {
          darkBackground.style.display = "none";
          hideAllSubMenus();
        });
      } else if (subMenu) {
        menu.addEventListener("mouseenter", () => {
          addDarkOverlay();
        });
        item.addEventListener("mouseover", () => {
          hideAllSubMenus();
          subMenu.style.display = "block";
          darkBackground.style.display = "block";
        });
        item.addEventListener("mouseout", () => {
          hideAllSubMenus();
        });
        menu.addEventListener("mouseleave", () => {
          removeDarkOverlay();
          hideAllSubMenus();
        });
      }
    }
  });

  const donate = document.querySelector(".menu-item-type-custom");
  donate.addEventListener("mouseenter", () => {
    darkBackground.style.display = "block";
    hideAllSubMenus();
  });

  let body = document.querySelector(".page");
  let home = document.querySelector(".home");
  let wpadminbar = document.querySelector("#wpadminbar");
  let bannerSearchBar = document.querySelector(".wt_banner--searchbar");

  let header = document.querySelector(".wt_header");
  let alertContainer = document.querySelector(".banner_alert_container");
  let alertHeight = alertContainer ? alertContainer.offsetHeight : 0;

  if (body && !home && alertContainer) {
    alertContainer.style.position = "absolute";
    alertContainer.style.top = 0;
  }

  let banner = document.querySelector(".wt_banner");

  if (body && !home && banner && alertContainer && !wpadminbar) {
    banner.style.paddingTop =
      parseInt(
        window.getComputedStyle(banner).getPropertyValue("padding-top"),
        10
      ) +
      alertContainer.offsetHeight +
      "px";
  }
  if (body && !home && banner && alertContainer && wpadminbar) {
    banner.style.paddingTop =
      parseInt(
        window.getComputedStyle(banner).getPropertyValue("padding-top"),
        10
      ) +
      wpadminbar.offsetHeight +
      alertContainer.offsetHeight +
      "px";
  }

  if(body && !home && bannerSearchBar && alertContainer && !wpadminbar){
    bannerSearchBar.style.paddingTop =
      parseInt(
        window
          .getComputedStyle(bannerSearchBar)
          .getPropertyValue("padding-top"),
        10
      ) +
      alertContainer.offsetHeight +
      "px";
  }
  if (body && !home && bannerSearchBar && alertContainer && wpadminbar) {
    bannerSearchBar.style.paddingTop =
      parseInt(
        window
          .getComputedStyle(bannerSearchBar)
          .getPropertyValue("padding-top"),
        10
      ) +
      wpadminbar.offsetHeight +
      alertContainer.offsetHeight +
      "px";
  }

  let start = 0;

  function displayBannerAlert() {
    if(alertContainer){
      header.style.marginTop = alertContainer.offsetHeight + "px";
      start = start +1;
    }
  }

  function adjustHeaderMargin() {
    if (alertContainer && alertContainer.style.display!="none" ) {
      let scrollPos = window.scrollY || window.pageYOffset;
      let bannerTop = alertContainer.getBoundingClientRect().top + scrollPos;
      let bannerVisibleHeight = Math.max(
        0,
        alertHeight + bannerTop - scrollPos
      );

      let margin = bannerVisibleHeight - bannerTop;

      if(start===1){
        window.scrollTo(0, 0);
      }
      else{
        header.style.transition = "margin-top 0.2s";
        header.style.marginTop = margin >= 0 ? margin + "px" : "0";
      }

      start = start + 1;
    }
  }

  window.addEventListener("scroll", adjustHeaderMargin);

  displayBannerAlert();

  let bannerAlertCloseButton = document.querySelector("#banner_alert_close_button");

  if(bannerAlertCloseButton){
    bannerAlertCloseButton.addEventListener("click", function() {
      alertContainer.style.display = "none";
      header.style.marginTop = "0px";
    });
  }
});
