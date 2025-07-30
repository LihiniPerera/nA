/* RESET Event Ticketing System - Frontend JavaScript */
jQuery(document).ready(function($) {
    
    // Token validation
    $('#reset-validate-token').on('click', function(e) {
        e.preventDefault();
        
        const tokenCode = $('#token-code').val();
        if (!tokenCode) {
            showMessage('Please enter a token code.', 'error');
            return;
        }
        
        showLoading('Validating token...');
        
        $.ajax({
            url: resetAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'reset_validate_token',
                token_code: tokenCode,
                nonce: resetAjax.nonce
            },
            success: function(response) {
                hideLoading();
                
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    $('#booking-form').show();
                    $('#token-entry').hide();
                } else {
                    showMessage(response.data.message, 'error');
                }
            },
            error: function() {
                hideLoading();
                showMessage('An error occurred. Please try again.', 'error');
            }
        });
    });
    
    // Booking form submission
    $('#reset-booking-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            action: 'reset_process_booking',
            token_code: $('#token-code').val(),
            name: $('#purchaser-name').val(),
            email: $('#purchaser-email').val(),
            phone: $('#purchaser-phone').val(),
            ticket_type: $('input[name="ticket_type"]:checked').val(),
            nonce: resetAjax.nonce
        };
        
        // Validate form
        if (!validateBookingForm(formData)) {
            return;
        }
        
        showLoading('Processing booking...');
        
        $.ajax({
            url: resetAjax.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                hideLoading();
                
                if (response.success) {
                    // Check if local mode
                    if (response.data.local_mode) {
                        showMessage('🚧 LOCAL MODE: Payment bypassed - Redirecting to success page...', 'success');
                        setTimeout(function() {
                            window.location.href = response.data.payment_url;
                        }, 2000);
                    } else {
                        // Redirect to payment
                        window.location.href = response.data.payment_url;
                    }
                } else {
                    showMessage(response.data.message, 'error');
                }
            },
            error: function() {
                hideLoading();
                showMessage('An error occurred. Please try again.', 'error');
            }
        });
    });
    
    // Helper functions
    function validateBookingForm(data) {
        if (!data.name) {
            showMessage('Please enter your name.', 'error');
            return false;
        }
        
        if (!data.email) {
            showMessage('Please enter your email.', 'error');
            return false;
        }
        
        if (!data.phone) {
            showMessage('Please enter your phone number.', 'error');
            return false;
        }
        
        if (!data.ticket_type) {
            showMessage('Please select a ticket type.', 'error');
            return false;
        }
        
        return true;
    }
    
    function showMessage(message, type) {
        const messageHtml = '<div class="reset-message ' + type + '">' + message + '</div>';
        $('#reset-messages').html(messageHtml);
        
        // Auto-hide success messages after 5 seconds
        if (type === 'success') {
            setTimeout(function() {
                $('#reset-messages').empty();
            }, 5000);
        }
    }
    
    function showLoading(message) {
        const loadingHtml = '<div class="reset-loading">' + message + '</div>';
        $('#reset-messages').html(loadingHtml);
    }
    
    function hideLoading() {
        $('#reset-messages').empty();
    }
    
}); 