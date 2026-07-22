(function($) {
    'use strict';

    /**
     * Enhanced Repeater Field - Standalone Implementation
     * Works independently without requiring ACF's JavaScript object
     */

    function initializeRepeater($repeater) {
        // Skip if already initialized
        if ($repeater.data('agrfuxd-initialized')) {
            return;
        }
        $repeater.data('agrfuxd-initialized', true);

        var $tbody = $repeater.find('tbody');
        var minRows = parseInt($repeater.attr('data-min')) || 0;
        var maxRows = parseInt($repeater.attr('data-max')) || 0;

        // Make rows sortable
        if ($.fn.sortable && $tbody.length) {
            $tbody.sortable({
                handle: '.acf-row-handle.order',
                axis: 'y',
                helper: function(e, tr) {
                    var $originals = tr.children();
                    var $helper = tr.clone();
                    $helper.children().each(function(index) {
                        $(this).width($originals.eq(index).width());
                    });
                    return $helper;
                },
                placeholder: 'acf-sortable-placeholder',
                start: function(e, ui) {
                    ui.placeholder.height(ui.item.height());
                }
            });
        }

        // Handle add row button
        $repeater.off('click', '[data-event="add-row"]').on('click', '[data-event="add-row"]', function(e) {
            e.preventDefault();
            addRow($repeater);
        });

        // Handle remove row button
        $repeater.off('click', '[data-event="remove-row"]').on('click', '[data-event="remove-row"]', function(e) {
            e.preventDefault();
            removeRow($(this).closest('.acf-row'));
        });

        // Handle duplicate row button
        $repeater.off('click', '[data-event="duplicate-row"]').on('click', '[data-event="duplicate-row"]', function(e) {
            e.preventDefault();
            duplicateRow($(this).closest('.acf-row'));
        });

        // Handle collapse/expand
        $repeater.off('click', '.acf-icon.-collapse').on('click', '.acf-icon.-collapse', function(e) {
            e.preventDefault();
            toggleRow($(this).closest('.acf-row'));
        });
    }

    function addRow($repeater) {
        var $tbody = $repeater.find('tbody');
        var $template = $repeater.find('.tmpl-row');
        var maxRows = parseInt($repeater.attr('data-max')) || 0;
        var currentRows = $tbody.find('.acf-row').length;

        // Check max rows
        if (maxRows > 0 && currentRows >= maxRows) {
            alert('Maximum rows reached (' + maxRows + ' rows)');
            return;
        }

        // Get template HTML
        var templateHTML = $template.html();
        var newIndex = Date.now();

        // Replace acfcloneindex with unique index
        templateHTML = templateHTML.replace(/acfcloneindex/g, newIndex);

        // Create new row
        var $newRow = $(templateHTML);

        // Append to tbody
        $tbody.append($newRow);

        // Initialize ACF fields in new row if ACF is available
        if (typeof acf !== 'undefined' && typeof acf.doAction === 'function') {
            acf.doAction('append', $newRow);
        }

        // Trigger custom event
        $repeater.trigger('agrfuxd-row-added', [$newRow]);
    }

    function removeRow($row) {
        var $repeater = $row.closest('.acf-repeater[data-type="repeater"]');
        var $tbody = $repeater.find('tbody');
        var minRows = parseInt($repeater.attr('data-min')) || 0;
        var currentRows = $tbody.find('.acf-row').length;

        // Check min rows
        if (minRows > 0 && currentRows <= minRows) {
            alert('Minimum rows required (' + minRows + ' rows)');
            return;
        }

        // Remove row with animation
        $row.addClass('acf-removing');
        setTimeout(function() {
            $row.remove();
            $repeater.trigger('agrfuxd-row-removed');
        }, 250);
    }

    function duplicateRow($row) {
        var $repeater = $row.closest('.acf-repeater[data-type="repeater"]');
        var $tbody = $repeater.find('tbody');
        var maxRows = parseInt($repeater.attr('data-max')) || 0;
        var currentRows = $tbody.find('.acf-row').length;

        // Check max rows
        if (maxRows > 0 && currentRows >= maxRows) {
            alert('Maximum rows reached (' + maxRows + ' rows)');
            return;
        }

        // Clone the row
        var $newRow = $row.clone();
        var newIndex = Date.now();

        // Update field names and IDs
        $newRow.find('[name]').each(function() {
            var $field = $(this);
            var name = $field.attr('name');
            var id = $field.attr('id');

            // Extract current index from name
            var matches = name.match(/\[(\d+)\]/);
            if (matches && matches[1]) {
                var oldIndex = matches[1];
                // Replace old index with new one
                name = name.replace('[' + oldIndex + ']', '[' + newIndex + ']');
                $field.attr('name', name);

                if (id) {
                    id = id.replace(oldIndex, newIndex);
                    $field.attr('id', id);
                }
            }
        });

        // Update data-id
        $newRow.attr('data-id', newIndex);

        // Insert after current row
        $row.after($newRow);

        // Initialize ACF fields in duplicated row if ACF is available
        if (typeof acf !== 'undefined' && typeof acf.doAction === 'function') {
            acf.doAction('append', $newRow);
        }

        // Trigger custom event
        $repeater.trigger('agrfuxd-row-duplicated', [$newRow]);
    }

    function toggleRow($row) {
        $row.toggleClass('-collapsed');
    }

    // Initialize on document ready
    $(document).ready(function() {
        // Find and initialize all repeater fields
        $('.acf-repeater[data-type="repeater"]').each(function() {
            initializeRepeater($(this));
        });
    });

    // Re-initialize when new fields are added (for ACF field groups)
    $(document).on('acf/setup_fields', function(e, $el) {
        $el.find('.acf-repeater[data-type="repeater"]').each(function() {
            initializeRepeater($(this));
        });
    });

    // Also initialize when ACF is ready (if ACF object is available)
    if (typeof acf !== 'undefined') {
        acf.addAction('ready', function() {
            $('.acf-repeater[data-type="repeater"]').each(function() {
                initializeRepeater($(this));
            });
        });

        acf.addAction('append', function($el) {
            $el.find('.acf-repeater[data-type="repeater"]').each(function() {
                initializeRepeater($(this));
            });
        });
    }

})(jQuery);
