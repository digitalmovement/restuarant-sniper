<?php
class Restaurant_Monitor_Cron {
    public static function init() {
        add_action('restaurant_monitor_cron_hook', [self::class, 'check_availability']);
        
        if (!wp_next_scheduled('restaurant_monitor_cron_hook')) {
            wp_schedule_event(time(), 'thirty_minutes', 'restaurant_monitor_cron_hook');
        }
    }

    public static function register_cron_interval($schedules) {
        $schedules['thirty_minutes'] = array(
            'interval' => 1800,
            'display'  => 'Every 30 Minutes'
        );
        return $schedules;
    }

    public static function check_availability() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'restaurant_monitors';
        
        $current_time = current_time('mysql');
        $monitors = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name 
            WHERE stop_monitoring = 0 
            AND reservation_date >= %s",
            date('Y-m-d')
        ));

        foreach ($monitors as $monitor) {
            // Skip if within 2 hours of booking time
            $booking_datetime = $monitor->reservation_date . ' ' . $monitor->reservation_time;
            $time_diff = strtotime($booking_datetime) - current_time('timestamp');
            if ($time_diff <= 7200) { // 2 hours in seconds
                self::stop_monitoring($monitor->id);
                continue;
            }

            $url = sprintf(
                'https://www.sevenrooms.com/api-yoa/availability/widget/range?venue=%s&time_slot=%s&party_size=%d&halo_size_interval=100&start_date=%s&num_days=1&channel=SEVENROOMS_WIDGET&selected_lang_code=en',
                $monitor->restaurant_url,
                urlencode(date('g:i A', strtotime($monitor->reservation_time))),
                $monitor->party_size,
                $monitor->reservation_date
            );

            $response = wp_remote_get($url);

            if (is_wp_error($response)) {
                error_log('Restaurant Monitor Error: ' . $response->get_error_message());
                continue;
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            $availability_check = self::check_availability_in_data(
                $data, 
                $monitor->reservation_date . ' ' . $monitor->reservation_time
            );

            if ($availability_check['available']) {
                self::notify_user($monitor, $availability_check['found_time'] ?? null);
                self::stop_monitoring($monitor->id);
            }
        }
    }

    public static function check_availability_in_data($data, $requested_time) {
        if (!isset($data['data']['availability'])) {
            return false;
        }

        $requested_timestamp = strtotime($requested_time);
        $two_hours_before = $requested_timestamp - 7200; // 2 hours in seconds
        $two_hours_after = $requested_timestamp + 7200;

        foreach ($data['data']['availability'] as $date => $dateData) {
            foreach ($dateData[0]['times'] as $slot) {
                if (isset($slot['time']) && !isset($slot['is_requestable'])) {
                    $slot_timestamp = strtotime($date . ' ' . $slot['time']);
                    
                    // Check if slot is within 2 hours window
                    if ($slot_timestamp >= $two_hours_before && $slot_timestamp <= $two_hours_after) {
                        return [
                            'available' => true,
                            'found_time' => $slot['time']
                        ];
                    }
                }
            }
        }
        
        return ['available' => false];
    }

    private static function notify_user($monitor, $found_time = null) {
        $user = get_userdata($monitor->user_id);
        if (!$user) return;

        $booking_url = sprintf(
            'https://www.sevenrooms.com/reservations/%s',
            $monitor->restaurant_url
        );

        $subject = 'Restaurant Availability Alert!';
        
        // Add found time information if available
        $time_message = $found_time ? 
            sprintf("Available Time: %s (within 2 hours of your requested time %s)\n", 
                $found_time, 
                $monitor->reservation_time) :
            sprintf("Time: %s\n", $monitor->reservation_time);

        $message = sprintf(
            "A table is now available for your requested booking:\n\n" .
            "Date: %s\n" .
            "%s" .
            "Party Size: %d\n\n" .
            "Book now at: %s",
            $monitor->reservation_date,
            $time_message,
            $monitor->party_size,
            $booking_url
        );

        wp_mail($user->user_email, $subject, $message);
    }


    private static function stop_monitoring($monitor_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'restaurant_monitors';
        
        $wpdb->update(
            $table_name,
            ['stop_monitoring' => 1],
            ['id' => $monitor_id],
            ['%d'],
            ['%d']
        );
    }
}

// Add to plugin's main file:
add_filter('cron_schedules', ['Restaurant_Monitor_Cron', 'register_cron_interval']);
add_action('init', ['Restaurant_Monitor_Cron', 'init']);