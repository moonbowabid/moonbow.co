/**
 * ACF Gallery & Repeater Addon - Frontend JavaScript
 * Handles carousels, lightbox, accordion, tabs, and masonry
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        initCarousels();
        initLightboxes();
        initAccordions();
        initTabs();
        initMasonry();
    });

    /**
     * Initialize Carousels
     */
    function initCarousels() {
        $('.agrfuxd-carousel').each(function() {
            var $carousel = $(this);
            var $track = $carousel.find('.agrfuxd-carousel-track');
            var $slides = $track.find('.agrfuxd-carousel-slide');
            var $dots = $carousel.find('.agrfuxd-carousel-dot');

            var autoplay = $carousel.data('autoplay') === true || $carousel.data('autoplay') === 'true';
            var speed = parseInt($carousel.data('speed')) || 3000;
            var slidesToShow = parseInt($carousel.data('slides-to-show')) || 3;
            var carouselHeight = parseInt($carousel.data('carousel-height')) || 400;

            var currentSlide = 0;
            var totalSlides = $slides.length;
            var totalPages = Math.ceil(totalSlides / slidesToShow);
            var autoplayInterval = null;

            // Set CSS variable for slides
            $carousel.css('--agrfuxd-slides', slidesToShow);

            function setEqualHeight() {
                // Use configured carousel height as exact height
                $carousel.find('.agrfuxd-carousel-wrapper').css({
                    'height': carouselHeight + 'px',
                    'min-height': carouselHeight + 'px'
                });

                // Set the carousel figure to match the height
                $carousel.find('.agrfuxd-carousel-figure').css('height', carouselHeight + 'px');
            }

            // Wait for images to load before setting height
            var imagesLoaded = 0;
            var totalImages = $slides.find('img').length;

            $slides.find('img').each(function() {
                var $img = $(this);
                if (this.complete) {
                    // Image already loaded
                    imagesLoaded++;
                    if (imagesLoaded === totalImages) {
                        setEqualHeight();
                    }
                } else {
                    // Wait for image to load
                    $img.on('load', function() {
                        imagesLoaded++;
                        if (imagesLoaded === totalImages) {
                            setEqualHeight();
                        }
                    });
                }
            });

            // Also set immediately with fallback
            setTimeout(function() {
                setEqualHeight();
            }, 100);

            function goToSlide(index) {
                if (index < 0) index = totalPages - 1;
                if (index >= totalPages) index = 0;

                currentSlide = index;

                // Calculate proper translation
                // Each page shows 'slidesToShow' slides
                // Each slide is 100/slidesToShow percent wide
                // To show page N, we translate by N * 100 percent
                var translateX = -(index * 100);
                $track.css('transform', 'translateX(' + translateX + '%)');

                // Update dots
                $dots.removeClass('active');
                $dots.eq(index).addClass('active');
            }

            // Navigation
            $carousel.find('.agrfuxd-carousel-prev').on('click', function(e) {
                e.preventDefault();
                goToSlide(currentSlide - 1);
                resetAutoplay();
            });

            $carousel.find('.agrfuxd-carousel-next').on('click', function(e) {
                e.preventDefault();
                goToSlide(currentSlide + 1);
                resetAutoplay();
            });

            // Dots
            $dots.on('click', function(e) {
                e.preventDefault();
                goToSlide($(this).data('index'));
                resetAutoplay();
            });

            // Autoplay
            function startAutoplay() {
                if (autoplay && totalPages > 1) {
                    autoplayInterval = setInterval(function() {
                        goToSlide(currentSlide + 1);
                    }, speed);
                }
            }

            function stopAutoplay() {
                if (autoplayInterval) {
                    clearInterval(autoplayInterval);
                    autoplayInterval = null;
                }
            }

            function resetAutoplay() {
                stopAutoplay();
                startAutoplay();
            }

            // Touch/Swipe support
            var touchStartX = 0;
            var touchEndX = 0;

            $carousel.on('touchstart', function(e) {
                touchStartX = e.touches[0].clientX;
            });

            $carousel.on('touchend', function(e) {
                touchEndX = e.changedTouches[0].clientX;
                handleSwipe();
            });

            function handleSwipe() {
                var diff = touchStartX - touchEndX;
                if (Math.abs(diff) > 50) {
                    if (diff > 0) {
                        goToSlide(currentSlide + 1);
                    } else {
                        goToSlide(currentSlide - 1);
                    }
                    resetAutoplay();
                }
            }

            // Pause on hover
            $carousel.on('mouseenter', stopAutoplay);
            $carousel.on('mouseleave', startAutoplay);

            // Start
            startAutoplay();

            // Recalculate on window resize
            var resizeTimer;
            $(window).on('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function() {
                    setEqualHeight();
                }, 250);
            });
        });
    }

    /**
     * Initialize Lightboxes
     */
    function initLightboxes() {
        var currentGallery = null;
        var currentIndex = 0;
        var images = [];
        var originalBodyOverflow = '';
        var originalBodyPosition = '';
        var scrollPosition = 0;

        // Disable Elementor's lightbox for our galleries
        $('.agrfuxd-lightbox-trigger').attr('data-elementor-open-lightbox', 'no');

        // Open lightbox
        $(document).on('click', '.agrfuxd-lightbox-trigger', function(e) {
            e.preventDefault();
            e.stopPropagation(); // Prevent Elementor's lightbox
            e.stopImmediatePropagation(); // Stop all other handlers

            var $trigger = $(this);
            var galleryId = $trigger.data('gallery');
            currentIndex = parseInt($trigger.data('index'));

            // Get all images in this gallery
            images = [];
            $('[data-gallery="' + galleryId + '"]').each(function() {
                images.push({
                    url: $(this).attr('href'),
                    title: $(this).find('img').attr('alt') || '',
                    caption: $(this).closest('figure').find('figcaption').text() || ''
                });
            });

            currentGallery = galleryId;
            openLightbox(currentIndex);
        });

        function openLightbox(index) {
            var $lightbox = $('#' + currentGallery + '-lightbox');
            var image = images[index];
            var isAlreadyOpen = $lightbox.hasClass('is-open');

            $lightbox.find('.agrfuxd-lightbox-image').attr('src', image.url).attr('alt', image.title);
            $lightbox.find('.agrfuxd-lightbox-title').text(image.title);
            $lightbox.find('.agrfuxd-lightbox-caption').text(image.caption);
            $lightbox.find('.agrfuxd-lightbox-counter').text((index + 1) + ' / ' + images.length);

            // Update thumbnail active state
            $lightbox.find('.agrfuxd-lightbox-thumb').removeClass('is-active');
            $lightbox.find('.agrfuxd-lightbox-thumb').eq(index).addClass('is-active');

            // Update current index
            currentIndex = index;

            // Only save scroll position and apply body styles if not already open
            if (!isAlreadyOpen) {
                // Save current scroll position and body styles
                scrollPosition = $(window).scrollTop();
                originalBodyOverflow = $('body').css('overflow');
                originalBodyPosition = $('body').css('position');

                // Prevent scrolling and show lightbox
                $lightbox.attr('aria-hidden', 'false').addClass('is-open');
                $('body').css({
                    'overflow': 'hidden',
                    'position': 'fixed',
                    'width': '100%',
                    'top': -scrollPosition + 'px'
                });
            }

            // Add loaded class to image when it loads
            var $img = $lightbox.find('.agrfuxd-lightbox-image');
            $img.removeClass('is-loaded');
            $img.one('load', function() {
                $(this).addClass('is-loaded');
            });
        }

        function closeLightbox() {
            // Close lightbox
            $('.agrfuxd-lightbox').attr('aria-hidden', 'true').removeClass('is-open');

            // Restore body scrolling
            $('body').css({
                'overflow': originalBodyOverflow || '',
                'position': originalBodyPosition || '',
                'width': '',
                'top': ''
            });

            // Restore scroll position
            $(window).scrollTop(scrollPosition);

            // Force remove any Elementor-added styles
            setTimeout(function() {
                $('body').removeClass('elementor-lightbox-prevent-close');
                $('html, body').css('overflow', '');
            }, 50);
        }

        // Close lightbox
        $(document).on('click', '.agrfuxd-lightbox-close, .agrfuxd-lightbox-overlay', function() {
            closeLightbox();
        });

        // Navigation
        $(document).on('click', '.agrfuxd-lightbox-prev', function() {
            currentIndex = currentIndex > 0 ? currentIndex - 1 : images.length - 1;
            openLightbox(currentIndex);
        });

        $(document).on('click', '.agrfuxd-lightbox-next', function() {
            currentIndex = currentIndex < images.length - 1 ? currentIndex + 1 : 0;
            openLightbox(currentIndex);
        });

        // Thumbnail navigation
        $(document).on('click', '.agrfuxd-lightbox-thumb', function() {
            var thumbIndex = $(this).index();
            openLightbox(thumbIndex);
        });

        // Keyboard navigation
        $(document).on('keydown', function(e) {
            if ($('.agrfuxd-lightbox.is-open').length) {
                if (e.key === 'Escape' || e.keyCode === 27) {
                    e.preventDefault();
                    closeLightbox();
                }
                if (e.key === 'ArrowLeft' || e.keyCode === 37) {
                    e.preventDefault();
                    $('.agrfuxd-lightbox-prev').click();
                }
                if (e.key === 'ArrowRight' || e.keyCode === 39) {
                    e.preventDefault();
                    $('.agrfuxd-lightbox-next').click();
                }
            }
        });
    }

    /**
     * Initialize Accordions
     */
    function initAccordions() {
        $(document).on('click', '.agrfuxd-accordion-header', function() {
            var $header = $(this);
            var $item = $header.parent();
            var $accordion = $item.parent();
            var multiple = $accordion.data('multiple') !== false;

            if (!multiple) {
                $accordion.find('.agrfuxd-accordion-item').not($item).removeClass('active');
                $accordion.find('.agrfuxd-accordion-content').not($header.next()).slideUp(300);
            }

            $item.toggleClass('active');
            $header.next().slideToggle(300);
        });
    }

    /**
     * Initialize Tabs
     */
    function initTabs() {
        $(document).on('click', '.agrfuxd-tab-button', function(e) {
            e.preventDefault();

            var $button = $(this);
            var $tabs = $button.closest('.agrfuxd-tabs');
            var index = $button.index();

            $tabs.find('.agrfuxd-tab-button').removeClass('active');
            $button.addClass('active');

            $tabs.find('.agrfuxd-tab-panel').removeClass('active');
            $tabs.find('.agrfuxd-tab-panel').eq(index).addClass('active');
        });
    }

    /**
     * Initialize Masonry
     */
    function initMasonry() {
        if (typeof $.fn.masonry !== 'function') {
            // Fallback to CSS grid if Masonry not available
            $('.agrfuxd-masonry').css({
                'display': 'grid',
                'grid-template-columns': 'repeat(auto-fill, minmax(250px, 1fr))',
                'grid-gap': '15px'
            });
            return;
        }

        $('.agrfuxd-masonry').each(function() {
            var $masonry = $(this);

            // Wait for images to load
            $masonry.imagesLoaded(function() {
                $masonry.masonry({
                    itemSelector: '.agrfuxd-masonry-item',
                    columnWidth: '.agrfuxd-masonry-item',
                    percentPosition: true,
                    transitionDuration: '0.3s'
                });
            });
        });
    }

})(jQuery);
