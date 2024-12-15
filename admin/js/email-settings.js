jQuery(document).ready(function($) {
    // Cache selectors
    const $form = $('form');
    const $templateFields = $('.template-field-wrapper');

    // Handle template ID input changes
    $templateFields.on('input', 'input', function() {
        const $input = $(this);
        const $testButton = $input.siblings('.test-template');
        const $spinner = $input.siblings('.spinner');
        
        // Enable/disable test button based on input value
        $testButton.prop('disabled', !$input.val().trim());
    });

    // Initialize button states
    $templateFields.find('input').trigger('input');

    // Handle test email button clicks
    $templateFields.on('click', '.test-template', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const $spinner = $button.siblings('.spinner');
        const $input = $button.siblings('input');
        const templateId = $input.val().trim();
        
        // Validate template ID
        if (!templateId) {
            alert(wizardEmailSettings.strings.testError);
            return;
        }
        
        // Disable button and show spinner
        $button.prop('disabled', true);
        $spinner.addClass('is-active');

        // Send test email
        $.ajax({
            url: wizardEmailSettings.ajaxUrl,
            type: 'POST',
            data: {
                action: 'test_email_template',
                template_type: $button.data('template-type'),
                nonce: wizardEmailSettings.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                } else {
                    let errorMessage = response.data.message;
                    
                    // Add debug information if available
                    if (response.data.debug) {
                        console.error('Email test failed:', response.data.debug);
                        errorMessage += '\n\nDebug information:';
                        Object.entries(response.data.debug).forEach(([key, value]) => {
                            errorMessage += `\n${key}: ${value}`;
                        });
                    }
                    
                    alert(errorMessage);
                }
            },
            error: function(xhr, status, error) {
                console.error('Test email request failed:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                
                let errorMessage = wizardEmailSettings.strings.testError;
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.data && response.data.message) {
                        errorMessage = response.data.message;
                    }
                } catch (e) {
                    console.error('Failed to parse error response:', e);
                }
                
                alert(errorMessage);
            },
            complete: function() {
                // Re-enable button and hide spinner
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    });

    // Prevent accidental form submission when clicking test buttons
    $form.on('submit', function() {
        // Disable test buttons during form submission to prevent double-sending
        $('.test-template').prop('disabled', true);
    });
}); 