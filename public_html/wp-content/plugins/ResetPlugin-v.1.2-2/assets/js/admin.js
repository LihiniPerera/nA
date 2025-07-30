/* RESET Event Ticketing System - Admin JavaScript */
jQuery(document).ready(function($) {
    
        // Check if resetAdminAjax is properly loaded
    if (typeof resetAdminAjax === 'undefined') {
        if (typeof ajaxurl !== 'undefined') {
            window.resetAdminAjax = { ajaxurl: ajaxurl, nonce: '' };
        } else {
            console.error('RESET Plugin: AJAX configuration not available. AJAX calls will fail.');
            return;
        }
    }
    
    if (!resetAdminAjax.ajaxurl || !resetAdminAjax.nonce) {
        console.error('RESET Plugin: Missing ajaxurl or nonce', resetAdminAjax);
        return;
    }
    
    // Initialize admin functionality
    initializeAdmin();
    
    function initializeAdmin() {
        // Auto-refresh dashboard data every 30 seconds
        if ($('#reset-dashboard').length > 0) {
            setInterval(refreshDashboardData, 30000);
        }
        
        // Initialize modals
        initializeModals();
        
        // Initialize search functionality
        initializeSearch();
        
        // Initialize token generation
        initializeTokenGeneration();
        
        // Initialize token cancellation
        initializeTokenCancellation();
        
        // Initialize mark as sent
        initializeMarkAsSent();
        
        // Initialize inline editing for sent to column
        initializeInlineEditing();
        
        // Initialize token copy functionality
        initializeTokenCopy();
    }
    
    function initializeModals() {
        // Close modal when clicking X
        $('.reset-modal-close').on('click', function() {
            $(this).closest('.reset-modal').hide();
        });
        
        // Close modal when clicking outside
        $('.reset-modal').on('click', function(e) {
            if (e.target === this) {
                $(this).hide();
            }
        });
    }
    
    function initializeSearch() {
        // Token search functionality
        $('#reset-search-tokens').on('click', function() {
            const searchTerm = $('#token-search').val();
            const status = $('#token-status-filter').val();
            const type = $('#token-type-filter').val();
            
            searchTokens(searchTerm, status, type);
        });
        
        // Enter key search
        $('#token-search').on('keypress', function(e) {
            if (e.which === 13) {
                $('#reset-search-tokens').click();
            }
        });
    }
    
    function initializeTokenGeneration() {
        $('#reset-generate-tokens').on('click', function() {
            const count = $('#token-count').val();
            
            if (!count || count < 1 || count > 100) {
                showMessage('Please enter a valid number between 1 and 100.', 'error');
                return;
            }
            
            generateTokens(count);
        });
    }
    
    function initializeTokenCancellation() {
        // Individual token cancellation
        $(document).on('click', '.cancel-token', function() {
            const tokenId = $(this).data('token-id');
            const tokenCode = $(this).data('token-code');
            
            showCancellationModal(tokenId, tokenCode);
        });
        
        // Bulk cancellation
        $('#bulk-cancel-tokens').on('click', function() {
            const selectedTokens = $('.token-checkbox:checked');
            
            if (selectedTokens.length === 0) {
                showMessage('Please select tokens to cancel.', 'error');
                return;
            }
            
            const tokenIds = [];
            selectedTokens.each(function() {
                tokenIds.push($(this).val());
            });
            
            showBulkCancellationModal(tokenIds);
        });
        
        // Confirm cancellation
        $('#confirm-cancel-token').on('click', function() {
            const tokenId = $(this).data('token-id');
            const reason = $('#cancellation-reason').val();
            
            cancelToken(tokenId, reason);
        });
    }
    
    function initializeMarkAsSent() {
        // Individual mark as sent
        $(document).on('click', '.mark-sent-btn', function() {
            const tokenId = $(this).data('token-id');
            const tokenCode = $(this).data('token-code');
            
            showMarkAsSentModal(tokenId, tokenCode);
        });
        
        // Handle bulk actions dropdown for mark as sent
        $('#bulk-cancel-btn').on('click', function() {
            const bulkAction = $('#bulk-action').val();
            const selectedTokens = $('.token-checkbox:checked');
            
            if (selectedTokens.length === 0) {
                showMessage('Please select tokens first.', 'error');
                return;
            }
            
            const tokenIds = [];
            selectedTokens.each(function() {
                tokenIds.push($(this).val());
            });
            
            if (bulkAction === 'mark_sent') {
                showBulkMarkAsSentModal(tokenIds);
            } else if (bulkAction === 'cancel') {
                showBulkCancellationModal(tokenIds);
            }
        });
        
        // Confirm mark as sent
        $('#confirm-mark-sent').on('click', function() {
            const tokenId = $('#mark-sent-token-id').val();
            const recipientName = $('#recipient_name').val();
            const recipientEmail = $('#recipient_email').val();
            const notes = $('#sent_notes').val();
            
                    if (!recipientName) {
            showMessage('Please fill in recipient name.', 'error');
            return;
        }
            
            markTokenAsSent(tokenId, recipientName, recipientEmail, notes);
        });
        
        // Close modal when clicking close button
        $('.cancel-modal-btn').on('click', function() {
            $('.reset-modal').hide();
            // Clear form
            $('#mark-sent-form')[0].reset();
        });
    }
    
    function refreshDashboardData() {
        $.ajax({
            url: resetAdminAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'reset_get_dashboard_data',
                nonce: resetAdminAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateDashboardData(response.data);
                }
            }
        });
    }
    
    function updateDashboardData(data) {
        // Update statistics
        if (data.dashboard) {
            $('#total-tokens').text(data.dashboard.total_tokens);
            $('#used-tokens').text(data.dashboard.used_tokens);
            $('#active-tokens').text(data.dashboard.active_tokens);
            $('#cancelled-tokens').text(data.dashboard.cancelled_tokens);
            $('#total-revenue').text(data.dashboard.total_revenue);
            $('#completed-purchases').text(data.dashboard.completed_purchases);
        }
        
        // Update capacity info
        if (data.capacity) {
            $('#current-capacity').text(data.capacity.current_sales);
            $('#target-capacity').text(data.capacity.target_capacity);
            
            // Update progress bar if exists
            const progressPercentage = (data.capacity.current_sales / data.capacity.target_capacity) * 100;
            $('#capacity-progress').css('width', progressPercentage + '%');
        }
    }
    
    function searchTokens(searchTerm, status, type) {
        showLoading('Searching tokens...');
        
        $.ajax({
            url: resetAdminAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'reset_search_tokens',
                search_term: searchTerm,
                status: status,
                type: type,
                nonce: resetAdminAjax.nonce
            },
            success: function(response) {
                hideLoading();
                
                if (response.success) {
                    updateTokensList(response.data.tokens);
                    updatePagination(response.data.total, response.data.count);
                } else {
                    showMessage('Search failed. Please try again.', 'error');
                }
            },
            error: function() {
                hideLoading();
                showMessage('An error occurred during search.', 'error');
            }
        });
    }
    
    function generateTokens(count) {
        showLoading('Generating tokens...');
        
        $.ajax({
            url: resetAdminAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'reset_generate_tokens',
                count: count,
                nonce: resetAdminAjax.nonce
            },
            success: function(response) {
                hideLoading();
                
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    
                    // Refresh token list
                    if (typeof refreshTokensList === 'function') {
                        refreshTokensList();
                    }
                    
                    // Clear form
                    $('#token-count').val('');
                } else {
                    showMessage(response.data.message, 'error');
                }
            },
            error: function() {
                hideLoading();
                showMessage('An error occurred while generating tokens.', 'error');
            }
        });
    }
    
    function showCancellationModal(tokenId, tokenCode) {
        $('#cancellation-modal').show();
        $('#cancel-token-code').text(tokenCode);
        $('#confirm-cancel-token').data('token-id', tokenId);
        $('#cancellation-reason').val('');
    }
    
    function showBulkCancellationModal(tokenIds) {
        $('#bulk-cancellation-modal').show();
        $('#bulk-cancel-count').text(tokenIds.length);
        $('#confirm-bulk-cancel').data('token-ids', tokenIds);
        $('#bulk-cancellation-reason').val('');
    }
    
    function cancelToken(tokenId, reason) {
        showLoading('Cancelling token...');
        
        $.ajax({
            url: resetAdminAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'reset_cancel_token',
                token_id: tokenId,
                reason: reason,
                nonce: resetAdminAjax.nonce
            },
            success: function(response) {
                hideLoading();
                $('#cancellation-modal').hide();
                
                if (response.success) {
                    showMessage('Token cancelled successfully.', 'success');
                    
                    // Refresh token list
                    if (typeof refreshTokensList === 'function') {
                        refreshTokensList();
                    }
                } else {
                    showMessage(response.data.message, 'error');
                }
            },
            error: function() {
                hideLoading();
                $('#cancellation-modal').hide();
                showMessage('An error occurred while cancelling token.', 'error');
            }
        });
    }
    
    function showMarkAsSentModal(tokenId, tokenCode) {
        $('#mark-sent-modal').show();
        $('#mark-sent-token-code').text(tokenCode);
        $('#mark-sent-token-id').val(tokenId);
        $('#mark-sent-form')[0].reset();
    }
    
    function showBulkMarkAsSentModal(tokenIds) {
        // For bulk operations, we'll show a simpler modal asking for recipient info
        // that will be applied to all selected tokens
        $('#mark-sent-modal').show();
        $('#mark-sent-token-code').text(tokenIds.length + ' tokens');
        $('#mark-sent-token-id').val(tokenIds.join(','));
        $('#mark-sent-form')[0].reset();
    }
    
    function markTokenAsSent(tokenId, recipientName, recipientEmail, notes) {
        showLoading('Marking token as sent...');
        
        // Handle bulk operation (comma-separated IDs)
        const isBulk = tokenId.includes(',');
        
        $.ajax({
            url: resetAdminAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'reset_mark_token_as_sent',
                token_id: tokenId,
                recipient_name: recipientName,
                recipient_email: recipientEmail,
                notes: notes,
                nonce: resetAdminAjax.nonce
            },
            success: function(response) {
                hideLoading();
                $('#mark-sent-modal').hide();
                
                if (response.success) {
                    showMessage(isBulk ? 'Tokens marked as sent successfully.' : 'Token marked as sent successfully.', 'success');
                    
                    // Refresh the page to show updated data
                    location.reload();
                } else {
                    showMessage(response.data, 'error');
                }
            },
            error: function() {
                hideLoading();
                $('#mark-sent-modal').hide();
                showMessage('An error occurred while marking token as sent.', 'error');
            }
        });
    }
    
    function updateTokensList(tokens) {
        const tbody = $('#tokens-table tbody');
        tbody.empty();
        
        tokens.forEach(function(token) {
            const row = $('<tr>').append(
                $('<td>').append('<input type="checkbox" class="token-checkbox" value="' + token.id + '">'),
                $('<td>').text(token.token_code),
                $('<td>').text(token.token_type),
                $('<td>').text(token.status),
                $('<td>').text(token.created_at),
                $('<td>').append('<button class="reset-button danger cancel-token" data-token-id="' + token.id + '" data-token-code="' + token.token_code + '">Cancel</button>')
            );
            tbody.append(row);
        });
    }
    
    function updatePagination(total, current) {
        // Update pagination info
        $('#pagination-info').text('Showing ' + current + ' of ' + total + ' tokens');
    }
    
    function showMessage(message, type) {
        const messageHtml = '<div class="reset-message ' + type + '">' + message + '</div>';
        $('#reset-admin-messages').html(messageHtml);
        
        // Auto-hide success messages after 5 seconds
        if (type === 'success') {
            setTimeout(function() {
                $('#reset-admin-messages').empty();
            }, 5000);
        }
    }
    
    function showLoading(message) {
        const loadingHtml = '<div class="reset-loading">' + message + '</div>';
        $('#reset-admin-messages').html(loadingHtml);
    }
    
    function hideLoading() {
        $('#reset-admin-messages').empty();
    }
    
    function initializeInlineEditing() {
        // Handle input field for new entries
        $(document).on('keypress blur', '.sent-to-input', function(e) {
            // Only save on Enter key press or blur event
            if (e.type === 'keypress' && e.which !== 13) {
                return;
            }
            
            const $input = $(this);
            const tokenId = $input.data('token-id');
            const name = $input.val().trim();
            const originalValue = $input.data('original-value');
            
            // Prevent multiple saves - check if already saving
            if ($input.hasClass('saving') || $input.data('is-saving')) {
                return;
            }
            
            // Only save if name is not empty and different from original
            if (name && name !== originalValue) {
                // Mark as saving to prevent duplicates
                $input.data('is-saving', true);
                
                // For Enter key, prevent blur event from also triggering
                if (e.type === 'keypress') {
                    setTimeout(function() {
                        $input.off('blur.temp');
                        $input.on('blur.temp', function() {
                            // Do nothing - already saved by Enter
                        });
                        setTimeout(function() {
                            $input.off('blur.temp');
                        }, 100);
                    }, 0);
                }
                
                saveSentToName(tokenId, name, $input);
            }
        });
        
        // Handle click on sent display area to edit
        $(document).on('click', '.sent-display', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $sentDisplay = $(this);
            const tokenId = $sentDisplay.data('token-id');
            const currentName = $sentDisplay.find('strong').text().trim();
            
            // Replace display with input
            const inputHtml = `
                <input type="text" 
                       class="sent-to-input editing" 
                       data-token-id="${tokenId}"
                       value="${escapeHtml(currentName)}"
                       data-original-value="${escapeHtml(currentName)}"
                       style="width: 100%;">
            `;
            
            $sentDisplay.closest('.sent-to-column').html(inputHtml);
            
            // Focus the input and select all text
            const $newInput = $sentDisplay.closest('.sent-to-column').find('.sent-to-input');
            $newInput.focus().select();
        });
        
        // Handle Escape key to cancel editing
        $(document).on('keydown', '.sent-to-input.editing', function(e) {
            if (e.which === 27) { // Escape key
                const originalValue = $(this).data('original-value');
                if (originalValue) {
                    // Restore original display
                    location.reload();
                } else {
                    // Just clear the input
                    $(this).val('').blur();
                }
            }
        });
    }
    
    function saveSentToName(tokenId, name, $input) {
        // Show saving state
        $input.addClass('saving').prop('disabled', true);
        
        // Add loading indicator (only if not already present)
        let $loadingIndicator = $input.siblings('.saving-indicator');
        if ($loadingIndicator.length === 0) {
            $loadingIndicator = $('<span class="saving-indicator"> Saving...</span>');
            $input.after($loadingIndicator);
        }
        
        $.ajax({
            url: resetAdminAjax.ajaxurl,
            method: 'POST',
            data: {
                action: 'reset_quick_mark_sent',
                token_id: tokenId,
                recipient_name: name,
                nonce: resetAdminAjax.nonce
            },
            success: function(response) {
                $loadingIndicator.remove();
                
                // Clear saving flags
                $input.removeClass('saving').prop('disabled', false).removeData('is-saving');
                
                if (response.success) {
                    // Transform to display mode
                    const displayHtml = `
                        <div class="sent-display" data-token-id="${tokenId}" title="Click to edit">
                            <div class="sent-info">
                                <strong>${escapeHtml(name)}</strong>
                            </div>
                        </div>
                    `;
                    $input.closest('.sent-to-column').html(displayHtml);
                    
                    // Update Actions column - remove the "Mark as Sent" button
                    const $row = $input.closest('tr');
                    
                    // Try multiple selectors to find the mark as sent button
                    let $markSentBtn = $row.find('.mark-sent-btn');
                    
                    if ($markSentBtn.length === 0) {
                        // Try alternative selectors
                        $markSentBtn = $row.find('button[data-token-id="' + tokenId + '"].mark-sent');
                    }
                    
                    if ($markSentBtn.length === 0) {
                        // Try finding by button content
                        $markSentBtn = $row.find('button').filter(function() {
                            return $(this).text().trim() === '✓' || $(this).hasClass('mark-sent');
                        });
                    }
                    
                    if ($markSentBtn.length > 0) {
                        $markSentBtn.fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        // If we can't find the button, refresh just this row
                        refreshTokenRow(tokenId);
                    }
                    
                    // Show success message briefly
                    showInlineMessage($input.closest('.sent-to-column'), 'Saved!', 'success');
                } else {
                    showInlineMessage($input.closest('.sent-to-column'), response.data || 'Failed to save', 'error');
                }
            },
            error: function(xhr, status, error) {
                $loadingIndicator.remove();
                
                // Clear saving flags
                $input.removeClass('saving').prop('disabled', false).removeData('is-saving');
                
                console.error('RESET Plugin: AJAX Error', { xhr: xhr, status: status, error: error, url: resetAdminAjax.ajaxurl });
                showInlineMessage($input.closest('.sent-to-column'), 'Network error. Please try again.', 'error');
            }
        });
    }
    
    function showInlineMessage($container, message, type) {
        const $message = $(`<div class="inline-message ${type}">${message}</div>`);
        $container.append($message);
        
        // Auto-hide after 2 seconds
        setTimeout(function() {
            $message.fadeOut(function() {
                $message.remove();
            });
        }, 2000);
    }
    
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    function refreshTokenRow(tokenId) {
        // Find the row by token ID
        const $row = $('tr').find('[data-token-id="' + tokenId + '"]').closest('tr');
        
        if ($row.length === 0) {
            location.reload();
            return;
        }
        
        // Add a loading state to the row
        $row.addClass('refreshing').css('opacity', '0.6');
        
        // Make AJAX call to get updated row data
        $.ajax({
            url: resetAdminAjax.ajaxurl,
            method: 'POST',
            data: {
                action: 'reset_get_token_row',
                token_id: tokenId,
                nonce: resetAdminAjax.nonce
            },
            success: function(response) {
                if (response.success && response.data.html) {
                    // Replace the entire row with updated HTML
                    const $newRow = $(response.data.html);
                    $row.replaceWith($newRow);
                    
                    // Note: No need to re-initialize inline editing since we use 
                    // event delegation $(document).on() which automatically works for new elements
                } else {
                    location.reload();
                }
            },
            error: function() {
                location.reload();
            }
        });
    }
    
    function initializeTokenCopy() {
        // Use event delegation for dynamically added token codes
        $(document).on('click', '.token-code-copy', function(e) {
            e.preventDefault();
            
            const $tokenCode = $(this);
            const tokenText = $tokenCode.data('token');
            
            // Copy to clipboard
            copyToClipboard(tokenText).then(function(success) {
                if (success) {
                    showCopyFeedback($tokenCode, 'Copied!');
                    $tokenCode.addClass('copied');
                    
                    // Remove copied class after animation
                    setTimeout(function() {
                        $tokenCode.removeClass('copied');
                    }, 1000);
                } else {
                    showCopyFeedback($tokenCode, 'Copy failed');
                }
            });
        });
    }
    
    function copyToClipboard(text) {
        return new Promise(function(resolve) {
            // Modern API
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function() {
                    resolve(true);
                }).catch(function() {
                    resolve(fallbackCopyTextToClipboard(text));
                });
            } else {
                // Fallback
                resolve(fallbackCopyTextToClipboard(text));
            }
        });
    }
    
    function fallbackCopyTextToClipboard(text) {
        const textArea = document.createElement("textarea");
        textArea.value = text;
        
        // Avoid scrolling to bottom
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.position = "fixed";
        textArea.style.opacity = "0";
        
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            const successful = document.execCommand('copy');
            document.body.removeChild(textArea);
            return successful;
        } catch (err) {
            document.body.removeChild(textArea);
            return false;
        }
    }
    
    function showCopyFeedback($element, message) {
        // Remove any existing feedback
        $element.find('.copy-feedback').remove();
        
        // Create feedback element
        const $feedback = $('<div class="copy-feedback">' + escapeHtml(message) + '</div>');
        $element.append($feedback);
        
        // Show feedback
        setTimeout(function() {
            $feedback.addClass('show');
        }, 10);
        
        // Hide and remove feedback
        setTimeout(function() {
            $feedback.removeClass('show');
            setTimeout(function() {
                $feedback.remove();
            }, 300);
        }, 1500);
    }
    
}); 