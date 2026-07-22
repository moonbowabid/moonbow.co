/**
 * ACF Gallery & Repeater Addon - Gallery Field JS
 * Handles the media library integration for the Enhanced Gallery field
 */
(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        initGalleryFields();
    });

    // Also init on ACF append (for repeaters, flex content, etc.)
    if (typeof acf !== 'undefined') {
        acf.addAction('append', function($el) {
            $el.find('.agrfuxd-gallery-field').each(function() {
                initSingleGallery($(this));
            });
        });
    }

    /**
     * Initialize all gallery fields on the page
     */
    function initGalleryFields() {
        $('.agrfuxd-gallery-field').each(function() {
            initSingleGallery($(this));
        });
    }

    /**
     * Initialize a single gallery field
     */
    function initSingleGallery($field) {
        // Skip if already initialized
        if ($field.data('agrfuxd-initialized')) {
            return;
        }

        $field.data('agrfuxd-initialized', true);

        var $attachments = $field.find('.agrfuxd-gallery-attachments');
        var settings = {
            min: parseInt($field.data('min')) || 0,
            max: parseInt($field.data('max')) || 0,
            library: $field.data('library') || 'all',
            previewSize: $field.data('preview-size') || 'medium',
            insert: $field.data('insert') || 'append',
            mimeTypes: $field.data('mime-types') || ''
        };

        // Make attachments sortable
        $attachments.sortable({
            items: '.agrfuxd-gallery-attachment',
            cursor: 'move',
            tolerance: 'pointer',
            placeholder: 'agrfuxd-gallery-attachment-placeholder',
            forcePlaceholderSize: true,
            start: function(e, ui) {
                ui.placeholder.width(ui.item.width());
                ui.placeholder.height(ui.item.height());
            }
        });

        // Add images button
        $field.on('click', '.agrfuxd-add-images', function(e) {
            e.preventDefault();
            openMediaLibrary($field, settings);
        });

        // Remove single attachment
        $field.on('click', '.agrfuxd-remove-attachment', function(e) {
            e.preventDefault();
            $(this).closest('.agrfuxd-gallery-attachment').fadeOut(200, function() {
                $(this).remove();
                updateRemoveAllButton($field);
            });
        });

        // Edit attachment
        $field.on('click', '.agrfuxd-edit-attachment', function(e) {
            e.preventDefault();
            var attachmentId = $(this).closest('.agrfuxd-gallery-attachment').data('id');
            editAttachment(attachmentId);
        });

        // Remove all button
        $field.on('click', '.agrfuxd-remove-all', function(e) {
            e.preventDefault();
            if (confirm(agrfuxd_gallery.text.confirm_remove_all || 'Remove all images?')) {
                $attachments.find('.agrfuxd-gallery-attachment').fadeOut(200, function() {
                    $(this).remove();
                    updateRemoveAllButton($field);
                });
            }
        });
    }

    /**
     * Open WordPress Media Library
     */
    function openMediaLibrary($field, settings) {
        var $attachments = $field.find('.agrfuxd-gallery-attachments');
        
        // Get existing attachment IDs
        var existingIds = [];
        $attachments.find('.agrfuxd-gallery-attachment').each(function() {
            existingIds.push(parseInt($(this).data('id')));
        });

        // Create media frame
        var frame = wp.media({
            title: agrfuxd_gallery.text.add_images || 'Add Images to Gallery',
            button: {
                text: agrfuxd_gallery.text.select_images || 'Select Images'
            },
            library: {
                type: 'image'
            },
            multiple: true
        });

        // When images are selected
        frame.on('select', function() {
            var selection = frame.state().get('selection');
            var newAttachments = [];

            selection.each(function(attachment) {
                var id = attachment.id;
                
                // Skip if already in gallery
                if (existingIds.indexOf(id) !== -1) {
                    return;
                }

                // Check max limit
                if (settings.max > 0) {
                    var currentCount = $attachments.find('.agrfuxd-gallery-attachment').length;
                    if (currentCount >= settings.max) {
                        alert('Maximum ' + settings.max + ' images allowed.');
                        return false;
                    }
                }

                // Create attachment HTML
                var html = createAttachmentHtml(attachment, $field);
                newAttachments.push(html);
            });

            // Insert new attachments
            if (newAttachments.length > 0) {
                var $newItems = $(newAttachments.join(''));
                
                if (settings.insert === 'prepend') {
                    $attachments.prepend($newItems);
                } else {
                    $attachments.append($newItems);
                }

                // Animate in
                $newItems.hide().fadeIn(300);
                
                updateRemoveAllButton($field);
            }
        });

        frame.open();
    }

    /**
     * Create HTML for a single attachment
     */
    function createAttachmentHtml(attachment, $field) {
        var data = attachment.toJSON();
        var thumbUrl = '';
        var previewSize = $field.data('preview-size') || 'medium';
        
        // Get thumbnail URL
        if (data.sizes) {
            if (data.sizes[previewSize]) {
                thumbUrl = data.sizes[previewSize].url;
            } else if (data.sizes.medium) {
                thumbUrl = data.sizes.medium.url;
            } else if (data.sizes.thumbnail) {
                thumbUrl = data.sizes.thumbnail.url;
            } else {
                thumbUrl = data.url;
            }
        } else {
            thumbUrl = data.url;
        }

        // Get field name from existing input or construct it
        var fieldName = $field.find('input[type="hidden"]').first().attr('name');
        if (fieldName && fieldName.indexOf('[]') === -1) {
            // Make sure it's an array name
        }
        
        // Find the pattern from existing attachments
        var existingInput = $field.find('.agrfuxd-gallery-attachment input[type="hidden"]').first();
        if (existingInput.length) {
            fieldName = existingInput.attr('name');
        } else {
            // Use the base field name + []
            var baseInput = $field.find('> input[type="hidden"]').first();
            if (baseInput.length) {
                fieldName = baseInput.attr('name') + '[]';
            }
        }

        var html = '<div class="agrfuxd-gallery-attachment" data-id="' + data.id + '">';
        html += '<input type="hidden" name="' + fieldName + '" value="' + data.id + '" />';
        html += '<div class="agrfuxd-gallery-attachment-preview">';
        html += '<img src="' + thumbUrl + '" alt="" />';
        html += '</div>';
        html += '<div class="agrfuxd-gallery-attachment-actions">';
        html += '<a href="#" class="agrfuxd-edit-attachment acf-icon -pencil small" title="' + (agrfuxd_gallery.text.edit || 'Edit') + '"></a>';
        html += '<a href="#" class="agrfuxd-remove-attachment acf-icon -minus small" title="' + (agrfuxd_gallery.text.remove || 'Remove') + '"></a>';
        html += '</div>';
        html += '<div class="agrfuxd-gallery-attachment-title">' + (data.title || '') + '</div>';
        html += '</div>';

        return html;
    }

    /**
     * Edit attachment in media modal
     */
    function editAttachment(attachmentId) {
        var attachment = wp.media.attachment(attachmentId);
        
        attachment.fetch().then(function() {
            var frame = wp.media({
                title: 'Edit Image',
                button: {
                    text: 'Update'
                },
                frame: 'select',
                library: {
                    type: 'image'
                }
            });

            frame.on('open', function() {
                var selection = frame.state().get('selection');
                selection.add(attachment);
            });

            frame.open();
        });
    }

    /**
     * Update remove all button visibility
     */
    function updateRemoveAllButton($field) {
        var count = $field.find('.agrfuxd-gallery-attachment').length;
        var $removeAllBtn = $field.find('.agrfuxd-remove-all');
        
        if (count > 0) {
            if ($removeAllBtn.length === 0) {
                $field.find('.agrfuxd-add-images').after(
                    '<button type="button" class="agrfuxd-remove-all button" style="margin-left: 5px;">Remove All</button>'
                );
            }
        } else {
            $removeAllBtn.remove();
        }
    }

})(jQuery);
