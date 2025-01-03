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
                    
                    // Use the modified check_availability_in_data function
                    $availability_check = Restaurant_Monitor_Cron::check_availability_in_data(
                        $data,
                        $monitor->reservation_date . ' ' . $monitor->reservation_time
                    );
                    
                    foreach ($data['data']['availability'] as $date => $dateData) {
                        echo "\nDate: $date\n";
                        echo "----------------------------------------\n";
                        
                        foreach ($dateData[0]['times'] as $slot) {
                            if (isset($slot['time'])) {
                                $slot_time = $date . ' ' . $slot['time'];
                                $time_diff = abs(strtotime($slot_time) - strtotime($booking_datetime));
                                $hours_diff = round($time_diff / 3600, 1);
                                
                                echo sprintf(
                                    "Time: %s - %s - %s (%.1f hours from requested time)\n",
                                    $slot['time'],
                                    isset($slot['type']) ? $slot['type'] : 'N/A',
                                    isset($slot['is_requestable']) ? 'Not Available' : 'Available',
                                    $hours_diff
                                );
                            }
                        }
                    }
                    
                    if ($availability_check['available']) {
                        echo "\n[SIMULATION] Would send notification for available time: " . 
                             $availability_check['found_time'] . "\n";
                        
                        // Simulate email content
                        $booking_url = sprintf(
                            'https://www.sevenrooms.com/reservations/%s',
                            $monitor->restaurant_url
                        );
                        
                        echo "\nSimulated Email Content:";
                        echo "\n------------------------";
                        echo "\nSubject: Restaurant Availability Alert!";
                        echo "\n\nA table is now available for your requested booking:";
                        echo "\n\nDate: " . $monitor->reservation_date;
                        echo "\nAvailable Time: " . $availability_check['found_time'] . 
                             " (within 2 hours of your requested time " . $monitor->reservation_time . ")";
                        echo "\nParty Size: " . $monitor->party_size;
                        echo "\n\nBook now at: " . $booking_url;
                        echo "\n------------------------\n";
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