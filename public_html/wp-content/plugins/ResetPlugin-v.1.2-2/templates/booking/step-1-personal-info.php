<?php
// Get session data
$session_data = $wizard->get_session_data();
$personal_info = $session_data['step_data']['personal_info'] ?? array();
$token_code = $session_data['token_code'] ?? '';
?>

<div class="step-1-content">
    <h2 class="step-title">Personal Information</h2>
    <p class="step-description">Please provide your contact details for ticket booking.</p>
    
    <form id="personalInfoForm" class="personal-info-form">
        <div class="form-section">
            <div class="form-grid">
                <div class="form-group">
                    <label for="fullName" class="form-label">Name *</label>
                    <input type="text" 
                           id="fullName" 
                           name="name" 
                           class="form-input" 
                           value="<?php echo esc_attr($personal_info['name'] ?? ''); ?>"
                           required>
                    <div class="field-error" id="nameError"></div>
                </div>
                
                <div class="form-group">
                    <label for="gamingName" class="form-label">Gaming Name *</label>
                    <input type="text" 
                           id="gamingName" 
                           name="gaming_name" 
                           class="form-input" 
                           placeholder="ProGamer123" 
                           value="<?php echo esc_attr($personal_info['gaming_name'] ?? ''); ?>"
                           maxlength="50"
                           required>
                    <div class="field-error" id="gamingNameError"></div>
                    <small class="field-help">Your gaming alias or nickname</small>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email Address *</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="form-input" 
                           value="<?php echo esc_attr($personal_info['email'] ?? ''); ?>"
                           required>
                    <div class="field-error" id="emailError"></div>
                    <small class="field-help">We'll send your ticket and confirmation to this email</small>
                </div>
                
                <div class="form-group">
                    <label for="phone" class="form-label">Phone Number *</label>
                    <input type="tel" 
                           id="phone" 
                           name="phone" 
                           class="form-input" 
                           placeholder="0771234567" 
                           value="<?php echo esc_attr($personal_info['phone'] ?? ''); ?>"
                           required>
                    <div class="field-error" id="phoneError"></div>
                    <small class="field-help">Enter your Sri Lankan phone number (10 digits starting with 0)</small>
                </div>
            </div>
        </div>
        
        <div class="token-info">
            <div class="token-display">
                <label>Your Key:</label>
                <span class="token-code"><?php echo esc_html($token_code); ?></span>
            </div>
        </div>
    </form>
</div>

<style>
.step-title {
    font-size: 28px;
    font-weight: 700;
    color: #000000;
    margin-bottom: 10px;
    text-align: center;
}

.step-description {
    font-size: 16px;
    color: #666;
    text-align: center;
    margin-bottom: 30px;
}

.personal-info-form {
    max-width: 600px;
    margin: 0 auto;
}

.form-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 15px;
    padding: 25px;
    border: 1px solid #e9ecef;
    margin-bottom: 20px;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
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
    margin-bottom: 8px;
    font-size: 16px;
}

#personalInfoForm .form-input {
    padding: 15px 18px;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    transition: all 0.3s ease;
    background: white;
    color: #000000 !important;
}

#personalInfoForm .form-input:focus {
    outline: none;
    border-color: #f9c613;
    box-shadow: 0 0 0 3px rgba(249, 198, 19, 0.15);
    transform: translateY(-1px);
}

#personalInfoForm .form-input:hover {
    border-color: #f9c613;
}

#personalInfoForm .form-input.error {
    border-color: #dc3545;
    box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.15);
}

.field-error {
    color: #dc3545;
    font-size: 14px;
    margin-top: 5px;
    display: none;
}

.field-help {
    color: #666;
    font-size: 13px;
    margin-top: 5px;
}

.token-info {
    background: linear-gradient(135deg, #fff9e6 0%, #fffbf0 100%);
    border: 2px solid #f9c613;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
}

.token-display {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
    font-size: 16px;
}

.token-display label {
    font-weight: 600;
    color: #000000;
}

.token-code {
    background: white;
    color: #000000;
    border: 2px solid #f9c613;
    padding: 8px 16px;
    border-radius: 8px;
    font-family: 'Courier New', monospace;
    font-weight: 700;
    font-size: 18px;
    letter-spacing: 1px;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .form-section {
        padding: 20px;
    }
    
    .token-display {
        flex-direction: column;
        gap: 10px;
    }

    .step-description {
        font-size: 14px;
    }
}
</style>

<script>
// Step 1 specific validation
function validateCurrentStep() {
    const form = document.getElementById('personalInfoForm');
    const formData = new FormData(form);
    
    let isValid = true;
    
    // Clear previous errors
    clearErrors();
    
    // Validate name
    const name = formData.get('name').trim();
    if (!name) {
        showFieldError('nameError', 'Name is required');
        isValid = false;
    }
    
    // Validate email
    const email = formData.get('email').trim();
    if (!email) {
        showFieldError('emailError', 'Email is required');
        isValid = false;
    } else if (!isValidEmail(email)) {
        showFieldError('emailError', 'Please enter a valid email address');
        isValid = false;
    }
    
    // Validate phone
    const phone = formData.get('phone').trim();
    if (!phone) {
        showFieldError('phoneError', 'Phone number is required');
        isValid = false;
    } else if (!isValidSriLankanPhone(phone)) {
        showFieldError('phoneError', 'Please enter a valid Sri Lankan phone number (10 digits starting with 0)');
        isValid = false;
    }
    
    // Validate gaming name (optional)
    const gamingName = formData.get('gaming_name').trim();
    if (gamingName) {
        if (gamingName.length > 50) {
            showFieldError('gamingNameError', 'Gaming name must be 50 characters or less');
            isValid = false;
        }
    } else {
        showFieldError('gamingNameError', 'Gaming Name is required');
        isValid = false;
    }
    
    if (isValid) {
        // Save data via AJAX and then proceed to next step
        saveStepData(1, {
            name: name,
            email: email,
            phone: phone,
            gaming_name: gamingName
        }, function() {
            // On success, proceed to next step
            proceedToNextStep(<?php echo $wizard->get_next_step(1); ?>);
        });
        return false; // Prevent default redirect, let AJAX handle it
    }
    
    return false;
}

function clearErrors() {
    document.querySelectorAll('.field-error').forEach(error => {
        error.style.display = 'none';
        error.textContent = '';
    });
    
    document.querySelectorAll('.form-input').forEach(input => {
        input.classList.remove('error');
    });
}

function showFieldError(errorId, message) {
    const errorElement = document.getElementById(errorId);
    const inputElement = errorElement.previousElementSibling;
    
    errorElement.textContent = message;
    errorElement.style.display = 'block';
    inputElement.classList.add('error');
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function isValidSriLankanPhone(phone) {
    // Remove non-numeric characters
    const cleanPhone = phone.replace(/[^0-9]/g, '');
    
    // Check if it's 10 digits starting with 0
    return /^0[0-9]{9}$/.test(cleanPhone);
}

function saveStepData(step, data, successCallback) {
    setLoading(true);
    
    fetch(resetAjax.ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'reset_save_step_data',
            step: step,
            data: JSON.stringify(data),
            nonce: resetAjax.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        setLoading(false);
        if (data.success) {
            // Data saved successfully
            console.log('Step data saved');
            if (successCallback && typeof successCallback === 'function') {
                successCallback();
            }
        } else {
            showMessage(data.data?.message || 'Failed to save data', 'error');
        }
    })
    .catch(error => {
        setLoading(false);
        console.error('Error saving step data:', error);
        showMessage('Network error. Please try again.', 'error');
    });
}

// Auto-format phone number
document.getElementById('phone').addEventListener('input', function() {
    this.value = this.value.replace(/[^0-9]/g, '');
    
    // Limit to 10 digits
    if (this.value.length > 10) {
        this.value = this.value.substr(0, 10);
    }
});

// Real-time validation
document.querySelectorAll('.form-input').forEach(input => {
    input.addEventListener('blur', function() {
        // Clear error state when user starts fixing
        this.classList.remove('error');
        const errorElement = this.nextElementSibling;
        if (errorElement && errorElement.classList.contains('field-error')) {
            errorElement.style.display = 'none';
        }
    });
});
</script> 