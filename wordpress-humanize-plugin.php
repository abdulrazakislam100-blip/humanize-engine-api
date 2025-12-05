<?php
/**
 * Plugin Name: Humanize Engine
 * Description: Automatically humanize AI-generated content using the Humanize Engine API
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL2
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// API Configuration
define('HUMANIZE_API_URL', 'https://humanize-engine-api.fly.dev/humanize');
define('HUMANIZE_API_TIMEOUT', 30);

/**
 * Humanize content by sending it to the API
 */
function humanize_engine_humanize_text($text) {
    if (empty($text)) {
        return $text;
    }

    // Skip if text is too short
    if (strlen($text) < 50) {
        return $text;
    }

    // Make API request
    $response = wp_remote_post(HUMANIZE_API_URL, array(
        'method' => 'POST',
        'timeout' => HUMANIZE_API_TIMEOUT,
        'sslverify' => true,
        'headers' => array(
            'Content-Type' => 'application/json',
        ),
        'body' => wp_json_encode(array(
            'text' => $text,
        )),
    ));

    // Handle errors
    if (is_wp_error($response)) {
        error_log('Humanize Engine API Error: ' . $response->get_error_message());
        return $text; // Return original text if API fails
    }

    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code !== 200) {
        error_log('Humanize Engine API returned status code: ' . $status_code);
        return $text;
    }

    // Parse response
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (isset($data['humanized_text']) && !empty($data['humanized_text'])) {
        return $data['humanized_text'];
    }

    return $text; // Return original if humanization fails
}

/**
 * Apply humanization to post content
 */
function humanize_engine_process_post_content($content) {
    // Only process if not in admin
    if (is_admin()) {
        return $content;
    }

    // Don't process if user has disabled it
    $enabled = get_option('humanize_engine_enabled', true);
    if (!$enabled) {
        return $content;
    }

    return humanize_engine_humanize_text($content);
}

// Hook into the_content filter
add_filter('the_content', 'humanize_engine_process_post_content', 99);

/**
 * Add admin settings page
 */
function humanize_engine_add_admin_menu() {
    add_options_page(
        'Humanize Engine Settings',
        'Humanize Engine',
        'manage_options',
        'humanize-engine',
        'humanize_engine_options_page'
    );
}

add_action('admin_menu', 'humanize_engine_add_admin_menu');

/**
 * Admin settings page HTML
 */
function humanize_engine_options_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Save settings
    if (isset($_POST['humanize_engine_nonce'])) {
        if (wp_verify_nonce($_POST['humanize_engine_nonce'], 'humanize_engine_nonce')) {
            $enabled = isset($_POST['humanize_engine_enabled']) ? 1 : 0;
            update_option('humanize_engine_enabled', $enabled);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
    }

    $enabled = get_option('humanize_engine_enabled', true);
    ?>
    <div class="wrap">
        <h1>Humanize Engine Settings</h1>
        <form method="post">
            <?php wp_nonce_field('humanize_engine_nonce', 'humanize_engine_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">Enable Humanization</th>
                    <td>
                        <input type="checkbox" name="humanize_engine_enabled" value="1" <?php checked($enabled, 1); ?> />
                        <p class="description">Enable automatic humanization of post content</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">API Endpoint</th>
                    <td>
                        <code><?php echo esc_html(HUMANIZE_API_URL); ?></code>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

/**
 * Admin settings initialization
 */
function humanize_engine_settings_init() {
    register_setting('humanize_engine', 'humanize_engine_enabled');
}

add_action('admin_init', 'humanize_engine_settings_init');

// Activation hook
function humanize_engine_activate() {
    add_option('humanize_engine_enabled', true);
}

register_activation_hook(__FILE__, 'humanize_engine_activate');

// Deactivation hook
function humanize_engine_deactivate() {
    delete_option('humanize_engine_enabled');
}

register_deactivation_hook(__FILE__, 'humanize_engine_deactivate');
?>
