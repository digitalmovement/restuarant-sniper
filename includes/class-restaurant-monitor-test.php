<?php

class Restaurant_Monitor_Test {
    public static function test_availability_check() {
        // Get all active monitors
        global $wpdb;
        $table_name = $wpdb->prefix . 'restaurant_monitors';
        
        $monitors = $wpdb->get_results(
            "SELECT * FROM $table_name WHERE stop_monitoring = 0"
        );
        
        echo "<h2>Testing Restaurant Monitors</h2>";
        echo "<pre>";
        
        foreach ($monitors as $monitor) {
            echo "\nTesting monitor ID: {$monitor->id}\n";
            echo "Restaurant URL: {$monitor->restaurant_url}\n";
            echo "Requested time: {$monitor->reservation_time}\n";
            
            // Format URL
            $url = sprintf(
                'https://www.sevenrooms.com/api-yoa/availability/widget/range?venue=%s&time_slot=%s&party_size=%d&halo_size_interval=100&start_date=%s&num_days=1&channel=SEVENROOMS_WIDGET&selected_lang_code=en',
                $monitor->restaurant_url,
                urlencode(date('g:i A', strtotime($monitor->reservation_time))),
                $monitor->party_size,
                $monitor->reservation_date
            );
            
            echo "\nAPI URL: $url\n";
            
            // Test time difference
            $booking_datetime = $monitor->reservation_date . ' ' . $monitor->reservation_time;
            $time_diff = strtotime($booking_datetime) - current_time('timestamp');
            echo "\nTime until booking: " . round($time_diff/3600, 2) . " hours\n";
            
            if ($time_diff <= 7200) {
                echo "NOTICE: Within 2 hours of booking - would stop monitoring\n";
                continue;
            }
            
            // Make API request
            $response = wp_remote_get($url);
            
            if (is_wp_error($response)) {
                echo "ERROR: " . $response->get_error_message() . "\n";
                continue;
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
       
            echo "\nResponse Status Code: " . wp_remote_retrieve_response_code($response) . "\n";
    

            if (isset($data['data']['availability'])) {
                echo "\nFound availability data.\n";
                foreach ($data['data']['availability'] as $date => $slots) {
                    echo "\nDate: $date\n";
                    echo "----------------------------------------\n";
                    foreach ($slots as $slot) {
                  
                        if (isset($slot['time'])) {
                            echo sprintf(
                                "Time: %s - %s - %s\n",
                                $slot['time'],
                                isset($slot['type']) ? $slot['type'] : 'N/A',
                                isset($slot['is_requestable']) ? 'Available' : 'Not Available'
                            );
                        }
                    }
                }
            } else {
                echo "\nNo availability data found in response\n";
                echo "Response data:\n";
                print_r($data);
            }
            
            echo "\n----------------------------------------\n";
        }
        
        echo "</pre>";
    }
}

// Add test endpoint
function register_test_endpoint() {
    add_action('wp_ajax_test_restaurant_monitor', function() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        Restaurant_Monitor_Test::test_availability_check();
        wp_die();
    });
}
add_action('init', 'register_test_endpoint');