<?php
// Get session and addon data
$session_data = $wizard->get_session_data();
$token_type = $session_data['token_type'] ?? '';
$addons_data = $session_data['step_data']['addons'] ?? array();
$selected_addons = $addons_data['selected_addons'] ?? $addons->get_default_addons($token_type);

// Get available addons
$available_addons = $addons->get_available_addons();

// Check if this is a polo_ordered token
$is_polo_ordered = ($token_type === 'polo_ordered');
?>

<div class="step-3-content">
    <h2 class="step-title">Add-ons Optional</h2>
    <p class="step-description">
        <?php if ($is_polo_ordered): ?>
            🎉 Great news! Your Package 0 is included FREE with your polo order. Want more drinks? Select an additional package below.
        <?php else: ?>
            Choose one optional add-on to enhance your event experience, or proceed without selecting any.
        <?php endif; ?>
    </p>
    
    <?php if (empty($available_addons)): ?>
        <div class="no-addons-message">
            <div class="info-box">
                <h4>ℹ️ No Add-ons Available</h4>
                <p>There are currently no add-ons available for selection. You can proceed to the next step.</p>
            </div>
        </div>
    <?php else: ?>
        <form id="addonsForm" class="addons-form">
            <div class="addons-section">
                <div class="addons-grid">
                    <?php foreach ($available_addons as $addon_key => $addon): 
                        $is_free_addon = ($is_polo_ordered && $addon_key === 'afterpart_package_0');
                        $is_selected = in_array($addon_key, $selected_addons);
                    ?>
                        <div class="addon-option <?php echo $is_selected ? 'selected' : ''; ?> <?php echo $is_free_addon ? 'free-addon' : ''; ?>" 
                             <?php echo !$is_free_addon ? 'onclick="toggleAddon(\'' . $addon_key . '\', this)"' : ''; ?>
                             data-addon-key="<?php echo esc_attr($addon_key); ?>"
                             data-addon-price="<?php echo esc_attr($addon['price']); ?>"
                             data-is-free="<?php echo $is_free_addon ? 'true' : 'false'; ?>">
                            
                            <input type="checkbox" 
                                   name="selected_addons[]" 
                                   value="<?php echo $addon_key; ?>" 
                                   id="addon_<?php echo $addon_key; ?>"
                                   <?php echo $is_selected ? 'checked' : ''; ?>
                                   <?php echo $is_free_addon ? 'readonly' : ''; ?>>
                            <div class="addon-header">
                                <div class="addon-info">
                                    <div class="addon-name"><?php echo esc_html($addon['name']); ?></div>
                                    <div class="addon-price <?php echo $is_free_addon ? 'free-price' : ''; ?>">
                                        <?php if ($is_free_addon): ?>
                                            🎁 FREE
                                        <?php else: ?>
                                            <?php echo esc_html($addon['formatted_price']); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="selection-indicator">
                                    <span class="checkmark">✓</span>
                                </div>
                            </div>
                            
                            <?php if (!empty($addon['description'])): ?>
                                <div class="addon-includes">
                                    <div class="includes-label">INCLUDES:</div>
                                    <div class="includes-content"><?php echo esc_html($addon['description']); ?></div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="selection-hint">
                                <?php if ($is_free_addon): ?>
                                    <span class="free-text">Included with your polo order!</span>
                                <?php else: ?>
                                    <span class="select-text"><?php echo $is_polo_ordered ? 'Click to add more drinks' : 'Click to select (only one)'; ?></span>
                                    <span class="deselect-text">Click again to deselect</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- All Afterparty Packages Include Section -->
            <div class="packages-info-section">
                <h3 class="packages-info-title">All Afterparty Packages Include:</h3>
                <ul class="common-features-list">
                        <li>Networking with Sri Lankan Esports crowd from 2007-2018</li>
                        <li>DJ and live entertainment</li>
                        <li>After hours gaming station access</li>
                        <li>5.00 PM - 11.00 PM</li>
                </ul>
            </div>

            <div class="optional-notice">
                <div class="info-box">
                    <?php if ($is_polo_ordered): ?>
                        <h4>🎁 Your Polo Order Benefits</h4>
                        <p><strong>Package 0 is FREE</strong> - included automatically with your polo order! You can add Package 01 or Package 02 if you want more drinks, or proceed to the next step with your free package.</p>
                    <?php else: ?>
                        <h4>⚡ Optional Selection</h4>
                        <p>Add-ons are completely optional. You can select one add-on or proceed to the next step without selecting any add-ons if you prefer.</p>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    <?php endif; ?>
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
    margin-bottom: 35px;
}

.packages-info-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 30px !important;
    border: 1px solid #e9ecef;
    max-width: 800px;
    margin: 0 auto;
}

.packages-info-title {
    font-size: 18px;
    font-weight: 600;
    color: #000000;
    margin-bottom: 20px;
    text-align: center;
    margin-top: 0;
}

ul.common-features-list {
    list-style: none;
    padding: 0;
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 20px;
    padding: 0;
}

.common-features-list li{
    color: #555;
    font-size: 15px;
    font-weight: 500;
}

.common-features-list li::before {
    content: '•';
    margin-right: 8px;
    color: #f9c613;
    font-weight: bold;
}

.common-features-list li::marker {
    display: none;
    color: transparent;
}

.packages-features {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}

.packages-features-line2 {
    display: flex;
    align-items: center;
    text-align: center;
    justify-content: center;
}

.feature-text {
    font-size: 14px;
    color: #333;
    font-weight: 500;
}

.feature-separator {
    font-size: 14px;
    color: #666;
    font-weight: bold;
}

.addons-form {
    max-width: 900px;
    margin: 0 auto;
}

.addons-section {
    margin-bottom: 25px;
}

.addons-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 20px;
}

/* Handle 1 addon */
.addons-grid:has(.addon-option:nth-child(1):nth-last-child(1)) {
    grid-template-columns: 1fr;
    max-width: 350px;
    margin: 0 auto;
}

/* Handle 2 addons */
.addons-grid:has(.addon-option:nth-child(2):nth-last-child(1)) {
    grid-template-columns: repeat(2, 1fr);
    max-width: 600px;
    margin: 0 auto;
}

.addon-option {
    border: 2px solid #e9ecef;
    border-radius: 15px;
    padding: 20px;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    background: white;
    overflow: hidden;
}

.addon-option::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #f9c613 0%, #ffdd44 100%);
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.addon-option:hover {
    border-color: #f9c613;
    box-shadow: 0 10px 30px rgba(249, 198, 19, 0.15);
    transform: translateY(-2px);
}

.addon-option:hover::before {
    transform: scaleX(1);
}

.addon-option.selected {
    border-color: #f9c613;
    background: linear-gradient(135deg, #fff9e6 0%, #fffbf0 100%);
    box-shadow: 0 10px 30px rgba(249, 198, 19, 0.2);
}

.addon-option.selected::before {
    transform: scaleX(1);
}

/* Free addon styling */
.addon-option.free-addon {
    border-color: #10B981;
    background: linear-gradient(135deg, #f0fdf4 0%, #f7fee7 100%);
    position: relative;
    pointer-events: none; /* Disable clicking */
}

.addon-option.free-addon::before {
    background: linear-gradient(90deg, #10B981 0%, #059669 100%);
    transform: scaleX(1);
}

.free-ribbon {
    position: absolute;
    top: 15px;
    right: -8px;
    background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
    color: white;
    padding: 4px 12px;
    font-size: 11px;
    font-weight: 700;
    border-radius: 4px;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
    transform: rotate(8deg);
    z-index: 10;
}

.addon-price.free-price {
    background: linear-gradient(135deg, #10B981 0%, #059669 100%);
    color: white;
    padding: 8px 16px;
    border-radius: 25px;
    font-size: 18px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    display: inline-block;
}

.selection-hint .free-text {
    color: #10B981;
    font-weight: 600;
    font-style: normal;
}

.addon-option input[type="checkbox"] {
    display: none;
}

.addon-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.addon-info {
    flex: 1;
}

.addon-name {
    font-size: 18px;
    font-weight: 700;
    color: #000000;
    margin-bottom: 5px;
}

.addon-price {
    font-size: 22px;
    font-weight: 700;
    color: #000000;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
}

.selection-indicator {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.addon-option.selected .selection-indicator {
    background: #f9c613;
    color: #000000;
    transform: scale(1.1);
}

.checkmark {
    font-size: 18px;
    color: #fff;
    font-weight: bold;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.addon-option.selected .checkmark {
    opacity: 1;
}

.addon-includes {
    background: linear-gradient(135deg, #f9f9f8, #fdf0c0);
    border: 1px solid #f9e6ab;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
}

.includes-label {
    font-size: 12px;
    font-weight: 600;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 5px;
}

.includes-content {
    font-size: 16px;
    color: #000000;
    font-weight: 600;
}

.selection-hint {
    font-size: 13px;
    color: #666;
    font-style: italic;
    text-align: center;
}

.addon-option.selected .select-text {
    display: none;
}

.addon-option:not(.selected) .deselect-text {
    display: none;
}

.optional-notice {
    margin-top: 30px;
}

.info-box {
    background: #f0f9ff;
    border-left: 4px solid #2196f3;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
    position: relative;
}

.info-box h4 {
    margin: 0 0 10px 0;
    color: #1565c0;
    font-size: 18px;
}

.info-box p {
    margin: 0;
    color: #424242;
    font-size: 14px;
    line-height: 1.5;
}

.no-addons-message {
    max-width: 600px;
    margin: 0 auto;
}

.no-addons-message .info-box {
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
    border-color: #ffc107;
}

.no-addons-message .info-box h4 {
    color: #856404;
}

.no-addons-message .info-box p {
    color: #856404;
}

/* Responsive design */
@media (max-width: 768px) {
    .addons-grid {
        grid-template-columns: 1fr !important;
        gap: 12px;
    }
    
    .common-features-list {
        flex-direction: column;
        gap: 8px !important;
    }

    .addon-option {
        padding: 12px;
    }
    
    .addon-header {
        margin-bottom: 10px;
    }
    
    .addon-name {
        font-size: 15px;
        margin-bottom: 3px;
    }
    
    .addon-price {
        font-size: 16px;
    }
    
    .selection-indicator {
        width: 28px;
        height: 28px;
    }
    
    .checkmark {
        font-size: 16px;
    }
    
    .addon-includes {
        padding: 10px;
        margin-bottom: 10px;
    }
    
    .includes-label {
        font-size: 11px;
        margin-bottom: 3px;
    }
    
    .includes-content {
        font-size: 14px;
    }
    
    .selection-hint {
        font-size: 12px;
    }
    
    .step-title {
        font-size: 24px;
    }
    
    .packages-features {
        gap: 6px;
    }
    
    .feature-text {
        font-size: 13px;
    }

     .step-description {
        font-size: 14px;
    }

    .packages-info-title {
        font-size: 16px;
    }

    .common-features-list li {
        font-size: 12px;
    }
    
    .free-ribbon {
        top: 10px;
        right: -6px;
        padding: 3px 8px;
        font-size: 10px;
    }
    
    .addon-price.free-price {
        padding: 6px 12px;
        font-size: 14px;
    }
}

@media (max-width: 480px) {
    .addon-header {
        flex-direction: column;
        gap: 10px;
    }
    
    .selection-indicator {
        align-self: flex-end;
    }
    
    .packages-info-section {
        padding: 20px;
    }
    
    .packages-features {
        flex-direction: column;
        gap: 4px;
        text-align: center;
    }
    
    .packages-features-line2 {
        margin-top: 4px;
    }
}
</style>

<script>
function toggleAddon(addonKey, element) {
    const checkbox = element.querySelector('input[type="checkbox"]');
    const allOptions = document.querySelectorAll('.addon-option');
    const isFreeAddon = element.getAttribute('data-is-free') === 'true';
    
    // Don't allow interaction with free addons
    if (isFreeAddon) {
        return;
    }
    
    // Check if we're in polo_ordered mode (has any free addon)
    const hasFreeAddon = document.querySelector('.addon-option[data-is-free="true"]') !== null;
    
    // If this addon is already selected, deselect it
    if (element.classList.contains('selected')) {
        element.classList.remove('selected');
        checkbox.checked = false;
        return;
    }
    
    if (hasFreeAddon) {
        // polo_ordered mode: Allow free addon + one paid addon
        // Deselect other paid addons (but keep free addon selected)
        allOptions.forEach(option => {
            const isOtherFreeAddon = option.getAttribute('data-is-free') === 'true';
            if (option !== element && !isOtherFreeAddon) {
                option.classList.remove('selected');
                const otherCheckbox = option.querySelector('input[type="checkbox"]');
                if (otherCheckbox) {
                    otherCheckbox.checked = false;
                }
            }
        });
    } else {
        // Normal mode: Deselect all other addons (only one allowed)
        allOptions.forEach(option => {
            if (option !== element) {
                option.classList.remove('selected');
                const otherCheckbox = option.querySelector('input[type="checkbox"]');
                if (otherCheckbox) {
                    otherCheckbox.checked = false;
                }
            }
        });
    }
    
    // Select this addon
    element.classList.add('selected');
    checkbox.checked = true;
    
    // Add a subtle animation
    element.style.transform = 'scale(1.02)';
    setTimeout(() => {
        element.style.transform = '';
    }, 200);
}

// Prevent form submission when clicking addon options
document.querySelectorAll('.addon-option').forEach(option => {
    option.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
    });
});

// Add keyboard navigation
document.addEventListener('keydown', function(e) {
    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        const options = document.querySelectorAll('.addon-option');
        const currentSelected = document.querySelector('.addon-option.selected');
        
        if (options.length === 0) return;
        
        let currentIndex = Array.from(options).indexOf(currentSelected);
        
        if (e.key === 'ArrowDown') {
            currentIndex = (currentIndex + 1) % options.length;
        } else {
            currentIndex = currentIndex <= 0 ? options.length - 1 : currentIndex - 1;
        }
        
        options[currentIndex].focus();
    }
    
    if (e.key === 'Enter' || e.key === ' ') {
        const focusedOption = document.activeElement;
        if (focusedOption && focusedOption.classList.contains('addon-option')) {
            e.preventDefault();
            const addonKey = focusedOption.getAttribute('data-addon-key');
            toggleAddon(addonKey, focusedOption);
        }
    }
});

// Make addon options focusable for keyboard navigation
document.querySelectorAll('.addon-option').forEach(option => {
    option.setAttribute('tabindex', '0');
    option.setAttribute('role', 'checkbox');
    option.setAttribute('aria-label', 'Toggle addon selection');
});

// Update price display in real-time (if there's a price display elsewhere)
document.addEventListener('DOMContentLoaded', function() {
    const selectedAddon = document.querySelector('.addon-option.selected');
    if (selectedAddon) {
        const price = selectedAddon.getAttribute('data-addon-price');
        // Trigger price update event for other components
        document.dispatchEvent(new CustomEvent('addon-price-changed', { 
            detail: { price: parseFloat(price) || 0 } 
        }));
    }
});

// Step 3 specific validation function
function validateCurrentStep() {
    // Get selected add-ons
    const selectedAddons = [];
    const checkedBoxes = document.querySelectorAll('input[name="selected_addons[]"]:checked');
    
    checkedBoxes.forEach(checkbox => {
        selectedAddons.push(checkbox.value);
    });
    
    // Add-ons are optional, so no validation errors
    // But we still need to save the data
    
    // Save data via AJAX and then proceed to next step
    saveStepData(3, {
        selected_addons: selectedAddons
    }, function() {
        // On success, proceed to next step
        proceedToNextStep(<?php echo $wizard->get_next_step(3); ?>);
    });
    
    return false; // Prevent default redirect, let AJAX handle it
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
</script> 