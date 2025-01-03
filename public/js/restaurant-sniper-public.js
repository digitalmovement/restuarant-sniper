jQuery(document).ready(function($) {
    $('#add-monitor-form').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: restaurantSniper.ajaxurl,
            type: 'POST',
            data: {
                action: 'add_restaurant_monitor',
                nonce: restaurantSniper.nonce,
                restaurant_url: $('#restaurant_url').val(),
                reservation_date: $('#reservation_date').val(),
                reservation_time: $('#reservation_time').val(),
                party_size: $('#party_size').val()
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Failed to add monitor');
                }
            }
        });
    });

    $('.delete-monitor').on('click', function() {
        if (!confirm('Are you sure you want to delete this monitor?')) {
            return;
        }

        const monitorId = $(this).closest('.monitor-item').data('id');
        
        $.ajax({
            url: restaurantSniper.ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_restaurant_monitor',
                nonce: restaurantSniper.nonce,
                monitor_id: monitorId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Failed to delete monitor');
                }
            }
        });
    });

    $('.edit-monitor').on('click', function() {
        const $item = $(this).closest('.monitor-item');
        const $details = $item.find('.monitor-details');
        const currentTime = $details.find('p:nth-child(3)').text().split(': ')[1];
        const currentPartySize = $details.find('p:nth-child(4)').text().split(': ')[1].split(' ')[0];
        
        // Generate time options HTML
        let timeOptions = '<option value="">Select Time</option>';
        const start = new Date();
        start.setHours(11, 0, 0);
        const end = new Date();
        end.setHours(23, 30, 0);
        
        for (let time = start; time <= end; time.setMinutes(time.getMinutes() + 30)) {
            const timeValue = time.toLocaleTimeString('en-US', { 
                hour12: false, 
                hour: '2-digit', 
                minute: '2-digit'
            });
            const timeDisplay = time.toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: '2-digit'
            });
            const selected = timeDisplay === currentTime ? 'selected' : '';
            timeOptions += `<option value="${timeValue}" ${selected}>${timeDisplay}</option>`;
        }
        
        // Generate party size options HTML
        let partySizeOptions = '<option value="">Number of Guests</option>';
        for (let i = 2; i <= 8; i++) {
            const selected = i === parseInt(currentPartySize) ? 'selected' : '';
            partySizeOptions += `<option value="${i}" ${selected}>${i} Guests</option>`;
        }
        
        // Transform display into editable form
        $details.html(`
            <form class="edit-monitor-form">
                <input type="url" name="restaurant_url" value="${$details.find('a').attr('href')}" required>
                <input type="date" name="reservation_date" value="${$details.find('p:nth-child(2)').text().split(': ')[1]}" required>
                <select name="reservation_time" required>${timeOptions}</select>
                <select name="party_size" required>${partySizeOptions}</select>
                <button type="submit">Save</button>
                <button type="button" class="cancel-edit">Cancel</button>
            </form>
        `);
    });

    $(document).on('submit', '.edit-monitor-form', function(e) {
        e.preventDefault();
        const $form = $(this);
        const $item = $form.closest('.monitor-item');
        
        $.ajax({
            url: restaurantSniper.ajaxurl,
            type: 'POST',
            data: {
                action: 'update_restaurant_monitor',
                nonce: restaurantSniper.nonce,
                monitor_id: $item.data('id'),
                restaurant_url: $form.find('[name="restaurant_url"]').val(),
                reservation_date: $form.find('[name="reservation_date"]').val(),
                reservation_time: $form.find('[name="reservation_time"]').val(),
                party_size: $form.find('[name="party_size"]').val()
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Failed to update monitor');
                }
            }
        });
    });

    $(document).on('click', '.cancel-edit', function() {
        location.reload();
    });
});