<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap kaib-wrap">
    <div class="kaib-header">
        <h1>
            <span class="dashicons dashicons-admin-settings"></span>
            AI Blogger Settings
        </h1>
    </div>

    <form method="post" action="options.php" class="kaib-settings-form">
        <?php settings_fields('kaib_settings'); ?>
        
        <div class="kaib-settings-grid">
            <!-- API Configuration -->
            <div class="kaib-settings-section">
                <h2>
                    <span class="dashicons dashicons-admin-network"></span>
                    API Configuration
                </h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="kaib_openai_api_key">OpenAI API Key</label>
                        </th>
                        <td>
                            <input type="password" 
                                   name="kaib_openai_api_key" 
                                   id="kaib_openai_api_key" 
                                   value="<?php echo esc_attr(get_option('kaib_openai_api_key', '')); ?>" 
                                   class="regular-text"
                                   autocomplete="new-password">
                            <button type="button" class="button" id="kaib-toggle-key">Show</button>
                            <p class="description">
                                Get your API key from <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Dashboard</a>
                                <br>Used for text content generation (GPT models)
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="kaib_openrouter_api_key">OpenRouter API Key</label>
                        </th>
                        <td>
                            <input type="password" 
                                   name="kaib_openrouter_api_key" 
                                   id="kaib_openrouter_api_key" 
                                   value="<?php echo esc_attr(get_option('kaib_openrouter_api_key', '')); ?>" 
                                   class="regular-text"
                                   autocomplete="new-password">
                            <button type="button" class="button" id="kaib-toggle-openrouter-key">Show</button>
                            <p class="description">
                                Get your API key from <a href="https://openrouter.ai/keys" target="_blank">OpenRouter Dashboard</a>
                                <br>Required for image generation with Gemini models
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="kaib_gpt_model">GPT Model</label>
                        </th>
                        <td>
                            <select name="kaib_gpt_model" id="kaib_gpt_model">
                                <option value="gpt-4o" <?php selected(get_option('kaib_gpt_model', 'gpt-4o'), 'gpt-4o'); ?>>GPT-4o (Recommended)</option>
                                <option value="gpt-4o-mini" <?php selected(get_option('kaib_gpt_model'), 'gpt-4o-mini'); ?>>GPT-4o Mini (Faster/Cheaper)</option>
                                <option value="gpt-4-turbo" <?php selected(get_option('kaib_gpt_model'), 'gpt-4-turbo'); ?>>GPT-4 Turbo</option>
                            </select>
                            <p class="description">Select the GPT model for content generation</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="kaib_image_model">Image Model</label>
                        </th>
                        <td>
                            <select name="kaib_image_model" id="kaib_image_model">
                                <option value="google/gemini-2.0-flash-exp:free" <?php selected(get_option('kaib_image_model', 'google/gemini-2.0-flash-exp:free'), 'google/gemini-2.0-flash-exp:free'); ?>>Gemini 2.0 Flash (Free via OpenRouter)</option>
                                <option value="google/gemini-flash-1.5" <?php selected(get_option('kaib_image_model'), 'google/gemini-flash-1.5'); ?>>Gemini Flash 1.5</option>
                                <option value="stability-ai/stable-diffusion-xl" <?php selected(get_option('kaib_image_model'), 'stability-ai/stable-diffusion-xl'); ?>>Stable Diffusion XL</option>
                                <option value="black-forest-labs/flux" <?php selected(get_option('kaib_image_model'), 'black-forest-labs/flux'); ?>>Flux (High Quality)</option>
                            </select>
                            <p class="description">Image generation models via OpenRouter. Requires OpenRouter API key.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Business Context -->
            <div class="kaib-settings-section">
                <h2>
                    <span class="dashicons dashicons-building"></span>
                    Business Context
                </h2>
                <p class="kaib-section-description">Help AI generate more relevant content by providing business context.</p>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="kaib_business_name">Business Name</label>
                        </th>
                        <td>
                            <input type="text" 
                                   name="kaib_business_name" 
                                   id="kaib_business_name" 
                                   value="<?php echo esc_attr(get_option('kaib_business_name', '')); ?>" 
                                   class="regular-text"
                                   placeholder="e.g., Kre8iv Designs">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="kaib_business_description">Business Description</label>
                        </th>
                        <td>
                            <textarea name="kaib_business_description" 
                                      id="kaib_business_description" 
                                      rows="3" 
                                      class="large-text"
                                      placeholder="e.g., We are a tech and marketing company specializing in scalable solutions..."><?php echo esc_textarea(get_option('kaib_business_description', '')); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="kaib_target_audience">Target Audience</label>
                        </th>
                        <td>
                            <textarea name="kaib_target_audience" 
                                      id="kaib_target_audience" 
                                      rows="2" 
                                      class="large-text"
                                      placeholder="e.g., Small to medium business owners, marketing professionals, entrepreneurs..."><?php echo esc_textarea(get_option('kaib_target_audience', '')); ?></textarea>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Post Settings -->
            <div class="kaib-settings-section">
                <h2>
                    <span class="dashicons dashicons-admin-post"></span>
                    Post Settings
                </h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="kaib_enable_auto_post">Enable Auto-Posting</label>
                        </th>
                        <td>
                            <label class="kaib-toggle">
                                <input type="checkbox" 
                                       name="kaib_enable_auto_post" 
                                       id="kaib_enable_auto_post" 
                                       value="1" 
                                       <?php checked(get_option('kaib_enable_auto_post', 0), 1); ?>>
                                <span class="kaib-toggle-slider"></span>
                            </label>
                            <p class="description">When enabled, posts will be generated automatically at the scheduled time</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="kaib_post_status">Default Post Status</label>
                        </th>
                        <td>
                            <select name="kaib_post_status" id="kaib_post_status">
                                <option value="draft" <?php selected(get_option('kaib_post_status', 'draft'), 'draft'); ?>>Draft (Review before publishing)</option>
                                <option value="publish" <?php selected(get_option('kaib_post_status'), 'publish'); ?>>Published (Go live immediately)</option>
                                <option value="pending" <?php selected(get_option('kaib_post_status'), 'pending'); ?>>Pending Review</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="kaib_post_categories">Post Categories</label>
                        </th>
                        <td>
                            <fieldset class="kaib-category-checkboxes">
                                <?php
                                $categories = get_categories(['hide_empty' => false]);
                                $selected_cats = get_option('kaib_post_categories', []);
                                foreach ($categories as $category):
                                ?>
                                <label class="kaib-checkbox-label">
                                    <input type="checkbox" 
                                           name="kaib_post_categories[]" 
                                           value="<?php echo esc_attr($category->term_id); ?>"
                                           <?php checked(in_array($category->term_id, $selected_cats)); ?>>
                                    <?php echo esc_html($category->name); ?>
                                    <span class="kaib-cat-count">(<?php echo $category->count; ?> posts)</span>
                                </label>
                                <?php endforeach; ?>
                            </fieldset>
                            <p class="description">
                                Select multiple categories. A random category will be chosen for each post.
                                <br>If none selected, all categories will be used.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="kaib_post_author">Post Author</label>
                        </th>
                        <td>
                            <?php
                            wp_dropdown_users([
                                'name' => 'kaib_post_author',
                                'id' => 'kaib_post_author',
                                'selected' => get_option('kaib_post_author', get_current_user_id()),
                                'who' => 'authors'
                            ]);
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="kaib_word_count">Target Word Count</label>
                        </th>
                        <td>
                            <input type="number" 
                                   name="kaib_word_count" 
                                   id="kaib_word_count" 
                                   value="<?php echo esc_attr(get_option('kaib_word_count', 1500)); ?>" 
                                   min="500" 
                                   max="3000" 
                                   step="100"
                                   class="small-text">
                            <p class="description">Recommended: 1200-1800 words for SEO</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Schedule Settings -->
            <div class="kaib-settings-section">
                <h2>
                    <span class="dashicons dashicons-calendar-alt"></span>
                    Schedule Settings
                </h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="kaib_scheduled_time">Daily Post Time</label>
                        </th>
                        <td>
                            <input type="time" 
                                   name="kaib_scheduled_time" 
                                   id="kaib_scheduled_time" 
                                   value="<?php echo esc_attr(get_option('kaib_scheduled_time', '06:00')); ?>">
                            <p class="description">
                                Time is based on your WordPress timezone: 
                                <strong><?php echo esc_html(get_option('timezone_string') ?: 'UTC' . get_option('gmt_offset')); ?></strong>
                            </p>
                        </td>
                    </tr>
                </table>
                <p class="kaib-note">
                    <span class="dashicons dashicons-info"></span>
                    Note: After changing the schedule time, you may need to deactivate and reactivate the plugin for changes to take effect.
                </p>
            </div>

            <!-- Maintenance -->
            <div class="kaib-settings-section">
                <h2>
                    <span class="dashicons dashicons-admin-tools"></span>
                    Maintenance
                </h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Clear Used Topics</th>
                        <td>
                            <button type="button" class="button" id="kaib-clear-topics">
                                Reset Topic History
                            </button>
                            <p class="description">
                                Clear the list of used topics to allow repetition. 
                                Currently <?php echo count(get_option('kaib_used_topics', [])); ?> topics tracked.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Clear Activity Log</th>
                        <td>
                            <button type="button" class="button" id="kaib-clear-log">
                                Clear Log
                            </button>
                            <p class="description">Remove all entries from the activity log</p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <?php submit_button('Save Settings'); ?>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Toggle API key visibility
    $('#kaib-toggle-key').on('click', function() {
        var input = $('#kaib_openai_api_key');
        var btn = $(this);
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            btn.text('Hide');
        } else {
            input.attr('type', 'password');
            btn.text('Show');
        }
    });

    // Toggle OpenRouter API key visibility
    $('#kaib-toggle-openrouter-key').on('click', function() {
        var input = $('#kaib_openrouter_api_key');
        var btn = $(this);
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            btn.text('Hide');
        } else {
            input.attr('type', 'password');
            btn.text('Show');
        }
    });

    // Clear topics
    $('#kaib-clear-topics').on('click', function() {
        if (confirm('Are you sure you want to clear all tracked topics? This cannot be undone.')) {
            $.post(ajaxurl, {
                action: 'kaib_clear_topics',
                nonce: kaib_ajax.nonce
            }, function(response) {
                if (response.success) {
                    alert('Topics cleared successfully!');
                    location.reload();
                }
            });
        }
    });

    // Clear log
    $('#kaib-clear-log').on('click', function() {
        if (confirm('Are you sure you want to clear the activity log?')) {
            $.post(ajaxurl, {
                action: 'kaib_clear_log',
                nonce: kaib_ajax.nonce
            }, function(response) {
                if (response.success) {
                    alert('Log cleared successfully!');
                    location.reload();
                }
            });
        }
    });
});
</script>
