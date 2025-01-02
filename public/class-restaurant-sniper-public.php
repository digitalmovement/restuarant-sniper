<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://ianbryce.com
 * @since      1.0.0
 *
 * @package    Restaurant_Sniper
 * @subpackage Restaurant_Sniper/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Restaurant_Sniper
 * @subpackage Restaurant_Sniper/public
 * @author     Ian Bryce <ian@digitalmovement.co.uk>
 */

class Restaurant_Sniper_Public {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        add_shortcode('restaurant_monitor', array($this, 'display_monitor_list'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_add_restaurant_monitor', array($this, 'add_restaurant_monitor'));
        add_action('wp_ajax_delete_restaurant_monitor', array($this, 'delete_restaurant_monitor'));
        add_action('wp_ajax_update_restaurant_monitor', array($this, 'update_restaurant_monitor'));
    }

    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/restaurant-sniper-public.css', array(), $this->version, 'all');
    }

    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/restaurant-sniper-public.js', array('jquery'), $this->version, false);
        wp_localize_script($this->plugin_name, 'restaurantSniper', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('restaurant_monitor_nonce')
        ));
    }

    public function display_monitor_list() {
        if (!is_user_logged_in()) {
            return '<p>Please log in to view your restaurant monitors.</p>';
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'restaurant_monitors';
        $user_id = get_current_user_id();
        $monitors = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC",
            $user_id
        ));

        ob_start();
        include plugin_dir_path(__FILE__) . 'partials/restaurant-sniper-public-display.php';
        return ob_get_clean();
    }

    public function add_restaurant_monitor() {
        check_ajax_referer('restaurant_monitor_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
            return;
        }

        $url = sanitize_url($_POST['restaurant_url']);
        $date = sanitize_text_field($_POST['reservation_date']);
        $time = sanitize_text_field($_POST['reservation_time']);
        $party_size = intval($_POST['party_size']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'restaurant_monitors';
        
        $result = $wpdb->insert($table_name, array(
            'user_id' => get_current_user_id(),
            'restaurant_url' => $url,
            'reservation_date' => $date,
            'reservation_time' => $time,
            'party_size' => $party_size
        ));

        if ($result) {
            wp_send_json_success('Monitor added successfully');
        } else {
            wp_send_json_error('Failed to add monitor');
        }
    }

    public function delete_restaurant_monitor() {
        check_ajax_referer('restaurant_monitor_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
            return;
        }

        $monitor_id = intval($_POST['monitor_id']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'restaurant_monitors';
        
        $result = $wpdb->delete($table_name, array(
            'id' => $monitor_id,
            'user_id' => get_current_user_id()
        ));

        if ($result) {
            wp_send_json_success('Monitor deleted successfully');
        } else {
            wp_send_json_error('Failed to delete monitor');
        }
    }

    public function update_restaurant_monitor() {
        check_ajax_referer('restaurant_monitor_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
            return;
        }

        $monitor_id = intval($_POST['monitor_id']);
        $url = sanitize_url($_POST['restaurant_url']);
        $date = sanitize_text_field($_POST['reservation_date']);
        $time = sanitize_text_field($_POST['reservation_time']);
        $party_size = intval($_POST['party_size']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'restaurant_monitors';
        
        $result = $wpdb->update($table_name, 
            array(
                'restaurant_url' => $url,
                'reservation_date' => $date,
                'reservation_time' => $time,
                'party_size' => $party_size
            ),
            array(
                'id' => $monitor_id,
                'user_id' => get_current_user_id()
            )
        );

        if ($result !== false) {
            wp_send_json_success('Monitor updated successfully');
        } else {
            wp_send_json_error('Failed to update monitor');
        }
    }
}
