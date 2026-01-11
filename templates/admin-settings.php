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

            <!-- Social Media Settings -->
            <div class="kaib-settings-section">
                <h2>
                    <span class="dashicons dashicons-share"></span>
                    Social Media Auto-Posting
                </h2>
                <p class="kaib-section-description">Automatically share your generated posts to Facebook and LinkedIn business pages.</p>
                
                <!-- Facebook Settings -->
                <h3 style="margin-top: 20px;">Facebook Business Page</h3>
                <?php 
                $fb_page_id = get_option('kaib_facebook_page_id', '');
                $fb_token = get_option('kaib_facebook_access_token', '');
                $fb_connected = !empty($fb_page_id) && !empty($fb_token);
                ?>
                <div style="margin-bottom: 20px;">
                    <?php if ($fb_connected): ?>
                        <div class="kaib-connection-status kaib-connected">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <strong>Facebook Connected</strong>
                            <span style="color: #666; margin-left: 10px;">Page ID: <?php echo esc_html($fb_page_id); ?></span>
                        </div>
                    <?php else: ?>
                        <div class="kaib-connection-status kaib-not-connected">
                            <span class="dashicons dashicons-warning"></span>
                            <strong>Facebook Not Connected</strong>
                        </div>
                    <?php endif; ?>
                </div>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="kaib_facebook_page_id">Facebook Page ID</label>
                        </th>
                        <td>
                            <input type="text" 
                                   name="kaib_facebook_page_id" 
                                   id="kaib_facebook_page_id" 
                                   value="<?php echo esc_attr($fb_page_id); ?>" 
                                   class="regular-text"
                                   placeholder="e.g., 123456789012345">
                            <button type="button" class="button" id="kaib-connect-facebook" style="margin-left: 10px;">
                                <span class="dashicons dashicons-admin-links"></span> Get Page ID
                            </button>
                            <p class="description">
                                Your Facebook Page ID. Click "Get Page ID" for step-by-step instructions.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="kaib_facebook_access_token">Facebook Access Token</label>
                        </th>
                        <td>
                            <input type="password" 
                                   name="kaib_facebook_access_token" 
                                   id="kaib_facebook_access_token" 
                                   value="<?php echo esc_attr($fb_token); ?>" 
                                   class="regular-text"
                                   autocomplete="new-password">
                            <button type="button" class="button" id="kaib-toggle-facebook-token">Show</button>
                            <button type="button" class="button button-primary" id="kaib-get-facebook-token" style="margin-left: 10px;">
                                <span class="dashicons dashicons-admin-links"></span> Get Access Token
                            </button>
                            <p class="description">
                                Get a Page Access Token with <code>pages_manage_posts</code> and <code>pages_read_engagement</code> permissions. Click "Get Access Token" for instructions.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="kaib_facebook_auto_post">Enable Auto-Posting</label>
                        </th>
                        <td>
                            <label class="kaib-toggle">
                                <input type="checkbox" 
                                       name="kaib_facebook_auto_post" 
                                       id="kaib_facebook_auto_post" 
                                       value="1" 
                                       <?php checked(get_option('kaib_facebook_auto_post', 0), 1); ?>>
                                <span class="kaib-toggle-slider"></span>
                            </label>
                            <p class="description">Automatically post to Facebook when posts are published</p>
                        </td>
                    </tr>
                </table>

                <!-- LinkedIn Settings -->
                <h3 style="margin-top: 30px;">LinkedIn Business Page</h3>
                <?php 
                $li_org_id = get_option('kaib_linkedin_org_id', '');
                $li_token = get_option('kaib_linkedin_access_token', '');
                $li_connected = !empty($li_org_id) && !empty($li_token);
                ?>
                <div style="margin-bottom: 20px;">
                    <?php if ($li_connected): ?>
                        <div class="kaib-connection-status kaib-connected">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <strong>LinkedIn Connected</strong>
                            <span style="color: #666; margin-left: 10px;">Organization ID: <?php echo esc_html($li_org_id); ?></span>
                        </div>
                    <?php else: ?>
                        <div class="kaib-connection-status kaib-not-connected">
                            <span class="dashicons dashicons-warning"></span>
                            <strong>LinkedIn Not Connected</strong>
                        </div>
                    <?php endif; ?>
                </div>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="kaib_linkedin_org_id">LinkedIn Organization ID</label>
                        </th>
                        <td>
                            <input type="text" 
                                   name="kaib_linkedin_org_id" 
                                   id="kaib_linkedin_org_id" 
                                   value="<?php echo esc_attr($li_org_id); ?>" 
                                   class="regular-text"
                                   placeholder="e.g., 12345678">
                            <button type="button" class="button" id="kaib-connect-linkedin" style="margin-left: 10px;">
                                <span class="dashicons dashicons-admin-links"></span> Get Organization ID
                            </button>
                            <p class="description">
                                Your LinkedIn Organization ID (numeric ID, not the company name). Click "Get Organization ID" for instructions.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="kaib_linkedin_access_token">LinkedIn Access Token</label>
                        </th>
                        <td>
                            <input type="password" 
                                   name="kaib_linkedin_access_token" 
                                   id="kaib_linkedin_access_token" 
                                   value="<?php echo esc_attr($li_token); ?>" 
                                   class="regular-text"
                                   autocomplete="new-password">
                            <button type="button" class="button" id="kaib-toggle-linkedin-token">Show</button>
                            <button type="button" class="button button-primary" id="kaib-get-linkedin-token" style="margin-left: 10px;">
                                <span class="dashicons dashicons-admin-links"></span> Get Access Token
                            </button>
                            <p class="description">
                                Get an Access Token with <code>w_member_social</code> and <code>w_organization_social</code> permissions. Click "Get Access Token" for instructions.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="kaib_linkedin_auto_post">Enable Auto-Posting</label>
                        </th>
                        <td>
                            <label class="kaib-toggle">
                                <input type="checkbox" 
                                       name="kaib_linkedin_auto_post" 
                                       id="kaib_linkedin_auto_post" 
                                       value="1" 
                                       <?php checked(get_option('kaib_linkedin_auto_post', 0), 1); ?>>
                                <span class="kaib-toggle-slider"></span>
                            </label>
                            <p class="description">Automatically post to LinkedIn when posts are published</p>
                        </td>
                    </tr>
                </table>
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

    // Toggle Facebook token visibility
    $('#kaib-toggle-facebook-token').on('click', function() {
        var input = $('#kaib_facebook_access_token');
        var btn = $(this);
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            btn.text('Hide');
        } else {
            input.attr('type', 'password');
            btn.text('Show');
        }
    });

    // Toggle LinkedIn token visibility
    $('#kaib-toggle-linkedin-token').on('click', function() {
        var input = $('#kaib_linkedin_access_token');
        var btn = $(this);
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            btn.text('Hide');
        } else {
            input.attr('type', 'password');
            btn.text('Show');
        }
    });

    // Facebook Connect Buttons
    $('#kaib-connect-facebook').on('click', function() {
        var instructions = 'How to Get Your Facebook Page ID:\n\n' +
            '1. Go to https://developers.facebook.com/tools/explorer/\n' +
            '2. Select your app from the dropdown\n' +
            '3. Click "Get Token" → "Get User Access Token"\n' +
            '4. Select "pages_show_list" permission and generate token\n' +
            '5. In the API explorer, use: GET /me/accounts\n' +
            '6. Find your page in the results - the "id" field is your Page ID\n\n' +
            'Alternatively:\n' +
            '- Go to your Facebook Page Settings\n' +
            '- Check the URL or Page Info section for the numeric ID';
        alert(instructions);
    });

    $('#kaib-get-facebook-token').on('click', function() {
        var instructions = 'How to Get Your Facebook Page Access Token:\n\n' +
            '1. Go to https://developers.facebook.com/tools/explorer/\n' +
            '2. Select your app from the dropdown\n' +
            '3. Click "Get Token" → "Get User Access Token"\n' +
            '4. Select these permissions:\n' +
            '   - pages_manage_posts\n' +
            '   - pages_read_engagement\n' +
            '   - pages_show_list\n' +
            '5. Generate the token\n' +
            '6. Use: GET /me/accounts to get your pages\n' +
            '7. Use: GET /{page-id}?fields=access_token to get Page Token\n' +
            '8. Copy the "access_token" value from the response\n\n' +
            'Note: Page tokens are long-lived and don\'t expire as quickly as user tokens.';
        alert(instructions);
        window.open('https://developers.facebook.com/tools/explorer/', '_blank');
    });

    // LinkedIn Connect Buttons
    $('#kaib-connect-linkedin').on('click', function() {
        var instructions = 'How to Get Your LinkedIn Organization ID:\n\n' +
            '1. Go to https://www.linkedin.com/company/your-company/\n' +
            '2. View page source (Ctrl+U or Cmd+U)\n' +
            '3. Search for "organizationId" or "companyId"\n' +
            '4. The numeric value is your Organization ID\n\n' +
            'Or use the LinkedIn API:\n' +
            '1. Go to https://www.linkedin.com/developers/apps\n' +
            '2. Create/select your app\n' +
            '3. Use the Organizations API endpoint\n' +
            '4. Query your organization to get the numeric ID';
        alert(instructions);
        window.open('https://www.linkedin.com/developers/apps', '_blank');
    });

    $('#kaib-get-linkedin-token').on('click', function() {
        var instructions = 'How to Get Your LinkedIn Access Token:\n\n' +
            '1. Go to https://www.linkedin.com/developers/apps\n' +
            '2. Create a new app or select existing one\n' +
            '3. In "Auth" tab, add these redirect URLs:\n' +
            '   - http://localhost (for testing)\n' +
            '   - Your website URL\n' +
            '4. Request these permissions:\n' +
            '   - w_member_social\n' +
            '   - w_organization_social\n' +
            '5. Use OAuth 2.0 flow to get authorization code\n' +
            '6. Exchange code for access token\n' +
            '7. The token will have access to post on behalf of your organization\n\n' +
            'For detailed steps, visit:\n' +
            'https://docs.microsoft.com/en-us/linkedin/shared/authentication/authentication';
        alert(instructions);
        window.open('https://www.linkedin.com/developers/apps', '_blank');
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
