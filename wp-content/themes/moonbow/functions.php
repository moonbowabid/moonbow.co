<?php
/**
 * Theme functions and definitions
 *
 * @package HelloElementorChild
 */

/**
 * Load child theme css and optional scripts
 *
 * @return void
 */
 
add_action( 'wp_enqueue_scripts', 'moonbow_enqueue_styles' );
 

function moonbow_enqueue_styles() {

    // Child theme main style
    wp_enqueue_style(
        'moonbow-style',
        get_stylesheet_directory_uri() . '/style.css',
        [],
        wp_get_theme()->get('Version')
    );

    // Custom child CSS (FIXED)
    wp_enqueue_style(
        'moonbow-custom-style',
        get_stylesheet_directory_uri() . '/custome-style.css',
        array('moonbow-style'),
        wp_get_theme()->get('Version')
    );

    // JS
    wp_enqueue_script(
        'moonbow-js',
        get_stylesheet_directory_uri() . '/assets/js/index.js',
        array('jquery'),
        wp_get_theme()->get('Version'),
        true
    );
}

// Keep the anchor to scroll to the form area
add_filter( 'gform_confirmation_anchor_4', '__return_true' );

// The logic to return the form and then the confirmation below it
add_filter( 'gform_confirmation_4', 'persist_form_4_after_submission', 10, 4 );
function persist_form_4_after_submission( $confirmation, $form, $entry, $ajax ) {
    if ( $ajax ) {
        // Get the markup of the form (this is the 'new' instance of the form)
        // We set the 7th parameter (display) to false so it doesn't print immediately
        $form_markup = gravity_form( 4, false, false, false, null, true, 0, false );

        // Wrap your confirmation message
        $success_message = "<div class='gforms_confirmation_message_custom' style='margin-top:20px;'>" . $confirmation . "</div>";

        // Combine: Form FIRST, Message SECOND
        $combined_output = $form_markup . $success_message;

        // Script to unlock the form and handle the button text
        $combined_output .= "<script type='text/javascript'>
            (function() {
                var initUpdate = function() {
                    if (window.jQuery) {
                        var $ = window.jQuery;
                        console.log('Form 4: Re-injected into DOM');
                        
                        // Unlock the form for potential re-submission
                        window['gf_submitting_4'] = false;
                        
                        // Change the button text of the new form instance
                        $('#gform_submit_button_4').addClass('form-submitted');
                        
                        // Note: We removed the .reset() line so data persists
                    } else {
                        setTimeout(initUpdate, 50);
                    }
                };
                initUpdate();
            })();
        </script>";

        return $combined_output;
    }
    return $confirmation;
	function force_video_controls($content) {
    $content = preg_replace('/<video(.*?)>/', '<video$1 controls>', $content);
    return $content;
}
add_filter('the_content', 'force_video_controls');
}
