/**
 * Kre8iv AI Blogger - Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // Generate Post Now button
        $('#kaib-generate-now').on('click', function() {
            var $btn = $(this);
            var $status = $('#kaib-generate-status');
            
            // Disable button and show loading
            $btn.prop('disabled', true).text('Generating...');
            $status
                .removeClass('kaib-success kaib-error')
                .addClass('kaib-loading')
                .html('<span class="dashicons dashicons-update spin"></span> Generating your marketing post... This may take 1-2 minutes.')
                .show();

            $.ajax({
                url: kaib_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'kaib_generate_now',
                    nonce: kaib_ajax.nonce
                },
                timeout: 180000, // 3 minute timeout
                success: function(response) {
                    if (response.success) {
                        $status
                            .removeClass('kaib-loading')
                            .addClass('kaib-success')
                            .html(
                                '<span class="dashicons dashicons-yes-alt"></span> ' +
                                '<strong>Success!</strong> Post created: "' + response.data.title + '" ' +
                                '<a href="' + response.data.edit_url + '" class="button button-small">Edit Post</a> ' +
                                '<a href="' + response.data.view_url + '" class="button button-small" target="_blank">View Post</a>'
                            );
                        
                        // Reload page after 3 seconds to update stats
                        setTimeout(function() {
                            location.reload();
                        }, 3000);
                    } else {
                        $status
                            .removeClass('kaib-loading')
                            .addClass('kaib-error')
                            .html('<span class="dashicons dashicons-warning"></span> <strong>Error:</strong> ' + response.data);
                    }
                },
                error: function(xhr, status, error) {
                    var errorMsg = 'Request failed. ';
                    if (status === 'timeout') {
                        errorMsg = 'Request timed out. The post may still be generating - please check your Posts page.';
                    } else {
                        errorMsg += error || 'Please try again.';
                    }
                    
                    $status
                        .removeClass('kaib-loading')
                        .addClass('kaib-error')
                        .html('<span class="dashicons dashicons-warning"></span> ' + errorMsg);
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-admin-post"></span> Generate Post Now');
                }
            });
        });

        // Generate Single Backdated Post
        $('#kaib-generate-backdated').on('click', function() {
            var $btn = $(this);
            var $status = $('#kaib-backfill-status');
            var backdate = $('#kaib-backdate-date').val();
            
            console.log('Backdated post generation requested for date:', backdate);
            
            if (!backdate) {
                alert('Please select a date for the backdated post.');
                return;
            }
            
            $btn.prop('disabled', true).text('Generating...');
            $status
                .removeClass('kaib-success kaib-error')
                .addClass('kaib-loading')
                .html('<span class="dashicons dashicons-update spin"></span> Generating backdated post for ' + backdate + '... This may take 1-2 minutes.')
                .show();

            console.log('Sending AJAX request:', {
                action: 'kaib_generate_single_backdate',
                backdate: backdate
            });

            $.ajax({
                url: kaib_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'kaib_generate_single_backdate',
                    nonce: kaib_ajax.nonce,
                    backdate: backdate
                },
                timeout: 180000,
                success: function(response) {
                    console.log('AJAX response:', response);
                    if (response.success) {
                        $status
                            .removeClass('kaib-loading')
                            .addClass('kaib-success')
                            .html(
                                '<span class="dashicons dashicons-yes-alt"></span> ' +
                                '<strong>Success!</strong> Backdated post created: "' + response.data.title + '" ' +
                                '<a href="' + response.data.edit_url + '" class="button button-small">Edit Post</a> ' +
                                '<a href="' + response.data.view_url + '" class="button button-small" target="_blank">View Post</a>'
                            );
                        // Reload page after delay to update stats
                        setTimeout(function() {
                            location.reload();
                        }, 3000);
                    } else {
                        console.error('Generation failed:', response.data);
                        $status
                            .removeClass('kaib-loading')
                            .addClass('kaib-error')
                            .html('<span class="dashicons dashicons-warning"></span> <strong>Error:</strong> ' + (response.data || 'Unknown error - check Activity Log'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', status, error, xhr.responseText);
                    var errorMsg = 'Request failed. ';
                    if (status === 'timeout') {
                        errorMsg = 'Request timed out. The post may still be generating - check your Posts page and Activity Log.';
                    } else {
                        errorMsg += error || 'Check browser console and Activity Log for details.';
                    }
                    $status
                        .removeClass('kaib-loading')
                        .addClass('kaib-error')
                        .html('<span class="dashicons dashicons-warning"></span> ' + errorMsg);
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Generate Backdated Post');
                }
            });
        });

        // Generate Bulk Backfill Posts - Sequential approach to avoid timeout
        $('#kaib-generate-backfill').on('click', function() {
            var $btn = $(this);
            var $status = $('#kaib-backfill-status');
            var $progress = $('#kaib-backfill-progress');
            var numPosts = parseInt($('#kaib-backfill-count').val()) || 7;
            var startDate = $('#kaib-backfill-start').val();
            
            console.log('Bulk backfill requested:', numPosts, 'posts starting', startDate);
            
            if (!startDate) {
                alert('Please select a starting date.');
                return;
            }
            
            if (numPosts > 30) {
                alert('Maximum 30 posts per batch.');
                numPosts = 30;
                $('#kaib-backfill-count').val(30);
            }
            
            if (!confirm('This will generate ' + numPosts + ' posts starting from ' + startDate + '. This may take ' + (numPosts * 2) + '+ minutes and use approximately $' + (numPosts * 0.12).toFixed(2) + ' in API credits. Continue?')) {
                return;
            }
            
            // Disable controls during generation
            $btn.prop('disabled', true).text('Generating...');
            $('#kaib-generate-backdated').prop('disabled', true);
            $('#kaib-generate-now').prop('disabled', true);
            
            $progress.show();
            $('.kaib-progress-fill').css('width', '0%');
            $('.kaib-progress-text').text('Preparing...');
            
            $status
                .removeClass('kaib-success kaib-error')
                .addClass('kaib-loading')
                .html('<span class="dashicons dashicons-update spin"></span> Starting bulk generation of ' + numPosts + ' posts...')
                .show();

            // Generate dates array
            var dates = [];
            var currentDate = new Date(startDate + 'T12:00:00');
            var today = new Date();
            today.setHours(23, 59, 59, 999);
            
            for (var i = 0; i < numPosts; i++) {
                if (currentDate > today) break;
                dates.push(currentDate.toISOString().split('T')[0]);
                currentDate.setDate(currentDate.getDate() + 1);
            }
            
            console.log('Dates to generate:', dates);
            
            var results = {
                success: [],
                failed: []
            };
            var currentIndex = 0;
            
            // Sequential generation function
            function generateNextPost() {
                if (currentIndex >= dates.length) {
                    // All done!
                    generationComplete();
                    return;
                }
                
                var dateToGenerate = dates[currentIndex];
                var progress = Math.round(((currentIndex) / dates.length) * 100);
                
                $('.kaib-progress-fill').css('width', progress + '%');
                $('.kaib-progress-text').text('Generating post ' + (currentIndex + 1) + ' of ' + dates.length + ' (' + dateToGenerate + ')...');
                
                $status.html('<span class="dashicons dashicons-update spin"></span> Generating post ' + (currentIndex + 1) + ' of ' + dates.length + ' for ' + dateToGenerate + '...');
                
                console.log('Generating post', currentIndex + 1, 'for date:', dateToGenerate);
                
                $.ajax({
                    url: kaib_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'kaib_generate_single_backdate',
                        nonce: kaib_ajax.nonce,
                        backdate: dateToGenerate
                    },
                    timeout: 180000, // 3 minutes per post
                    success: function(response) {
                        console.log('Post', currentIndex + 1, 'response:', response);
                        if (response.success) {
                            results.success.push({
                                post_id: response.data.post_id,
                                date: dateToGenerate,
                                title: response.data.title
                            });
                        } else {
                            results.failed.push({
                                date: dateToGenerate,
                                error: response.data || 'Unknown error'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Post', currentIndex + 1, 'error:', status, error);
                        results.failed.push({
                            date: dateToGenerate,
                            error: error || status || 'Request failed'
                        });
                    },
                    complete: function() {
                        currentIndex++;
                        // Small delay between posts to avoid rate limiting
                        setTimeout(generateNextPost, 1000);
                    }
                });
            }
            
            function generationComplete() {
                console.log('Bulk generation complete:', results);
                
                $('.kaib-progress-fill').css('width', '100%');
                $('.kaib-progress-text').text('Complete!');
                
                var message = '<span class="dashicons dashicons-yes-alt"></span> ';
                message += '<strong>Bulk generation complete!</strong><br>';
                message += results.success.length + ' posts created successfully';
                if (results.failed.length > 0) {
                    message += ', ' + results.failed.length + ' failed';
                }
                message += '.<br><br>';
                
                if (results.success.length > 0) {
                    message += '<strong>Created Posts:</strong><ul class="kaib-created-list">';
                    results.success.forEach(function(post) {
                        message += '<li>' + post.date + ': ' + post.title + '</li>';
                    });
                    message += '</ul>';
                }
                
                if (results.failed.length > 0) {
                    message += '<br><strong>Failed:</strong><ul class="kaib-created-list">';
                    results.failed.forEach(function(fail) {
                        message += '<li>' + fail.date + ': ' + fail.error + '</li>';
                    });
                    message += '</ul>';
                }
                
                $status
                    .removeClass('kaib-loading')
                    .addClass(results.success.length > 0 ? 'kaib-success' : 'kaib-error')
                    .html(message);
                
                // Re-enable controls
                $btn.prop('disabled', false).text('Generate Bulk Posts');
                $('#kaib-generate-backdated').prop('disabled', false);
                $('#kaib-generate-now').prop('disabled', false);
                
                // Reload page after delay to update stats
                if (results.success.length > 0) {
                    setTimeout(function() {
                        location.reload();
                    }, 5000);
                } else {
                    setTimeout(function() {
                        $progress.hide();
                    }, 3000);
                }
            }
            
            // Start the sequential generation
            generateNextPost();
        });

        // Test API Connection button
        $('#kaib-test-connection').on('click', function() {
            var $btn = $(this);
            var originalText = $btn.text();
            
            $btn.prop('disabled', true).text('Testing...');

            $.ajax({
                url: kaib_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'kaib_test_connection',
                    nonce: kaib_ajax.nonce
                },
                timeout: 30000,
                success: function(response) {
                    if (response.success) {
                        alert('✓ ' + response.data);
                    } else {
                        alert('✗ ' + response.data);
                    }
                },
                error: function() {
                    alert('✗ Connection test failed. Please check your API key.');
                },
                complete: function() {
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        });

        // Clear topics button
        $('#kaib-clear-topics').on('click', function() {
            if (!confirm('Are you sure you want to clear all tracked topics? This will allow the AI to potentially repeat previously covered topics.')) {
                return;
            }

            var $btn = $(this);
            $btn.prop('disabled', true).text('Clearing...');

            $.ajax({
                url: kaib_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'kaib_clear_topics',
                    nonce: kaib_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Topic history cleared successfully!');
                        location.reload();
                    } else {
                        alert('Failed to clear topics: ' + response.data);
                    }
                },
                error: function() {
                    alert('Failed to clear topics. Please try again.');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Reset Topic History');
                }
            });
        });

        // Clear log button
        $('#kaib-clear-log').on('click', function() {
            if (!confirm('Are you sure you want to clear the activity log?')) {
                return;
            }

            var $btn = $(this);
            $btn.prop('disabled', true).text('Clearing...');

            $.ajax({
                url: kaib_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'kaib_clear_log',
                    nonce: kaib_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Activity log cleared!');
                        location.reload();
                    } else {
                        alert('Failed to clear log.');
                    }
                },
                error: function() {
                    alert('Failed to clear log. Please try again.');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Clear Log');
                }
            });
        });

        // Toggle API key visibility
        $('#kaib-toggle-key').on('click', function() {
            var $input = $('#kaib_openai_api_key');
            var $btn = $(this);
            
            if ($input.attr('type') === 'password') {
                $input.attr('type', 'text');
                $btn.text('Hide');
            } else {
                $input.attr('type', 'password');
                $btn.text('Show');
            }
        });

        // Add spinning animation for loading
        $('<style>')
            .prop('type', 'text/css')
            .html(`
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                .dashicons.spin {
                    animation: spin 1s linear infinite;
                }
            `)
            .appendTo('head');
    });

})(jQuery);
