<?php
/**
 * Plugin Name:       Block Content Protection
 * Description:       A comprehensive plugin to protect website content. Choose which pages and which content types (images, text, video, code, banners) are protected. Blocks screenshots, screen recording, right-click, developer tools, and more.
 * Plugin URI:        https://adschi.com/
 * Version:           2.0.0
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * Author:            Mohammad Babaei (Adschi) & A. Babaei
 * Author URI:        https://adschi.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       block-content-protection
 * Domain Path:       /languages
 *
 * Credits:
 * - Base app developed by: Mohammad Babaei - Adschi (https://adschi.com/)
 * - Extension & development by: A. Babaei
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

define( 'BCP_VERSION', '2.0.0' );
define( 'BCP_DB_VERSION', '2' );
define( 'BCP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BCP_PLUGIN_FILE', __FILE__ );

/**
 * Content types that can be individually protected, and the meta/option
 * keys derived from them ("images" -> "protect_images").
 */
function bcp_get_content_types() {
    return [
        'images'  => __( 'Images', 'block-content-protection' ),
        'text'    => __( 'Text', 'block-content-protection' ),
        'videos'  => __( 'Videos', 'block-content-protection' ),
        'code'    => __( 'Code Blocks', 'block-content-protection' ),
        'banners' => __( 'Banners / Logos', 'block-content-protection' ),
    ];
}

function bcp_get_default_options() {
    return [
        'disable_right_click'       => 1,
        'disable_devtools'          => 1,
        'disable_copy'              => 1,
        'disable_text_selection'    => 1,
        'disable_image_drag'        => 1,
        'disable_video_download'    => 1,
        'disable_screenshot'        => 1,
        'enhanced_protection'       => 0,
        'mobile_screenshot_block'   => 1,
        'video_screen_record_block' => 1,

        // Content-type protection toggles.
        'protect_images'            => 1,
        'protect_text'              => 1,
        'protect_videos'            => 1,
        'protect_code'              => 1,
        'protect_banners'           => 1,
        'banner_selector'           => '.site-logo, .custom-logo, .site-branding img, .wp-block-cover, .banner',

        // Page selection.
        'page_selection_mode'       => 'all', // 'all' or 'selected'
        'excluded_pages'            => [],
        'included_pages'            => [],

        'whitelisted_ips'           => '',
        'screenshot_alert_message'  => __( 'Screenshots are disabled on this site.', 'block-content-protection' ),
        'recording_alert_message'   => __( 'Screen recording detected. Video playback blocked.', 'block-content-protection' ),
        'enable_custom_messages'    => 0,
        'watermark_text'            => '',
        'enable_video_watermark'    => 0,
        'watermark_opacity'         => 0.5,
        'watermark_position'        => 'animated',
        'watermark_style'           => 'text',
    ];
}

function bcp_add_admin_menu() {
    add_menu_page(
        __( 'Content Protection', 'block-content-protection' ),
        __( 'Content Protection', 'block-content-protection' ),
        'manage_options',
        'block_content_protection',
        'bcp_options_page',
        'dashicons-shield-alt', // Icon
        25 // Position
    );
}
add_action( 'admin_menu', 'bcp_add_admin_menu' );

function bcp_register_settings() {
    register_setting( 'bcp_settings_group', 'bcp_options', 'bcp_sanitize_options' );

    // Protection Settings Section
    add_settings_section( 'bcp_protection_section', __( 'Protection Settings', 'block-content-protection' ), null, 'block_content_protection' );
    $protection_fields = [
        'disable_right_click'       => __( 'Disable Right Click', 'block-content-protection' ),
        'disable_devtools'          => __( 'Disable Developer Tools (F12, etc.)', 'block-content-protection' ),
        'disable_copy'              => __( 'Disable Copy (Ctrl+C)', 'block-content-protection' ),
        'disable_text_selection'    => __( 'Disable Text Selection', 'block-content-protection' ),
        'disable_image_drag'        => __( 'Disable Image Dragging', 'block-content-protection' ),
        'disable_video_download'    => __( 'Disable Video Download', 'block-content-protection' ),
        'disable_screenshot'        => __( 'Disable Screenshot Shortcuts', 'block-content-protection' ),
        'enhanced_protection'       => __( 'Enhanced Screen Protection', 'block-content-protection' ),
        'mobile_screenshot_block'   => __( 'Block Mobile Screenshots', 'block-content-protection' ),
        'video_screen_record_block' => __( 'Block Video Screen Recording', 'block-content-protection' ),
    ];
    foreach ($protection_fields as $id => $label) {
        $desc = '';
        if ($id === 'disable_screenshot') {
            $desc = __( 'Blocks PrintScreen and macOS screenshot shortcuts (Cmd+Shift+3/4).', 'block-content-protection' );
        }
        if ($id === 'enhanced_protection') {
            $desc = __( 'Applies additional CSS to interfere with screen capture. Note: These methods are not foolproof.', 'block-content-protection' );
        }
        if ($id === 'mobile_screenshot_block') {
            $desc = __( 'Attempts to block screenshots on mobile devices. Works on some Android devices.', 'block-content-protection' );
        }
        if ($id === 'video_screen_record_block') {
            $desc = __( 'Client-side methods are not foolproof. For the highest level of security, a Digital Rights Management (DRM) service is the recommended solution.', 'block-content-protection' );
        }
        add_settings_field( $id, $label, 'bcp_render_checkbox_field', 'block_content_protection', 'bcp_protection_section', [ 'id' => $id, 'description' => $desc ] );
    }

    // Content Type Protection Section
    add_settings_section(
        'bcp_content_types_section',
        __( 'Content Type Protection', 'block-content-protection' ),
        function () {
            echo '<p class="description">' . esc_html__( 'Choose which kinds of content the protections above are applied to. These can also be overridden per post/page.', 'block-content-protection' ) . '</p>';
        },
        'block_content_protection'
    );
    $content_type_desc = [
        'protect_images'  => __( 'Applies drag and right-click protection to images.', 'block-content-protection' ),
        'protect_text'    => __( 'Applies text-selection protection to page content.', 'block-content-protection' ),
        'protect_videos'  => __( 'Enables video download blocking, watermarking, and screen-recording detection.', 'block-content-protection' ),
        'protect_code'    => __( 'Prevents selecting, copying, and right-clicking code blocks (<pre>, <code>).', 'block-content-protection' ),
        'protect_banners' => __( 'Prevents dragging, selecting, and right-clicking elements matching the CSS selector below (e.g. logo, banners).', 'block-content-protection' ),
    ];
    foreach ( bcp_get_content_types() as $type => $label ) {
        $id = 'protect_' . $type;
        add_settings_field( $id, sprintf( __( 'Protect %s', 'block-content-protection' ), $label ), 'bcp_render_checkbox_field', 'block_content_protection', 'bcp_content_types_section', [ 'id' => $id, 'description' => $content_type_desc[ $id ] ] );
    }
    add_settings_field( 'banner_selector', __( 'Banner / Logo CSS Selector', 'block-content-protection' ), 'bcp_render_textfield_field', 'block_content_protection', 'bcp_content_types_section', [ 'id' => 'banner_selector', 'description' => __( 'Comma-separated CSS selectors for banner/logo elements, e.g. .site-logo, .banner', 'block-content-protection' ) ] );

    // Page Selection Section (order matters: mode, excluded, included - admin JS relies on it)
    add_settings_section(
        'bcp_page_selection_section',
        __( 'Page Selection', 'block-content-protection' ),
        function () {
            echo '<p class="description">' . esc_html__( 'Choose which posts and pages protection applies to.', 'block-content-protection' ) . '</p>';
        },
        'block_content_protection'
    );
    add_settings_field( 'page_selection_mode', __( 'Apply Protection To', 'block-content-protection' ), 'bcp_render_select_field', 'block_content_protection', 'bcp_page_selection_section', [
        'id'          => 'page_selection_mode',
        'description' => __( 'Protect everything (with exclusions) or only specific posts/pages.', 'block-content-protection' ),
        'options'     => [
            'all'      => __( 'All posts & pages (except excluded)', 'block-content-protection' ),
            'selected' => __( 'Only selected posts & pages', 'block-content-protection' ),
        ],
    ] );
    add_settings_field( 'excluded_pages', __( 'Excluded Posts/Pages', 'block-content-protection' ), 'bcp_render_post_selector_field', 'block_content_protection', 'bcp_page_selection_section', [ 'id' => 'excluded_pages', 'description' => __( 'These posts/pages will never be protected.', 'block-content-protection' ) ] );
    add_settings_field( 'included_pages', __( 'Included Posts/Pages', 'block-content-protection' ), 'bcp_render_post_selector_field', 'block_content_protection', 'bcp_page_selection_section', [ 'id' => 'included_pages', 'description' => __( 'Only these posts/pages will be protected.', 'block-content-protection' ) ] );

    // Exclusions Section (IP whitelist)
    add_settings_section( 'bcp_exclusions_section', __( 'IP Exclusions', 'block-content-protection' ), null, 'block_content_protection' );
    add_settings_field( 'whitelisted_ips', __( 'Whitelisted IP Addresses', 'block-content-protection' ), 'bcp_render_textarea_field', 'block_content_protection', 'bcp_exclusions_section', [ 'id' => 'whitelisted_ips', 'description' => __( 'Enter one IP address per line. These IPs will not be affected by the protection.', 'block-content-protection' ) ] );

    // Messages Section
    add_settings_section( 'bcp_messages_section', null, null, 'block_content_protection' );
    add_settings_field( 'enable_custom_messages', __( 'Enable Custom Messages', 'block-content-protection' ), 'bcp_render_checkbox_field', 'block_content_protection', 'bcp_messages_section', [ 'id' => 'enable_custom_messages', 'description' => __( 'Enable to override the default browser alerts with your own messages.', 'block-content-protection' ) ] );
    add_settings_field( 'screenshot_alert_message', __( 'Screenshot Alert Message', 'block-content-protection' ), 'bcp_render_textfield_field', 'block_content_protection', 'bcp_messages_section', [ 'id' => 'screenshot_alert_message', 'description' => __( 'The message shown when a user tries to take a screenshot.', 'block-content-protection' ), 'class' => 'bcp-message-field' ] );
    add_settings_field( 'recording_alert_message', __( 'Screen Recording Alert', 'block-content-protection' ), 'bcp_render_textfield_field', 'block_content_protection', 'bcp_messages_section', [ 'id' => 'recording_alert_message', 'description' => __( 'Message shown when screen recording is detected.', 'block-content-protection' ), 'class' => 'bcp-message-field' ] );

    // Watermark Section
    add_settings_section( 'bcp_watermark_section', null, null, 'block_content_protection' );
    add_settings_field( 'enable_video_watermark', __( 'Enable Video Watermark', 'block-content-protection' ), 'bcp_render_checkbox_field', 'block_content_protection', 'bcp_watermark_section', [ 'id' => 'enable_video_watermark', 'description' => __( 'Enable this to show a dynamic watermark over videos.', 'block-content-protection' ) ] );
    add_settings_field( 'watermark_text', __( 'Watermark Text', 'block-content-protection' ), 'bcp_render_textfield_field', 'block_content_protection', 'bcp_watermark_section', [ 'id' => 'watermark_text', 'description' => __( 'Enter text for the watermark. Use placeholders: {user_login}, {user_email}, {user_mobile}, {ip_address}, {date}.', 'block-content-protection' ) ] );
    add_settings_field( 'watermark_opacity', __( 'Watermark Opacity', 'block-content-protection' ), 'bcp_render_number_field', 'block_content_protection', 'bcp_watermark_section', [ 'id' => 'watermark_opacity', 'description' => __( 'Set the opacity from 0 (transparent) to 1 (opaque). Default: 0.5', 'block-content-protection' ), 'min' => 0, 'max' => 1, 'step' => '0.1' ] );
    add_settings_field( 'watermark_position', __( 'Watermark Position', 'block-content-protection' ), 'bcp_render_select_field', 'block_content_protection', 'bcp_watermark_section', [ 'id' => 'watermark_position', 'description' => __( 'Select the watermark position.', 'block-content-protection' ), 'options' => [ 'animated' => 'Animated', 'top_left' => 'Top Left', 'top_right' => 'Top Right', 'bottom_left' => 'Bottom Left', 'bottom_right' => 'Bottom Right', ] ] );
    add_settings_field( 'watermark_style', __( 'Watermark Style', 'block-content-protection' ), 'bcp_render_select_field', 'block_content_protection', 'bcp_watermark_section', [ 'id' => 'watermark_style', 'description' => __( 'Select the watermark style.', 'block-content-protection' ), 'options' => [ 'text' => 'Simple Text', 'pattern' => 'Pattern' ] ] );
}
add_action( 'admin_init', 'bcp_register_settings' );

function bcp_render_select_field( $args ) {
    $options = get_option( 'bcp_options', [] );
    $id = $args['id'];
    $value = isset( $options[$id] ) ? esc_attr( $options[$id] ) : '';
    echo "<select id='$id' name='bcp_options[$id]'>";
    foreach ( $args['options'] as $val => $label ) {
        echo "<option value='" . esc_attr( $val ) . "' " . selected( $value, $val, false ) . ">" . esc_html( $label ) . "</option>";
    }
    echo "</select>";
    if ( ! empty( $args['description'] ) ) {
        echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
    }
}

function bcp_render_number_field( $args ) {
    $options = get_option( 'bcp_options', [] );
    $id = $args['id'];
    $value = isset( $options[$id] ) ? esc_attr( $options[$id] ) : '';
    $min = isset( $args['min'] ) ? $args['min'] : '';
    $max = isset( $args['max'] ) ? $args['max'] : '';
    $step = isset( $args['step'] ) ? $args['step'] : '';
    echo "<input type='number' id='$id' name='bcp_options[$id]' value='$value' class='regular-text' min='$min' max='$max' step='$step' />";
    if ( ! empty( $args['description'] ) ) {
        echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
    }
}

function bcp_render_checkbox_field( $args ) {
    $options = get_option( 'bcp_options', [] );
    $id = $args['id'];
    $checked = isset( $options[$id] ) ? checked( $options[$id], 1, false ) : '';
    echo "<label for='$id'>";
    echo "<input type='checkbox' id='$id' name='bcp_options[$id]' value='1' $checked />";
    echo '<span class="bcp-switch"></span>'; // This is the toggle switch
    if ( ! empty( $args['description'] ) ) {
        echo '<span class="bcp-field-description">' . esc_html( $args['description'] ) . '</span>';
    }
    echo "</label>";
}

function bcp_render_textarea_field( $args ) {
    $options = get_option( 'bcp_options', [] );
    $id = $args['id'];
    $value = isset( $options[$id] ) ? esc_textarea( $options[$id] ) : '';
    echo "<textarea id='$id' name='bcp_options[$id]' rows='5' cols='50' class='large-text code'>$value</textarea>";
    if ( ! empty( $args['description'] ) ) {
        echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
    }
}

function bcp_render_textfield_field( $args ) {
    $options = get_option( 'bcp_options', [] );
    $id = $args['id'];
    $value = isset( $options[$id] ) ? esc_attr( $options[$id] ) : '';
    echo "<input type='text' id='$id' name='bcp_options[$id]' value='$value' class='regular-text' />";
    if ( ! empty( $args['description'] ) ) {
        echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
    }
}

/**
 * Renders an AJAX-powered post/page multi-select ("chips") field.
 * Stores an array of post IDs at bcp_options[$id][].
 */
function bcp_render_post_selector_field( $args ) {
    $options = get_option( 'bcp_options', [] );
    $id = $args['id'];
    $ids = isset( $options[ $id ] ) && is_array( $options[ $id ] ) ? $options[ $id ] : [];

    echo '<div class="bcp-post-selector" data-field-name="' . esc_attr( "bcp_options[{$id}][]" ) . '">';
    echo '<div class="bcp-post-selector-chips">';
    foreach ( $ids as $post_id ) {
        $post_id = (int) $post_id;
        $title   = get_the_title( $post_id );
        if ( '' === $title ) {
            continue;
        }
        echo '<span class="bcp-chip" data-id="' . esc_attr( $post_id ) . '">'
            . esc_html( $title )
            . ' <a href="#" class="bcp-chip-remove" aria-label="' . esc_attr__( 'Remove', 'block-content-protection' ) . '">&times;</a>'
            . '<input type="hidden" name="' . esc_attr( "bcp_options[{$id}][]" ) . '" value="' . esc_attr( $post_id ) . '" />'
            . '</span>';
    }
    echo '</div>';
    echo '<input type="text" class="regular-text bcp-post-selector-input" placeholder="' . esc_attr__( 'Type to search posts and pages…', 'block-content-protection' ) . '" autocomplete="off" />';
    echo '<div class="bcp-post-selector-results"></div>';
    echo '</div>';

    if ( ! empty( $args['description'] ) ) {
        echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
    }
}

/**
 * Allow-list based CSS selector sanitizer. Strips anything that isn't a
 * valid CSS selector character so the value can't be used to inject markup
 * or scripts into the settings page or the front-end JSON payload.
 */
function bcp_sanitize_css_selector( $selector ) {
    $selector = wp_strip_all_tags( (string) $selector );
    $selector = preg_replace( '/[^a-zA-Z0-9\s\.\#\,\>\~\+\:\(\)\[\]\=\"\'\-\_\*\^\$\|]/', '', $selector );
    return trim( (string) $selector );
}

function bcp_sanitize_options( $input ) {
    $input    = is_array( $input ) ? $input : [];
    $defaults = bcp_get_default_options();
    $new_options = [];

    $checkboxes = [
        'disable_right_click', 'disable_devtools', 'disable_copy',
        'disable_text_selection', 'disable_image_drag', 'disable_video_download',
        'disable_screenshot', 'enhanced_protection', 'mobile_screenshot_block',
        'video_screen_record_block', 'enable_video_watermark', 'enable_custom_messages',
        'protect_images', 'protect_text', 'protect_videos', 'protect_code', 'protect_banners',
    ];
    foreach ( $checkboxes as $field ) {
        $new_options[ $field ] = ! empty( $input[ $field ] ) ? 1 : 0;
    }

    $new_options['page_selection_mode'] = ( isset( $input['page_selection_mode'] ) && 'selected' === $input['page_selection_mode'] ) ? 'selected' : 'all';

    foreach ( [ 'excluded_pages', 'included_pages' ] as $field ) {
        $ids = isset( $input[ $field ] ) && is_array( $input[ $field ] ) ? $input[ $field ] : [];
        $new_options[ $field ] = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
    }

    // Sanitize text and textarea fields.
    if ( isset( $input['whitelisted_ips'] ) ) {
        $ips = array_map( 'trim', explode( "\n", (string) $input['whitelisted_ips'] ) );
        $ips = array_filter( $ips, function ( $ip ) {
            return '' === $ip || false !== filter_var( $ip, FILTER_VALIDATE_IP );
        } );
        $new_options['whitelisted_ips'] = implode( "\n", array_map( 'sanitize_text_field', $ips ) );
    }
    foreach ( [ 'screenshot_alert_message', 'recording_alert_message', 'watermark_text' ] as $field ) {
        if ( isset( $input[ $field ] ) ) {
            $new_options[ $field ] = sanitize_text_field( $input[ $field ] );
        }
    }

    if ( isset( $input['banner_selector'] ) ) {
        $new_options['banner_selector'] = bcp_sanitize_css_selector( $input['banner_selector'] );
    }
    if ( empty( $new_options['banner_selector'] ) ) {
        $new_options['banner_selector'] = $defaults['banner_selector'];
    }

    // Sanitize number and select fields.
    if ( isset( $input['watermark_opacity'] ) ) {
        $new_options['watermark_opacity'] = max( 0, min( 1, floatval( $input['watermark_opacity'] ) ) );
    }
    if ( isset( $input['watermark_position'] ) ) {
        $allowed_positions = [ 'animated', 'top_left', 'top_right', 'bottom_left', 'bottom_right' ];
        $pos = sanitize_key( $input['watermark_position'] );
        $new_options['watermark_position'] = in_array( $pos, $allowed_positions, true ) ? $pos : 'animated';
    }
    if ( isset( $input['watermark_style'] ) ) {
        $allowed_styles = [ 'text', 'pattern' ];
        $style = sanitize_key( $input['watermark_style'] );
        $new_options['watermark_style'] = in_array( $style, $allowed_styles, true ) ? $style : 'text';
    }

    return wp_parse_args( $new_options, $defaults );
}

function bcp_options_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $plugin_data = get_plugin_data( __FILE__ );
    ?>
    <div class="wrap bcp-wrap">
        <div class="bcp-header">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        </div>

        <div class="notice notice-warning">
            <p><strong>⚠️ <?php _e( 'Attention:', 'block-content-protection' ); ?></strong> <?php _e( 'Completely preventing screenshots and screen recording is impossible. These tools only make it more difficult, not impossible.', 'block-content-protection' ); ?></p>
        </div>

        <form action="options.php" method="post">
            <?php settings_fields( 'bcp_settings_group' ); ?>
            <div class="bcp-content">
                <div class="bcp-main">
                    <!-- Protection Settings Card -->
                    <div class="bcp-card">
                        <h2 class="bcp-card-header"><?php _e( 'Protection Settings', 'block-content-protection' ); ?></h2>
                        <div class="bcp-card-body">
                            <table class="form-table">
                                <?php do_settings_fields( 'block_content_protection', 'bcp_protection_section' ); ?>
                            </table>
                        </div>
                    </div>

                    <!-- Content Type Protection Card -->
                    <div class="bcp-card">
                        <h2 class="bcp-card-header"><?php _e( 'Content Type Protection', 'block-content-protection' ); ?></h2>
                        <div class="bcp-card-body">
                            <table class="form-table">
                                <?php do_settings_fields( 'block_content_protection', 'bcp_content_types_section' ); ?>
                            </table>
                        </div>
                    </div>

                    <!-- Watermark Settings Card -->
                    <div class="bcp-card">
                        <h2 class="bcp-card-header"><?php _e( 'Watermark Settings', 'block-content-protection' ); ?></h2>
                        <div class="bcp-card-body">
                            <table class="form-table">
                                <?php do_settings_fields( 'block_content_protection', 'bcp_watermark_section' ); ?>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="bcp-sidebar">
                    <!-- Page Selection Card -->
                    <div class="bcp-card">
                        <h2 class="bcp-card-header"><?php _e( 'Page Selection', 'block-content-protection' ); ?></h2>
                        <div class="bcp-card-body">
                            <table class="form-table">
                                <?php do_settings_fields( 'block_content_protection', 'bcp_page_selection_section' ); ?>
                            </table>
                        </div>
                    </div>

                    <!-- IP Exclusion Settings Card -->
                    <div class="bcp-card">
                        <h2 class="bcp-card-header"><?php _e( 'IP Exclusions', 'block-content-protection' ); ?></h2>
                        <div class="bcp-card-body">
                            <table class="form-table">
                                <?php do_settings_fields( 'block_content_protection', 'bcp_exclusions_section' ); ?>
                            </table>
                        </div>
                    </div>

                    <!-- Messages Card -->
                    <div class="bcp-card">
                        <h2 class="bcp-card-header"><?php _e( 'Custom Messages', 'block-content-protection' ); ?></h2>
                        <div class="bcp-card-body">
                            <table class="form-table">
                                <?php do_settings_fields( 'block_content_protection', 'bcp_messages_section' ); ?>
                            </table>
                        </div>
                    </div>
                     <div class="bcp-card">
                        <div class="bcp-card-body">
                             <?php submit_button( __( 'Save Settings', 'block-content-protection' ) ); ?>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="bcp-footer">
            <p>
                <?php
                $allowed_html = [
                    'a' => [
                        'href'   => [],
                        'target' => ['_blank'],
                    ],
                ];
                $footer_text = sprintf(
                    /* translators: 1: Plugin Name, 2: Plugin Version, 3: Base developer link. */
                    __( 'Thank you for using %1$s! Version %2$s. Base app developed by %3$s.', 'block-content-protection' ),
                    esc_html( $plugin_data['Name'] ),
                    esc_html( $plugin_data['Version'] ),
                    '<a href="' . esc_url( $plugin_data['AuthorURI'] ) . '" target="_blank">' . esc_html__( 'Mohammad Babaei - Adschi', 'block-content-protection' ) . '</a>'
                );
                echo wp_kses( $footer_text, $allowed_html );
                ?>
            </p>
            <p class="bcp-footer-credit">
                <?php
                echo esc_html__( 'Extension & development by A. Babaei.', 'block-content-protection' );
                ?>
            </p>
        </div>
    </div>
    <?php
}

/* -----------------------------------------------------------------------
 * Per-post protection override (meta box).
 * --------------------------------------------------------------------- */

function bcp_add_meta_boxes() {
    $post_types = get_post_types( [ 'public' => true ], 'names' );
    unset( $post_types['attachment'] );
    foreach ( $post_types as $post_type ) {
        add_meta_box(
            'bcp_meta_box',
            __( 'Content Protection', 'block-content-protection' ),
            'bcp_render_meta_box',
            $post_type,
            'side',
            'default'
        );
    }
}
add_action( 'add_meta_boxes', 'bcp_add_meta_boxes' );

function bcp_render_meta_box( $post ) {
    wp_nonce_field( 'bcp_save_meta_box', 'bcp_meta_box_nonce' );

    $override = get_post_meta( $post->ID, '_bcp_override', true );
    if ( ! in_array( $override, [ 'enable', 'disable' ], true ) ) {
        $override = '';
    }

    $content_types = get_post_meta( $post->ID, '_bcp_content_types', true );
    $content_types = is_array( $content_types ) ? $content_types : [];
    if ( empty( $content_types ) ) {
        $options = get_option( 'bcp_options', [] );
        foreach ( array_keys( bcp_get_content_types() ) as $type ) {
            if ( ! empty( $options[ 'protect_' . $type ] ) ) {
                $content_types[] = $type;
            }
        }
    }
    ?>
    <p class="bcp-meta-row">
        <label class="bcp-meta-radio">
            <input type="radio" name="bcp_override" value="" <?php checked( $override, '' ); ?> />
            <?php esc_html_e( 'Use global settings', 'block-content-protection' ); ?>
        </label>
        <label class="bcp-meta-radio">
            <input type="radio" name="bcp_override" value="enable" <?php checked( $override, 'enable' ); ?> />
            <?php esc_html_e( 'Enable protection on this page', 'block-content-protection' ); ?>
        </label>
        <label class="bcp-meta-radio">
            <input type="radio" name="bcp_override" value="disable" <?php checked( $override, 'disable' ); ?> />
            <?php esc_html_e( 'Disable protection on this page', 'block-content-protection' ); ?>
        </label>
    </p>
    <div class="bcp-meta-content-types<?php echo 'enable' === $override ? '' : ' bcp-hidden'; ?>">
        <p><strong><?php esc_html_e( 'Content types to protect on this page:', 'block-content-protection' ); ?></strong></p>
        <?php foreach ( bcp_get_content_types() as $type => $label ) : ?>
            <label class="bcp-meta-checkbox">
                <input type="checkbox" name="bcp_content_types[]" value="<?php echo esc_attr( $type ); ?>" <?php checked( in_array( $type, $content_types, true ) ); ?> />
                <?php echo esc_html( $label ); ?>
            </label>
        <?php endforeach; ?>
    </div>
    <?php
}

function bcp_save_meta_box( $post_id ) {
    if ( ! isset( $_POST['bcp_meta_box_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['bcp_meta_box_nonce'] ), 'bcp_save_meta_box' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $override = isset( $_POST['bcp_override'] ) ? sanitize_key( wp_unslash( $_POST['bcp_override'] ) ) : '';
    if ( ! in_array( $override, [ 'enable', 'disable' ], true ) ) {
        $override = '';
    }
    if ( '' === $override ) {
        delete_post_meta( $post_id, '_bcp_override' );
    } else {
        update_post_meta( $post_id, '_bcp_override', $override );
    }

    $allowed_types = array_keys( bcp_get_content_types() );
    $submitted     = isset( $_POST['bcp_content_types'] ) && is_array( $_POST['bcp_content_types'] ) ? wp_unslash( $_POST['bcp_content_types'] ) : [];
    $content_types = array_values( array_intersect( $allowed_types, array_map( 'sanitize_key', $submitted ) ) );
    if ( empty( $content_types ) ) {
        delete_post_meta( $post_id, '_bcp_content_types' );
    } else {
        update_post_meta( $post_id, '_bcp_content_types', $content_types );
    }
}
add_action( 'save_post', 'bcp_save_meta_box' );

/* -----------------------------------------------------------------------
 * Admin AJAX: post/page search used by the settings page selector.
 * --------------------------------------------------------------------- */

function bcp_ajax_search_posts() {
    check_ajax_referer( 'bcp_admin_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [], 403 );
    }

    $search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

    $query = new WP_Query( [
        'post_type'      => [ 'post', 'page' ],
        'post_status'    => 'publish',
        's'              => $search,
        'posts_per_page' => 20,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'no_found_rows'  => true,
        'ignore_sticky_posts' => true,
    ] );

    $results = [];
    foreach ( $query->posts as $post ) {
        $post_type_obj = get_post_type_object( $post->post_type );
        $results[]     = [
            'id'    => $post->ID,
            'title' => html_entity_decode( get_the_title( $post ), ENT_QUOTES ),
            'type'  => $post_type_obj ? $post_type_obj->labels->singular_name : $post->post_type,
        ];
    }

    wp_send_json_success( $results );
}
add_action( 'wp_ajax_bcp_search_posts', 'bcp_ajax_search_posts' );

function bcp_get_user_ip() {
    $ip_keys = [ 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR' ];
    foreach ( $ip_keys as $key ) {
        if ( array_key_exists( $key, $_SERVER ) === true ) {
            foreach ( explode( ',', $_SERVER[ $key ] ) as $ip ) {
                $ip = trim( $ip );
                if ( filter_var( $ip, FILTER_VALIDATE_IP ) !== false ) {
                    return $ip;
                }
            }
        }
    }
    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
}

/**
 * Resolves whether protection is active for the current front-end request,
 * taking IP whitelist, page selection mode, and any per-post override into
 * account, and (when active) which content types apply. Cached per-request.
 *
 * @return array{active: bool, options: array, content_types: array<string,bool>}
 */
function bcp_get_page_protection_context() {
    static $context = null;
    if ( null !== $context ) {
        return $context;
    }

    $options = wp_parse_args( get_option( 'bcp_options', [] ), bcp_get_default_options() );
    $all_types = array_keys( bcp_get_content_types() );

    $context = [
        'active'        => false,
        'options'       => $options,
        'content_types' => array_fill_keys( $all_types, false ),
    ];

    if ( ! empty( $options['whitelisted_ips'] ) ) {
        $whitelisted_ips = array_filter( array_map( 'trim', explode( "\n", $options['whitelisted_ips'] ) ) );
        if ( in_array( bcp_get_user_ip(), $whitelisted_ips, true ) ) {
            return $context;
        }
    }

    $post_id  = is_singular() ? get_queried_object_id() : 0;
    $override = $post_id ? get_post_meta( $post_id, '_bcp_override', true ) : '';
    if ( ! in_array( $override, [ 'enable', 'disable' ], true ) ) {
        $override = '';
    }

    if ( 'disable' === $override ) {
        return $context;
    }

    if ( '' === $override ) {
        $excluded_pages = array_map( 'intval', (array) $options['excluded_pages'] );
        $included_pages = array_map( 'intval', (array) $options['included_pages'] );

        if ( 'selected' === $options['page_selection_mode'] ) {
            if ( ! $post_id || ! in_array( $post_id, $included_pages, true ) ) {
                return $context;
            }
        } elseif ( $post_id && in_array( $post_id, $excluded_pages, true ) ) {
            return $context;
        }
    }

    if ( 'enable' === $override ) {
        $selected_types = get_post_meta( $post_id, '_bcp_content_types', true );
        $selected_types = is_array( $selected_types ) && ! empty( $selected_types ) ? $selected_types : $all_types;
    } else {
        $selected_types = array_filter( $all_types, function ( $type ) use ( $options ) {
            return ! empty( $options[ 'protect_' . $type ] );
        } );
    }

    foreach ( $selected_types as $type ) {
        if ( array_key_exists( $type, $context['content_types'] ) ) {
            $context['content_types'][ $type ] = true;
        }
    }
    $context['active'] = true;

    return $context;
}

function bcp_add_meta_tags() {
    $context = bcp_get_page_protection_context();
    if ( ! $context['active'] || empty( $context['options']['mobile_screenshot_block'] ) ) {
        return;
    }
    echo '<meta name="flags" content="FLAG_SECURE">' . "\n";
    echo '<meta name="mobile-web-app-capable" content="yes">' . "\n";
}
add_action( 'wp_head', 'bcp_add_meta_tags' );

function bcp_enqueue_scripts() {
    $context = bcp_get_page_protection_context();
    if ( ! $context['active'] ) {
        return;
    }

    $options       = $context['options'];
    $content_types = $context['content_types'];

    $protection_options = [ 'disable_right_click', 'disable_devtools', 'disable_copy', 'disable_text_selection', 'disable_image_drag', 'disable_screenshot', 'enhanced_protection', 'mobile_screenshot_block', 'video_screen_record_block' ];
    $is_feature_enabled = false;
    foreach ( $protection_options as $key ) {
        if ( ! empty( $options[ $key ] ) ) {
            $is_feature_enabled = true;
            break;
        }
    }
    $has_content_type = in_array( true, $content_types, true );
    $show_watermark   = $content_types['videos'] && ! empty( $options['enable_video_watermark'] );

    if ( ! $is_feature_enabled && ! $has_content_type && ! $show_watermark ) {
        return;
    }

    // Replace watermark placeholders
    if ( $show_watermark && ! empty( $options['watermark_text'] ) ) {
        $current_user = wp_get_current_user();
        $ip_address = bcp_get_user_ip();
        $date = date( get_option( 'date_format' ) );
        $user_mobile = $current_user->user_login; // Fallback to username

        // Check for Digits plugin mobile number
        if ( function_exists( 'get_user_meta' ) && $current_user->ID ) {
            $digits_mobile = get_user_meta( $current_user->ID, 'digits_phone', true );
            if ( ! empty( $digits_mobile ) ) {
                $user_mobile = $digits_mobile;
            }
        }

        $replacements = [
            '{user_login}'  => $current_user->user_login,
            '{user_email}'  => $current_user->user_email,
            '{user_mobile}' => $user_mobile,
            '{ip_address}'  => $ip_address,
            '{date}'        => $date,
        ];

        $options['watermark_text'] = str_replace( array_keys( $replacements ), array_values( $replacements ), $options['watermark_text'] );
    }

    $options['content_types']   = $content_types;
    $options['banner_selector'] = ! empty( $options['banner_selector'] ) ? $options['banner_selector'] : bcp_get_default_options()['banner_selector'];

    // Enqueue the module script
    wp_enqueue_script( 'bcp-protect-module', BCP_PLUGIN_URL . 'js/protect.module.js', [], BCP_VERSION, true );

    // Create a data bridge for the module
    add_action('wp_footer', function() use ($options) {
        echo '<script type="application/json" id="bcp-settings-data">' . wp_json_encode($options) . '</script>';
    }, 99);

    // Enqueue styles if needed
    if ( ! empty( $options['enhanced_protection'] ) || ! empty( $options['video_screen_record_block'] ) || $show_watermark || $content_types['code'] || $content_types['banners'] ) {
        wp_enqueue_style( 'bcp-protect-css', BCP_PLUGIN_URL . 'css/protect.css', [], BCP_VERSION );
    }
}
add_action( 'wp_enqueue_scripts', 'bcp_enqueue_scripts' );

function bcp_add_module_to_script( $tag, $handle, $src ) {
    if ( 'bcp-protect-module' === $handle ) {
        // Since the module is self-executing, we just need to add type="module"
        $tag = '<script type="module" src="' . esc_url( $src ) . '" id="' . esc_attr( $handle ) . '-js"></script>';
    }
    return $tag;
}
add_filter( 'script_loader_tag', 'bcp_add_module_to_script', 10, 3 );

function bcp_enqueue_admin_scripts( $hook ) {
    $is_settings_page = 'toplevel_page_block_content_protection' === $hook;
    $is_post_editor   = in_array( $hook, [ 'post.php', 'post-new.php' ], true );

    if ( ! $is_settings_page && ! $is_post_editor ) {
        return;
    }

    wp_enqueue_style( 'bcp-admin-styles', BCP_PLUGIN_URL . 'admin/css/admin-styles.css', [], BCP_VERSION );
    wp_enqueue_script( 'bcp-admin-scripts', BCP_PLUGIN_URL . 'admin/js/admin-scripts.js', [], BCP_VERSION, true );
    wp_localize_script( 'bcp-admin-scripts', 'bcpAdmin', [
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'bcp_admin_nonce' ),
    ] );
}
add_action( 'admin_enqueue_scripts', 'bcp_enqueue_admin_scripts' );

function bcp_activation() {
    $existing = get_option( 'bcp_options' );
    if ( false === $existing ) {
        update_option( 'bcp_options', bcp_get_default_options() );
    } else {
        update_option( 'bcp_options', wp_parse_args( $existing, bcp_get_default_options() ) );
    }
    update_option( 'bcp_db_version', BCP_DB_VERSION );
}
register_activation_hook( __FILE__, 'bcp_activation' );

/**
 * Non-destructively merges in new default options and migrates legacy data
 * formats for sites that update the plugin files without deactivating first
 * (activation hooks only fire on activate, not on a simple file replace).
 */
function bcp_maybe_upgrade() {
    $db_version = get_option( 'bcp_db_version', '1' );
    if ( version_compare( (string) $db_version, BCP_DB_VERSION, '>=' ) ) {
        return;
    }

    $options = get_option( 'bcp_options', [] );
    if ( ! is_array( $options ) ) {
        $options = [];
    }

    // Legacy `excluded_pages` was a comma-separated string of IDs.
    if ( isset( $options['excluded_pages'] ) && is_string( $options['excluded_pages'] ) ) {
        $options['excluded_pages'] = array_values( array_filter( array_map( 'intval', array_map( 'trim', explode( ',', $options['excluded_pages'] ) ) ) ) );
    }

    $options = wp_parse_args( $options, bcp_get_default_options() );
    update_option( 'bcp_options', $options );
    update_option( 'bcp_db_version', BCP_DB_VERSION );
}
add_action( 'plugins_loaded', 'bcp_maybe_upgrade' );
