<?php
$core = ResetCore::getInstance();
$event_details = $core->get_event_details();
$ticket_pricing = $core->get_ticket_pricing();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RESET Event - Book Your Ticket</title>
    <?php wp_head(); ?>
    <style>
        * {
            box-sizing: border-box;
        }
        
        body {
            margin: 0;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #000000;
            min-height: 100vh;
            color: #333;
        }
        
        .reset-container {
            background: white;
            border-radius: 20px;
            padding: 0;
            box-shadow: 0 20px 40px rgba(255,255,255,0.1);
            max-width: 1000px;
            margin: 0 auto;
            overflow: hidden;
            position: relative;
        }
        
        .reset-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #f9c613 0%, #ffdd44 100%);
        }
        
        .page-header {
            text-align: center;
            padding-top: 30px;
            background: white;
            border-radius: 20px 20px 0 0;
        }
        
        /* .logo-container {
            margin-bottom: 20px;
        } */
        
        .logo-container img {
            max-width: 200px !important;
            height: auto !important;
            transition: transform 0.3s ease !important;
            display: unset !important;
        }
        
        .logo-container img:hover {
            transform: scale(1.05);
        }
        
        .booking-form {
            padding: 30px;
            padding-top: 0;
        }
        
        .form-section {
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 24px;
            font-weight: 700;
            color: #000000;
            margin-bottom: 25px;
            position: relative;
            padding-left: 15px;
        }
        
        .section-title::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(180deg, #f9c613 0%, #ffdd44 100%);
            border-radius: 2px;
        }
        
        .ticket-options {
                background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
                gap: 15px;
                border-radius: 15px;
                border: 1px solid #e9ecef;
                padding: 25px;
        }
        
        .ticket-option {
            border: 2px solid #f0f0f0;
            border-radius: 12px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            background: linear-gradient(135deg, #ffffff 0%, #fafafa 100%);
            overflow: hidden;
        }
        
        .ticket-option::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #f9c613 0%, #ffdd44 100%);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .ticket-option:hover {
            border-color: #f9c613;
            box-shadow: 0 10px 30px rgba(249, 198, 19, 0.15);
            transform: translateY(-2px);
        }
        
        .ticket-option:hover::before {
            transform: scaleX(1);
        }
        
        .ticket-option.selected {
            border-color: #f9c613;
            background: linear-gradient(135deg, #fff9e6 0%, #fffbf0 100%);
            box-shadow: 0 10px 30px rgba(249, 198, 19, 0.2);
        }
        
        .ticket-option.selected::before {
            transform: scaleX(1);
        }
        
        .ticket-option input[type="radio"] {
            display: none;
            position: absolute;
            top: 20px;
            right: 20px;
            width: 24px;
            height: 24px;
            accent-color: #f9c613;
            cursor: pointer;
        }
        
        .ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        
        .ticket-name {
            font-size: 18px;
            font-weight: 700;
            color: #000000;
            margin-bottom: 5px;
        }
        
        .ticket-price {
            font-size: 24px;
            font-weight: 800;
            color: #000000;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }
        
        .ticket-benefits {
            font-size: 13px;
            color: #666;
            line-height: 1.5;
            background: rgba(249, 198, 19, 0.1);
            padding: 12px;
            border-radius: 8px;
            border-left: 4px solid #f9c613;
        }
        
        .personal-info-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border-radius: 15px;
            padding: 25px;
            border: 1px solid #e9ecef;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-label {
            font-weight: 600;
            color: #000000;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .form-input {
            padding: 15px 18px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #f9c613;
            box-shadow: 0 0 0 3px rgba(249, 198, 19, 0.15);
            transform: translateY(-1px);
        }
        
        .form-input:hover {
            border-color: #f9c613;
        }
        
        .form-input.error {
            border-color: #dc3545;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.15);
        }
        
        .submit-section {
            text-align: center;
            padding: 25px 0 15px 0;
        }
        
        .submit-btn {
            background: linear-gradient(135deg, #f9c613 0%, #ffdd44 100%);
            color: #000000;
            border: none;
            padding: 20px 50px;
            font-size: 18px;
            font-weight: 700;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 6px 20px rgba(249, 198, 19, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }
        
        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(249, 198, 19, 0.4);
        }
        
        .submit-btn:hover::before {
            left: 100%;
        }
        
        .submit-btn:active {
            transform: translateY(-1px);
        }
        
        .submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        .message {
            padding: 20px;
            border-radius: 12px;
            margin: 25px 0;
            font-weight: 500;
            border: 2px solid transparent;
        }
        
        .message.success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border-color: #c3e6cb;
        }
        
        .message.error {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border-color: #f5c6cb;
        }
        
        .important-note {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: 2px solid #f9c613;
            padding: 20px;
            border-radius: 15px;
            margin-top: 20px;
            position: relative;
        }
        
        .important-note::before {
            content: '⚠️';
            position: absolute;
            top: -10px;
            left: 25px;
            background: #f9c613;
            padding: 5px 10px;
            border-radius: 50%;
            font-size: 20px;
        }
        
        .important-note h4 {
            color: #856404;
            margin-top: 10px;
            margin-bottom: 15px;
            font-size: 18px;
            font-weight: 700;
        }
        
        .important-note p {
            color: #856404;
            margin-bottom: 0;
            line-height: 1.6;
        }
        
        .loading {
            display: none;
            text-align: center;
            margin: 30px 0;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #f9c613;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .loading p {
            color: #666;
            font-size: 16px;
            margin: 0;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .ticket-options {
                grid-template-columns: 1fr;
            }
            
            .reset-container {
                margin: 10px;
            }
            
            .booking-form {
                padding: 20px 15px;
            }
            
            .page-header {
                padding-top: 30px;
            }
            
            .logo-container img {
                max-width: 160px;
            }
            
            .ticket-option {
                padding: 12px;
            }
            
            .ticket-name {
                font-size: 16px;
            }
            
            .ticket-price {
                font-size: 20px;
            }
            
            .submit-btn {
                padding: 15px 40px;
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="page-header">
            <div class="logo-container">
                <?php
                $core = ResetCore::getInstance();
                echo $core->get_logo_html('black', '400', '', '200px');
                ?>
            </div>
        </div>
        
        <div class="booking-form">
            <form id="bookingForm">
                <!-- Ticket Selection Section -->
                <div class="form-section">
                    <h2 class="section-title">Select Your Ticket</h2>
                    <div class="ticket-options">
                        <?php foreach ($ticket_pricing as $key => $ticket): ?>
                            <?php if ($ticket['available']): ?>
                                <div class="ticket-option" onclick="selectTicket('<?php echo $key; ?>', this)">
                                    <input type="radio" name="ticket_type" value="<?php echo $key; ?>" id="ticket_<?php echo $key; ?>">
                                    <div class="ticket-header">
                                        <div>
                                            <div class="ticket-name"><?php echo $ticket['name']; ?></div>
                                            <div class="ticket-price">Rs. <?php echo number_format($ticket['price']); ?></div>
                                        </div>
                                    </div>
                                    <div class="ticket-benefits"><?php echo $ticket['benefits']; ?></div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Personal Information Section -->
                <div class="form-section">
                    <h2 class="section-title">Personal Information</h2>
                    <div class="personal-info-section">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="fullName" class="form-label">Full Name *</label>
                                <input type="text" id="fullName" name="name" class="form-input" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone" class="form-label">Phone Number *</label>
                                <input type="tel" id="phone" name="phone" class="form-input" placeholder="0771234567" required>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" id="email" name="email" class="form-input" required>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Important Information -->
                <div class="important-note">
                    <h4>Important Information</h4>
                    <p>After successful payment, you will receive 5 free invitation keys that you can share with your friends. Please note that we cannot guarantee all tokens will be eligible if we reach our venue capacity.</p>
                </div>
                
                <div id="loading" class="loading">
                    <div class="spinner"></div>
                    <p>Processing your booking...</p>
                </div>
                
                <div id="message" class="message" style="display: none;"></div>
                
                <div class="submit-section">
                    <button type="submit" class="submit-btn" id="submitBtn">
                        Proceed to Payment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const bookingForm = document.getElementById('bookingForm');
        const loading = document.getElementById('loading');
        const messageDiv = document.getElementById('message');
        const submitBtn = document.getElementById('submitBtn');
        
        // Check if key exists in session storage
        const token = sessionStorage.getItem('reset_token');
        if (!token) {
            window.location.href = '<?php echo site_url('/reset'); ?>';
            return;
        }
        
        // Form submission
        bookingForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (validateForm()) {
                processBooking();
            }
        });
        
        function validateForm() {
            let isValid = true;
            
            // Clear previous errors
            document.querySelectorAll('.form-input').forEach(input => {
                input.classList.remove('error');
            });
            
            // Check if ticket is selected
            const selectedTicket = document.querySelector('input[name="ticket_type"]:checked');
            if (!selectedTicket) {
                showMessage('Please select a ticket type.', 'error');
                return false;
            }
            
            // Validate required fields
            const requiredFields = ['name', 'email', 'phone'];
            
            requiredFields.forEach(fieldName => {
                const field = document.querySelector(`[name="${fieldName}"]`);
                if (!field.value.trim()) {
                    field.classList.add('error');
                    isValid = false;
                }
            });
            
            // Validate email format
            const emailField = document.querySelector('[name="email"]');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (emailField.value && !emailRegex.test(emailField.value)) {
                emailField.classList.add('error');
                showMessage('Please enter a valid email address.', 'error');
                return false;
            }
            
            // Validate phone format (Sri Lankan)
            const phoneField = document.querySelector('[name="phone"]');
            const phoneRegex = /^0[0-9]{9}$/;
            if (phoneField.value && !phoneRegex.test(phoneField.value.replace(/[^0-9]/g, ''))) {
                phoneField.classList.add('error');
                showMessage('Please enter a valid Sri Lankan phone number (10 digits starting with 0).', 'error');
                return false;
            }
            
            if (!isValid) {
                showMessage('Please fill in all required fields correctly.', 'error');
            }
            
            return isValid;
        }
        
        function processBooking() {
            setLoading(true);
            hideMessage();
            
            const formData = new FormData(bookingForm);
            formData.append('action', 'reset_process_booking');
            formData.append('token_code', token);
            formData.append('nonce', resetAjax.nonce);
            
            fetch(resetAjax.ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                setLoading(false);
                
                if (data.success && data.data.success) {
                    showMessage('Booking created successfully! Redirecting to payment...', 'success');
                    
                    // Store booking data for success page
                    sessionStorage.setItem('reset_purchase_id', data.data.purchase_id);
                    
                    // Redirect to payment or success page
                    setTimeout(() => {
                        if (data.data.payment_url) {
                            window.location.href = data.data.payment_url;
                        } else {
                            window.location.href = '<?php echo site_url('/reset/success'); ?>';
                        }
                    }, 2000);
                } else {
                    const message = data.data ? data.data.message : 'An error occurred. Please try again.';
                    showMessage(message, 'error');
                }
            })
            .catch(error => {
                setLoading(false);
                showMessage('Network error. Please check your connection and try again.', 'error');
                console.error('Error:', error);
            });
        }
        
        function setLoading(isLoading) {
            if (isLoading) {
                loading.style.display = 'block';
                submitBtn.disabled = true;
                submitBtn.textContent = 'Processing...';
            } else {
                loading.style.display = 'none';
                submitBtn.disabled = false;
                submitBtn.textContent = 'Proceed to Payment';
            }
        }
        
        function showMessage(message, type) {
            messageDiv.textContent = message;
            messageDiv.className = 'message ' + type;
            messageDiv.style.display = 'block';
            messageDiv.scrollIntoView({ behavior: 'smooth' });
        }
        
        function hideMessage() {
            messageDiv.style.display = 'none';
        }
        
        // Format phone number as user types
        document.getElementById('phone').addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    });
    
    // Global function for ticket selection
    function selectTicket(ticketKey, element) {
        // Remove selected class from all options
        document.querySelectorAll('.ticket-option').forEach(option => {
            option.classList.remove('selected');
        });
        
        // Add selected class to clicked option
        element.classList.add('selected');
        
        // Check the radio button
        const radioButton = element.querySelector('input[type="radio"]');
        if (radioButton) {
            radioButton.checked = true;
        }
    }
    </script>
    
    <?php wp_footer(); ?>
</body>
</html> 