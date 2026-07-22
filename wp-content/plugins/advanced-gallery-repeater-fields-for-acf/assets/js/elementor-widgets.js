/**
 * AGRFUXD Elementor Widgets JavaScript
 *
 * Handles interactive functionality for Enhanced Gallery and Enhanced Repeater widgets
 */

(function($) {
    'use strict';

    /**
     * Initialize all AGRFUXD Elementor widgets
     */
    function initWidgets($scope) {
        var $container = $scope || $(document);

        // Initialize Accordion
        initAccordion($container);

        // Initialize Tabs
        initTabs($container);

        // Initialize Gallery (if masonry)
        initMasonryGallery($container);
    }

    /**
     * Initialize Accordion functionality
     */
    function initAccordion($container) {
        $container.find('.agrfuxd-repeater-accordion').each(function() {
            var $accordion = $(this);
            var allowMultiple = $accordion.data('multiple') === true || $accordion.data('multiple') === 'true';

            $accordion.find('.agrfuxd-accordion-header').off('click.agrfuxd').on('click.agrfuxd', function(e) {
                e.preventDefault();

                var $header = $(this);
                var $item = $header.closest('.agrfuxd-accordion-item');
                var $content = $item.find('.agrfuxd-accordion-content');
                var isOpen = $header.hasClass('active');

                if (!allowMultiple) {
                    // Close all other items
                    $accordion.find('.agrfuxd-accordion-item').not($item).each(function() {
                        var $otherItem = $(this);
                        $otherItem.find('.agrfuxd-accordion-header').removeClass('active').attr('aria-expanded', 'false');
                        $otherItem.find('.agrfuxd-accordion-content').slideUp(300);
                        $otherItem.removeClass('active');
                    });
                }

                // Toggle current item
                if (isOpen) {
                    $header.removeClass('active').attr('aria-expanded', 'false');
                    $content.slideUp(300);
                    $item.removeClass('active');
                } else {
                    $header.addClass('active').attr('aria-expanded', 'true');
                    $content.slideDown(300);
                    $item.addClass('active');
                }
            });
        });
    }

    /**
     * Initialize Tabs functionality
     */
    function initTabs($container) {
        $container.find('.agrfuxd-repeater-tabs').each(function() {
            var $tabs = $(this);

            $tabs.find('.agrfuxd-tab-title').off('click.agrfuxd').on('click.agrfuxd', function(e) {
                e.preventDefault();

                var $tab = $(this);
                var tabId = $tab.data('tab');

                // Update active states
                $tabs.find('.agrfuxd-tab-title').removeClass('active').attr('aria-selected', 'false');
                $tab.addClass('active').attr('aria-selected', 'true');

                // Show corresponding panel
                $tabs.find('.agrfuxd-tab-panel').removeClass('active').hide();
                $tabs.find('#' + tabId).addClass('active').fadeIn(300);
            });

            // Keyboard navigation
            $tabs.find('.agrfuxd-tab-title').on('keydown', function(e) {
                var $current = $(this);
                var $allTabs = $tabs.find('.agrfuxd-tab-title');
                var currentIndex = $allTabs.index($current);

                switch (e.keyCode) {
                    case 37: // Left arrow
                    case 38: // Up arrow
                        e.preventDefault();
                        var prevIndex = currentIndex > 0 ? currentIndex - 1 : $allTabs.length - 1;
                        $allTabs.eq(prevIndex).focus().click();
                        break;
                    case 39: // Right arrow
                    case 40: // Down arrow
                        e.preventDefault();
                        var nextIndex = currentIndex < $allTabs.length - 1 ? currentIndex + 1 : 0;
                        $allTabs.eq(nextIndex).focus().click();
                        break;
                    case 36: // Home
                        e.preventDefault();
                        $allTabs.first().focus().click();
                        break;
                    case 35: // End
                        e.preventDefault();
                        $allTabs.last().focus().click();
                        break;
                }
            });
        });
    }

    /**
     * Initialize Masonry Gallery
     */
    function initMasonryGallery($container) {
        if (typeof Masonry === 'undefined') {
            return;
        }

        $container.find('.agrfuxd-gallery-masonry').each(function() {
            var $gallery = $(this);

            // Wait for images to load
            $gallery.imagesLoaded(function() {
                new Masonry($gallery[0], {
                    itemSelector: '.agrfuxd-masonry-item',
                    columnWidth: '.agrfuxd-gallery-sizer',
                    percentPosition: true,
                    gutter: parseInt($gallery.css('--agrfuxd-gap')) || 15
                });
            });
        });
    }

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        initWidgets();
    });

    /**
     * Elementor Frontend handler
     */
    $(window).on('elementor/frontend/init', function() {
        // For Enhanced Gallery widget
        if (typeof elementorFrontend !== 'undefined') {
            elementorFrontend.hooks.addAction('frontend/element_ready/agrfuxd_enhanced_gallery.default', function($scope) {
                initMasonryGallery($scope);
            });

            // For Enhanced Repeater widget
            elementorFrontend.hooks.addAction('frontend/element_ready/agrfuxd_enhanced_repeater.default', function($scope) {
                initAccordion($scope);
                initTabs($scope);
            });
        }
    });

    /**
     * Reinitialize on AJAX content load
     */
    $(document).on('ajaxComplete', function() {
        initWidgets();
    });

    /**
     * Handle window resize for responsive layouts
     */
    var resizeTimer;
    $(window).on('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            // Reinitialize masonry on resize
            $('.agrfuxd-gallery-masonry').each(function() {
                var $gallery = $(this);
                if ($gallery.data('masonry')) {
                    $gallery.masonry('layout');
                }
            });
        }, 250);
    });

})(jQuery);
