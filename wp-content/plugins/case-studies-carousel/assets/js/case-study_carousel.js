document.addEventListener("DOMContentLoaded", function () {
  if (typeof Swiper === "undefined") return;

  const swiperEls = document.querySelectorAll(".case-studies-swiper");

  if (!swiperEls.length) return;

  swiperEls.forEach((swiperEl) => {
    const container = swiperEl.closest(".case-studies__ctn");

    const swiper = new Swiper(swiperEl, {
      slidesPerView: 1,
      spaceBetween: 35,
      slidesPerGroup: 1,
      speed: 500,
      grabCursor: true,

      navigation: {
        nextEl: container?.querySelector(".case-studies-next"),
        prevEl: container?.querySelector(".case-studies-prev"),
      },

      breakpoints: {
        0: { slidesPerView: 1 },
        768: { slidesPerView: 1.5 },
        1024: { slidesPerView: 2.45 },
      },

      on: {
        init: () => setEqualSlideHeight(swiperEl),
        resize: () => setEqualSlideHeight(swiperEl),
        breakpoint: () => setEqualSlideHeight(swiperEl),
      },
    });
  });

  function setEqualSlideHeight(container) {
    const slides = container.querySelectorAll(".case-study-item.swiper-slide");
    let maxHeight = 0;

    slides.forEach((slide) => {
      slide.style.height = "auto";
      maxHeight = Math.max(maxHeight, slide.offsetHeight);
    });

    slides.forEach((slide) => {
      slide.style.height = maxHeight + "px";
    });
  }
});
