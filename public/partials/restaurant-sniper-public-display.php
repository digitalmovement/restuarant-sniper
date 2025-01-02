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
        <input type="time" id="reservation_time" name="reservation_time" required>
        <input type="number" id="party_size" name="party_size" placeholder="Number of people" required>
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
                        <p>Time: <?php echo esc_html($monitor->reservation_time); ?></p>
                        <p>Party Size: <?php echo esc_html($monitor->party_size); ?></p>
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