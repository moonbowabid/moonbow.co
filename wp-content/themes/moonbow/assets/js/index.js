document.addEventListener("DOMContentLoaded", function () {
  // Header top find_out container close function (cookie based)
  (function () {
    // run on all pages (banner lives in the global header)
    console.log("Running top advertisement script");

    const advertisementSection = document.querySelector(
      ".top_advertisement_section",
    );
    console.log("Advertisement section found:", !!advertisementSection);
    const Ad_closeBtn = document.querySelector(".header_topfinout_remove_btn");

    if (!advertisementSection || !Ad_closeBtn) return;

    const COOKIE_NAME = "top_header_ad_closed";
    const HOURS = 24;

    function setCookie(name, value, hours) {
      const date = new Date();
      date.setTime(date.getTime() + hours * 60 * 60 * 1000);
      document.cookie =
        name + "=" + value + "; expires=" + date.toUTCString() + "; path=/";
    }

    function getCookieValue(name) {
      const match = document.cookie
        .split("; ")
        .find((row) => row.startsWith(name + "="));

      return match ? match.split("=")[1] : null;
    }
    advertisementSection.style.display = "none";

    if (getCookieValue(COOKIE_NAME) !== "yes") {
      advertisementSection.style.display = "block";
    }

    Ad_closeBtn.addEventListener("click", function () {
      advertisementSection.style.display = "none";
      setCookie(COOKIE_NAME, "yes", HOURS);
    });
  })();
  // Weather info fetch and display
  (async function () {
    const apiKey = "0a7de8a9f29826d016088174067533b5";
    const lat = 52.0052167;
    const lon = -0.6994821;

    const weatherClasses = {
      Clear: "clear",
      Clouds: "clouds",
      Rain: "rain",
      Snow: "snow",
    };

    async function fetchWeather() {
      // Clear; Clouds;  Rain; Drizzle; Thunderstorm; Snow; Mist; Smoke; Haze; Fog; Dust; Sand; Ash; Squall; Tornado;
      const iconEl = document.querySelector(".weather-svg-container");
      const weatherTextEl = document.getElementById("weather-text");

      // Bail out when the weather widget isn't on the page (e.g. Elementor editor preview)
      if (!iconEl || !weatherTextEl) return;

      try {
        // Fetch weather
        const weatherRes = await fetch(
          `https://api.openweathermap.org/data/2.5/weather?lat=${lat}&lon=${lon}&units=metric&appid=${apiKey}`,
        );
        if (!weatherRes.ok) throw new Error("Weather fetch failed");

        const weatherData = await weatherRes.json();
        const weatherMain = weatherData.weather[0].main;

        // Reverse geocoding
        const geoRes = await fetch(
          `https://api.openweathermap.org/geo/1.0/reverse?lat=${lat}&lon=${lon}&limit=1&appid=${apiKey}`,
        );
        if (!geoRes.ok) throw new Error("Reverse geocoding failed");
        const geoData = await geoRes.json();
        const locationName = geoData[0]?.name || "Unknown Location";
        // Determine the weather class
        let weatherClass;

        if (weatherClasses[weatherMain]) {
          weatherClass = weatherClasses[weatherMain];
        } else {
          weatherClass = "unknown-weather";
        }

        // Remove old custom classes
        Object.values(weatherClasses).forEach((cls) =>
          iconEl.classList.remove(cls),
        );

        if (weatherClasses[weatherMain]) {
          // Use your custom SVG class
          iconEl.style.backgroundImage = ""; // clear inline icon
          iconEl.classList.add(weatherClasses[weatherMain]);
        } else {
          // Use OpenWeather's own icon automatically
          const iconCode = weatherData.weather[0].icon;

          iconEl.classList.remove(...Object.values(weatherClasses));

          iconEl.style.background = `url("https://openweathermap.org/img/wn/${iconCode}@2x.png") no-repeat center`;
          iconEl.style.backgroundSize = "contain";
        }

        // Add the new weather class
        iconEl.classList.add(weatherClass);
        // Update the text
        weatherTextEl.innerText = `${weatherMain} in Moonbow HQ, ${locationName}`;
      } catch (err) {
        console.error(err);
        if (weatherTextEl) {
          weatherTextEl.innerText = "Weather info not available!";
        }
      }
    }

    fetchWeather();
  })();

  //Home page business case studies counts
  (function () {
    const counters = document.querySelectorAll(".count");
    if (!counters.length) return;

    const startCount = (counter) => {
      const rawValue = counter.textContent.trim();
      const target = Number(rawValue);

      // stop if invalid number
      if (!Number.isFinite(target) || target <= 0) return;

      let current = 0;
      const duration = 500; // ms
      const startTime = performance.now();

      const updateCount = (now) => {
        const progress = Math.min((now - startTime) / duration, 1);
        current = Math.floor(progress * target);
        counter.textContent = current;

        if (progress < 1) {
          requestAnimationFrame(updateCount);
        } else {
          counter.textContent = target;
        }
      };

      requestAnimationFrame(updateCount);
    };

    const observer = new IntersectionObserver(
      (entries, observer) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            startCount(entry.target);
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.5 },
    );

    counters.forEach((counter) => observer.observe(counter));
  })();
  (function () {
    /**
     * Equalize min-height based on tallest element
     */
    function setEqualMinHeight(selector) {
      const elements = document.querySelectorAll(selector);
      if (!elements.length) return;

      // RESET (write)
      elements.forEach((el) => {
        el.style.minHeight = "";
      });

      // READ
      let maxHeight = 0;
      elements.forEach((el) => {
        const height = el.getBoundingClientRect().height;
        if (height > maxHeight) {
          maxHeight = height;
        }
      });

      // APPLY (write)
      elements.forEach((el) => {
        el.style.minHeight = `${Math.ceil(maxHeight)}px`;
      });
    }

    function initEqualHeights() {
      setEqualMinHeight(".heading_h3");
      setEqualMinHeight(".animation_box_peragraph");
    }

    // run after full page load (images, fonts)
    window.addEventListener("load", initEqualHeights);

    // resize (debounced)
    let resizeTimer;
    window.addEventListener("resize", () => {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(initEqualHeights, 150);
    });
  })();
  (function () {
    const menuBtn = document.querySelector(".hfe-nav-menu__toggle");
    if (!menuBtn) return;

    menuBtn.addEventListener("click", function () {
      // prevent rapid double clicks
      if (menuBtn.classList.contains("menu-click-lock")) return;

      menuBtn.classList.add("menu-click-lock");

      setTimeout(() => {
        menuBtn.classList.remove("menu-click-lock");
      }, 500);
    });
  })();
});
jQuery(function ($) {
  const $slider = $(".real-companies-block");

  if (
    $slider.find(".case-studies__ctn.is-swiper, .case-studies__ctn.is-grid")
      .length
  ) {
    $slider.show();
  } else {
    $slider.hide();
  }
});

jQuery(document).on("gform_post_render", function (event, formId) {
  if (formId !== 4) return;

  var $form = jQuery("#gform_4");
  if (!$form.length) return;

  // -------- PHONE FIX (your existing code) --------
  $form.off("submit.phoneFix").on("submit.phoneFix", function () {
    var input = document.getElementById("input_4_4");
    if (!input) return true;

    var dialCodeElement = $form.find(".iti__selected-dial-code");
    if (!dialCodeElement.length) return true;

    var dialCode = dialCodeElement.text().trim();
    var phoneNumber = input.value ? input.value.trim() : "";

    if (!phoneNumber) return true;

    phoneNumber = phoneNumber.replace(/[\s\-\(\)]/g, "");

    if (phoneNumber.charAt(0) === "0") {
      phoneNumber = phoneNumber.substring(1);
    }

    input.value = dialCode + " " + phoneNumber;

    return true;
  });

  // -------- OBSERVE BUTTON CLASS CHANGE --------
  const observer = new MutationObserver(function () {
    const btn = document.querySelector(
      "#gform_4 .gform_button.button.form-submitted",
    );

    if (btn) {
      const formBody = document.querySelector(
        "#gform_4 .gform-body.gform_body",
      );

      if (formBody && !formBody.classList.contains("form-submitted")) {
        formBody.classList.add("form-submitted");
      }
    }
  });

  observer.observe(document.querySelector("#gform_4"), {
    childList: true,
    subtree: true,
  });
});

/*
 * ElementsKit mega menu — keep the dropdown panel inside the viewport.
 * Opens the panel to the RIGHT from the menu item, and only shifts it left
 * far enough to stay on screen (never crosses the left/right window edge).
 *
 * ElementsKit injects the panel's content lazily on hover, so we reposition
 * via a MutationObserver (fires once content is in and the panel is measurable)
 * plus mouseenter as a backup. Desktop only; mobile off-canvas is untouched.
 */
(function () {
  var MOBILE_BP = 1025; // ElementsKit collapses to hamburger below this
  var EDGE = 16; // min gap from the window edge (px)

  function positionPanel(panel) {
    var li = panel.closest("li");
    if (!li) return;
    var vw = document.documentElement.clientWidth;

    // Mobile: clear inline positioning and let ElementsKit handle it.
    if (vw < MOBILE_BP) {
      panel.style.left = "";
      panel.style.right = "";
      return;
    }

    var panelWidth = panel.offsetWidth;
    if (!panelWidth) return; // content not injected yet

    var itemLeft = li.getBoundingClientRect().left;
    var left = itemLeft; // preferred: open rightward from the item's left edge
    if (left + panelWidth + EDGE > vw) left = vw - panelWidth - EDGE; // shift left to fit
    if (left < EDGE) left = EDGE; // never start off the left edge

    var op = panel.offsetParent;
    var opLeft = op ? op.getBoundingClientRect().left : 0;
    panel.style.left = Math.round(left - opLeft) + "px";
    panel.style.right = "auto";
  }

  function init() {
    var panels = document.querySelectorAll(
      ".elementskit-navbar-nav .elementskit-megamenu-panel",
    );
    if (!panels.length) return;

    panels.forEach(function (panel) {
      // Reposition whenever ElementsKit injects content or toggles the
      // open class. Only child/class changes are observed (not our own
      // style writes) so this can't loop.
      var obs = new MutationObserver(function () {
        positionPanel(panel);
      });
      obs.observe(panel, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ["class"],
      });

      var li = panel.closest("li");
      if (li) {
        li.addEventListener("mouseenter", function () {
          // small delay to let ElementsKit inject the content first
          setTimeout(function () {
            positionPanel(panel);
          }, 30);
        });
      }
    });

    window.addEventListener("resize", function () {
      panels.forEach(function (panel) {
        var li = panel.closest("li");
        if (li && li.matches(":hover")) positionPanel(panel);
      });
    });
  }

  if (document.readyState !== "loading") init();
  else document.addEventListener("DOMContentLoaded", init);
})();

/* Mobile off-canvas: let a tap on a parent menu TITLE (e.g. "Services") open
   its submenu, not just the caret icon. ElementsKit only toggles on the icon
   while the menu carries the `submenu-click-on-icon` class; removing that class
   at mobile widths lets ElementsKit's own handler fire for the whole parent
   link. That handler is bound to `.elementskit-dropdown-has > a`, so childless
   items (About us, Contact us, …) keep navigating normally. */
(function () {
  var BREAKPOINT = 1024;

  function applyTitleToggle() {
    var isMobile = window.innerWidth <= BREAKPOINT;
    var menus = document.querySelectorAll(".elementskit-navbar-nav");
    menus.forEach(function (menu) {
      // Record the original setting once so desktop behaviour can be restored.
      if (menu.dataset.mbClickOnIcon === undefined) {
        menu.dataset.mbClickOnIcon = menu.classList.contains(
          "submenu-click-on-icon",
        )
          ? "1"
          : "0";
      }
      if (menu.dataset.mbClickOnIcon !== "1") return; // wasn't icon-only anyway
      menu.classList.toggle("submenu-click-on-icon", !isMobile);
    });
  }

  function init() {
    applyTitleToggle();
    window.addEventListener("resize", applyTitleToggle);
  }

  if (document.readyState !== "loading") init();
  else document.addEventListener("DOMContentLoaded", init);
})();
