<?php

/**
 * Provide a public-facing view for the plugin
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       https://ianbryce.com
 * @since      1.0.0
 *
 * @package    Restaurant_Sniper
 * @subpackage Restaurant_Sniper/public/partials
 */
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="restaurant-monitor-container">
    <h2>Restaurant Monitors</h2>
    
    <form id="add-monitor-form" class="monitor-form">
        <h3>Add New Monitor</h3>
        <input type="url" id="restaurant_url" name="restaurant_url" placeholder="Restaurant URL" required>
        <input type="date" id="reservation_date" name="reservation_date" required>
        <select id="reservation_time" name="reservation_time" required>
            <option value="">Select Time</option>
            <?php
            // Generate time options from 11:00 AM to 11:30 PM in 30-minute increments
            $start = strtotime('11:00');
            $end = strtotime('23:30');
            for ($time = $start; $time <= $end; $time = strtotime('+30 minutes', $time)) {
                echo sprintf(
                    '<option value="%s">%s</option>', 
                    date('H:i', $time),
                    date('g:i A', $time)
                );
            }
            ?>
        </select>
        <select id="party_size" name="party_size" required>
            <option value="">Number of Guests</option>
            <?php
            // Generate options for 2-8 guests
            for ($i = 2; $i <= 8; $i++) {
                echo sprintf(
                    '<option value="%d">%d %s</option>',
                    $i,
                    $i,
                    $i === 1 ? 'Guest' : 'Guests'
                );
            }
            ?>
        </select>
        <button type="submit">Add Monitor</button>
    </form>

    <div class="monitors-list">
        <?php if (empty($monitors)) : ?>
            <p>No restaurants being monitored.</p>
        <?php else : ?>
            <?php foreach ($monitors as $monitor) : ?>
                <div class="monitor-item" data-id="<?php echo esc_attr($monitor->id); ?>">
                    <div class="monitor-details">
                        <p>Restaurant: <a href="<?php echo esc_url($monitor->restaurant_url); ?>" target="_blank"><?php echo esc_url($monitor->restaurant_url); ?></a></p>
                        <p>Date: <?php echo esc_html($monitor->reservation_date); ?></p>
                        <p>Time: <?php echo esc_html(date('g:i A', strtotime($monitor->reservation_time))); ?></p>
                        <p>Party Size: <?php echo esc_html($monitor->party_size); ?> Guests</p>
                    </div>
                    <div class="monitor-actions">
                        <button class="edit-monitor">Edit</button>
                        <button class="delete-monitor">Delete</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>