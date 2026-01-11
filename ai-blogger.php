<?php
/**
 * Plugin Name: AI Blogger
 * Plugin URI: https://www.kre8ivtech.com
 * Description: Automatically generates daily SEO-optimized blog posts about marketing concepts with AI-generated featured images using OpenAI and OpenRouter.
 * Version: 1.3.0
 * Author: Kre8ivTech, LLC
 * Author URI: https://www.kre8ivtech.com
 * License: GPL v2 or later
 * Text Domain: kre8iv-ai-blogger
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('KAIB_VERSION', '1.3.0');
define('KAIB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('KAIB_PLUGIN_URL', plugin_dir_url(__FILE__));

class Kre8iv_AI_Blogger {
    
    private static $instance = null;
    private $openai_api_key;
    private $openrouter_api_key;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->openai_api_key = get_option('kaib_openai_api_key', '');
        $this->openrouter_api_key = get_option('kaib_openrouter_api_key', '');
        
        // Initialize hooks
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('kaib_daily_post_event', [$this, 'generate_daily_post']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
        
        // Add meta box for social media posting on post edit page
        add_action('add_meta_boxes', [$this, 'add_social_media_meta_box']);
        
        // AJAX handlers
        add_action('wp_ajax_kaib_generate_now', [$this, 'ajax_generate_now']);
        add_action('wp_ajax_kaib_test_connection', [$this, 'ajax_test_connection']);
        add_action('wp_ajax_kaib_clear_topics', [$this, 'ajax_clear_topics']);
        add_action('wp_ajax_kaib_clear_log', [$this, 'ajax_clear_log']);
        add_action('wp_ajax_kaib_generate_backfill', [$this, 'ajax_generate_backfill']);
        add_action('wp_ajax_kaib_generate_single_backdate', [$this, 'ajax_generate_single_backdate']);
        add_action('wp_ajax_kaib_post_to_facebook', [$this, 'ajax_post_to_facebook']);
        add_action('wp_ajax_kaib_post_to_linkedin', [$this, 'ajax_post_to_linkedin']);
        
        // Auto-posting hooks
        add_action('transition_post_status', [$this, 'handle_post_publish'], 10, 3);
        
        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }
    
    public function activate() {
        // Schedule the daily cron event
        if (!wp_next_scheduled('kaib_daily_post_event')) {
            $scheduled_time = get_option('kaib_scheduled_time', '06:00');
            $timezone = get_option('timezone_string', 'America/New_York');
            
            try {
                $dt = new DateTime($scheduled_time, new DateTimeZone($timezone));
                $dt->setTimezone(new DateTimeZone('UTC'));
                
                // If the time has already passed today, schedule for tomorrow
                $now = new DateTime('now', new DateTimeZone('UTC'));
                if ($dt <= $now) {
                    $dt->modify('+1 day');
                }
                
                wp_schedule_event($dt->getTimestamp(), 'daily', 'kaib_daily_post_event');
            } catch (Exception $e) {
                // Fallback: schedule for next hour
                wp_schedule_event(time() + 3600, 'daily', 'kaib_daily_post_event');
            }
        }
        
        // Initialize used topics option
        if (!get_option('kaib_used_topics')) {
            update_option('kaib_used_topics', []);
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        // Clear scheduled hook
        $timestamp = wp_next_scheduled('kaib_daily_post_event');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'kaib_daily_post_event');
        }
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'AI Blogger',
            'AI Blogger',
            'manage_options',
            'kre8iv-ai-blogger',
            [$this, 'render_admin_page'],
            'dashicons-edit-page',
            30
        );
        
        add_submenu_page(
            'kre8iv-ai-blogger',
            'Settings',
            'Settings',
            'manage_options',
            'kre8iv-ai-blogger-settings',
            [$this, 'render_settings_page']
        );
        
        add_submenu_page(
            'kre8iv-ai-blogger',
            'Post History',
            'Post History',
            'manage_options',
            'kre8iv-ai-blogger-history',
            [$this, 'render_history_page']
        );
    }
    
    public function register_settings() {
        // API Settings
        register_setting('kaib_settings', 'kaib_openai_api_key', [
            'sanitize_callback' => 'sanitize_text_field'
        ]);
        register_setting('kaib_settings', 'kaib_openrouter_api_key', [
            'sanitize_callback' => 'sanitize_text_field'
        ]);
        
        // Content Settings
        register_setting('kaib_settings', 'kaib_post_status', [
            'default' => 'draft'
        ]);
        register_setting('kaib_settings', 'kaib_post_categories', [
            'default' => [],
            'sanitize_callback' => [$this, 'sanitize_categories']
        ]);
        register_setting('kaib_settings', 'kaib_backfill_enabled', [
            'default' => 0
        ]);
        register_setting('kaib_settings', 'kaib_backfill_days', [
            'default' => 30
        ]);
        register_setting('kaib_settings', 'kaib_post_author', [
            'default' => 1
        ]);
        register_setting('kaib_settings', 'kaib_scheduled_time', [
            'default' => '06:00'
        ]);
        register_setting('kaib_settings', 'kaib_word_count', [
            'default' => 1500
        ]);
        register_setting('kaib_settings', 'kaib_gpt_model', [
            'default' => 'gpt-4o'
        ]);
        register_setting('kaib_settings', 'kaib_image_model', [
            'default' => 'black-forest-labs/flux'
        ]);
        register_setting('kaib_settings', 'kaib_business_name', [
            'default' => ''
        ]);
        register_setting('kaib_settings', 'kaib_business_description', [
            'default' => ''
        ]);
        register_setting('kaib_settings', 'kaib_target_audience', [
            'default' => ''
        ]);
        register_setting('kaib_settings', 'kaib_custom_system_prompt', [
            'sanitize_callback' => 'sanitize_textarea_field',
            'default' => ''
        ]);
        register_setting('kaib_settings', 'kaib_custom_prompt', [
            'sanitize_callback' => 'sanitize_textarea_field',
            'default' => ''
        ]);
        register_setting('kaib_settings', 'kaib_enable_auto_post', [
            'default' => 0
        ]);
        
        // Social Media Settings
        register_setting('kaib_settings', 'kaib_facebook_page_id', [
            'sanitize_callback' => 'sanitize_text_field'
        ]);
        register_setting('kaib_settings', 'kaib_facebook_access_token', [
            'sanitize_callback' => 'sanitize_text_field'
        ]);
        register_setting('kaib_settings', 'kaib_facebook_auto_post', [
            'default' => 0
        ]);
        register_setting('kaib_settings', 'kaib_linkedin_org_id', [
            'sanitize_callback' => 'sanitize_text_field'
        ]);
        register_setting('kaib_settings', 'kaib_linkedin_access_token', [
            'sanitize_callback' => 'sanitize_text_field'
        ]);
        register_setting('kaib_settings', 'kaib_linkedin_auto_post', [
            'default' => 0
        ]);
    }
    
    public function enqueue_admin_styles($hook) {
        // Load on plugin admin pages
        if (strpos($hook, 'kre8iv-ai-blogger') !== false) {
            wp_enqueue_style(
                'kaib-admin-styles',
                KAIB_PLUGIN_URL . 'assets/css/admin.css',
                [],
                KAIB_VERSION
            );
            
            wp_enqueue_script(
                'kaib-admin-script',
                KAIB_PLUGIN_URL . 'assets/js/admin.js',
                ['jquery'],
                KAIB_VERSION,
                true
            );
            
            wp_localize_script('kaib-admin-script', 'kaib_ajax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('kaib_nonce')
            ]);
        }
        
        // Also load on post edit pages for social media meta box
        if ($hook === 'post.php' || $hook === 'post-new.php') {
            wp_enqueue_script('jquery');
            wp_localize_script('jquery', 'kaib_ajax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('kaib_nonce')
            ]);
        }
    }
    
    public function render_admin_page() {
        $next_scheduled = wp_next_scheduled('kaib_daily_post_event');
        $api_configured = !empty($this->openai_api_key);
        $auto_enabled = get_option('kaib_enable_auto_post', 0);
        
        include KAIB_PLUGIN_DIR . 'templates/admin-dashboard.php';
    }
    
    public function render_settings_page() {
        include KAIB_PLUGIN_DIR . 'templates/admin-settings.php';
    }
    
    public function render_history_page() {
        $generated_posts = $this->get_generated_posts();
        include KAIB_PLUGIN_DIR . 'templates/admin-history.php';
    }
    
    public function add_social_media_meta_box() {
        add_meta_box(
            'kaib_social_media',
            'Social Media Posting',
            [$this, 'render_social_media_meta_box'],
            'post',
            'side',
            'high'
        );
    }
    
    public function render_social_media_meta_box($post) {
        $fb_post_id = get_post_meta($post->ID, '_kaib_facebook_post_id', true);
        $li_post_id = get_post_meta($post->ID, '_kaib_linkedin_post_id', true);
        $is_generated = get_post_meta($post->ID, '_kaib_generated', true) === '1';
        
        $fb_page_id = get_option('kaib_facebook_page_id', '');
        $fb_token = get_option('kaib_facebook_access_token', '');
        $fb_configured = !empty($fb_page_id) && !empty($fb_token);
        
        $li_org_id = get_option('kaib_linkedin_org_id', '');
        $li_token = get_option('kaib_linkedin_access_token', '');
        $li_configured = !empty($li_org_id) && !empty($li_token);
        
        ?>
        <div class="kaib-social-meta-box">
            <?php if ($post->post_status !== 'publish'): ?>
                <p style="color: #d63638; margin: 0 0 15px 0;">
                    <span class="dashicons dashicons-info"></span>
                    <strong>Publish this post first</strong> to enable social media posting.
                </p>
            <?php else: ?>
                
                <!-- Facebook Section -->
                <div class="kaib-social-platform" style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #ddd;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                        <strong style="display: flex; align-items: center; gap: 8px;">
                            <span class="dashicons dashicons-facebook-alt" style="color: #1877f2;"></span>
                            Facebook
                        </strong>
                        <?php if ($fb_post_id): ?>
                            <span style="color: #46b450; font-size: 12px;">
                                <span class="dashicons dashicons-yes-alt"></span> Posted
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!$fb_configured): ?>
                        <p style="color: #d63638; font-size: 12px; margin: 5px 0;">
                            <span class="dashicons dashicons-warning"></span>
                            Facebook not configured. <a href="<?php echo admin_url('admin.php?page=kre8iv-ai-blogger-settings'); ?>">Configure in Settings</a>
                        </p>
                    <?php else: ?>
                        <button type="button" 
                                class="button button-secondary kaib-post-facebook-edit" 
                                data-post-id="<?php echo $post->ID; ?>"
                                <?php echo $fb_post_id ? 'disabled' : ''; ?>>
                            <?php if ($fb_post_id): ?>
                                <span class="dashicons dashicons-yes-alt"></span> Already Posted
                            <?php else: ?>
                                <span class="dashicons dashicons-share"></span> Post to Facebook
                            <?php endif; ?>
                        </button>
                        <?php if ($fb_post_id): ?>
                            <p style="font-size: 11px; color: #666; margin: 5px 0 0 0;">
                                Post ID: <?php echo esc_html($fb_post_id); ?>
                            </p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <!-- LinkedIn Section -->
                <div class="kaib-social-platform">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                        <strong style="display: flex; align-items: center; gap: 8px;">
                            <span class="dashicons dashicons-linkedin" style="color: #0077b5;"></span>
                            LinkedIn
                        </strong>
                        <?php if ($li_post_id): ?>
                            <span style="color: #46b450; font-size: 12px;">
                                <span class="dashicons dashicons-yes-alt"></span> Posted
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!$li_configured): ?>
                        <p style="color: #d63638; font-size: 12px; margin: 5px 0;">
                            <span class="dashicons dashicons-warning"></span>
                            LinkedIn not configured. <a href="<?php echo admin_url('admin.php?page=kre8iv-ai-blogger-settings'); ?>">Configure in Settings</a>
                        </p>
                    <?php else: ?>
                        <button type="button" 
                                class="button button-secondary kaib-post-linkedin-edit" 
                                data-post-id="<?php echo $post->ID; ?>"
                                <?php echo $li_post_id ? 'disabled' : ''; ?>>
                            <?php if ($li_post_id): ?>
                                <span class="dashicons dashicons-yes-alt"></span> Already Posted
                            <?php else: ?>
                                <span class="dashicons dashicons-share"></span> Post to LinkedIn
                            <?php endif; ?>
                        </button>
                        <?php if ($li_post_id): ?>
                            <p style="font-size: 11px; color: #666; margin: 5px 0 0 0;">
                                Post ID: <?php echo esc_html($li_post_id); ?>
                            </p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                
            <?php endif; ?>
        </div>
        
        <style>
        .kaib-social-meta-box {
            padding: 5px 0;
        }
        .kaib-social-platform {
            margin-bottom: 15px;
        }
        .kaib-post-facebook-edit,
        .kaib-post-linkedin-edit {
            width: 100%;
            justify-content: center;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .kaib-post-facebook-edit:disabled,
        .kaib-post-linkedin-edit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Post to Facebook from edit page
            $('.kaib-post-facebook-edit').on('click', function() {
                var $btn = $(this);
                var postId = $btn.data('post-id');
                
                if ($btn.prop('disabled')) {
                    return;
                }
                
                if (!confirm('Post this to your Facebook Business Page?')) {
                    return;
                }
                
                $btn.prop('disabled', true).html('<span class="spinner is-active" style="float: none; margin: 0;"></span> Posting...');
                
                $.post(ajaxurl, {
                    action: 'kaib_post_to_facebook',
                    post_id: postId,
                    nonce: kaib_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        $btn.html('<span class="dashicons dashicons-yes-alt"></span> Posted to Facebook')
                            .removeClass('button-secondary')
                            .addClass('button-primary')
                            .prop('disabled', true);
                        
                        // Reload page to show post ID
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-share"></span> Post to Facebook');
                        alert('Error: ' + (response.data || 'Failed to post to Facebook'));
                    }
                });
            });
            
            // Post to LinkedIn from edit page
            $('.kaib-post-linkedin-edit').on('click', function() {
                var $btn = $(this);
                var postId = $btn.data('post-id');
                
                if ($btn.prop('disabled')) {
                    return;
                }
                
                if (!confirm('Post this to your LinkedIn Business Page?')) {
                    return;
                }
                
                $btn.prop('disabled', true).html('<span class="spinner is-active" style="float: none; margin: 0;"></span> Posting...');
                
                $.post(ajaxurl, {
                    action: 'kaib_post_to_linkedin',
                    post_id: postId,
                    nonce: kaib_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        $btn.html('<span class="dashicons dashicons-yes-alt"></span> Posted to LinkedIn')
                            .removeClass('button-secondary')
                            .addClass('button-primary')
                            .prop('disabled', true);
                        
                        // Reload page to show post ID
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-share"></span> Post to LinkedIn');
                        alert('Error: ' + (response.data || 'Failed to post to LinkedIn'));
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    private function get_generated_posts($limit = 50) {
        $args = [
            'post_type' => 'post',
            'posts_per_page' => $limit,
            'meta_query' => [
                [
                    'key' => '_kaib_generated',
                    'value' => '1'
                ]
            ],
            'orderby' => 'date',
            'order' => 'DESC'
        ];
        
        return get_posts($args);
    }
    
    public function sanitize_categories($input) {
        if (!is_array($input)) {
            return [];
        }
        return array_map('absint', $input);
    }
    
    public function generate_daily_post() {
        // Check if auto-posting is enabled
        if (!get_option('kaib_enable_auto_post', 0)) {
            $this->log('Auto-posting is disabled. Skipping generation.');
            return false;
        }
        
        return $this->generate_post();
    }
    
    public function generate_post($backdate = null) {
        if (empty($this->openai_api_key)) {
            $this->log('OpenAI API key not configured.');
            return false;
        }
        
        // Log what type of post we're generating
        if ($backdate) {
            $this->log("Starting backdated post generation for: {$backdate}");
        } else {
            $this->log("Starting current date post generation");
        }
        
        // Get a marketing topic
        $topic = $this->get_next_topic();
        if (!$topic) {
            $this->log('Failed to generate topic.');
            return false;
        }
        
        $this->log("Generating post for topic: {$topic}");
        
        // Generate the article content
        $content = $this->generate_article_content($topic);
        if (!$content) {
            $this->log('Failed to generate article content.');
            return false;
        }
        
        $this->log("Article content generated successfully");
        
        // Generate SEO metadata
        $seo_data = $this->generate_seo_metadata($topic, $content);
        
        // Generate featured image
        $image_id = $this->generate_featured_image($topic, $seo_data['image_prompt'] ?? $topic);
        
        // Get random category from selected categories
        $categories = get_option('kaib_post_categories', []);
        if (empty($categories)) {
            // Fallback to all categories if none selected
            $all_cats = get_categories(['hide_empty' => false]);
            if (!empty($all_cats)) {
                $categories = wp_list_pluck($all_cats, 'term_id');
            } else {
                $categories = [1]; // Default to category ID 1 (usually "Uncategorized")
            }
        }
        $random_category = !empty($categories) ? $categories[array_rand($categories)] : 1;
        
        // Determine post date
        $post_date = current_time('mysql');
        $post_date_gmt = current_time('mysql', 1);
        
        if ($backdate) {
            // Validate and parse the backdate
            $parsed_date = date_create($backdate);
            if ($parsed_date) {
                $post_date = date_format($parsed_date, 'Y-m-d H:i:s');
                $post_date_gmt = get_gmt_from_date($post_date);
                $this->log("Using backdate: {$post_date} (GMT: {$post_date_gmt})");
            } else {
                $this->log("Invalid backdate format: {$backdate}, using current time");
            }
        }
        
        // Create the post
        $post_data = [
            'post_title' => $content['title'],
            'post_content' => $content['body'],
            'post_excerpt' => $seo_data['meta_description'] ?? '',
            'post_status' => get_option('kaib_post_status', 'draft'),
            'post_author' => get_option('kaib_post_author', 1),
            'post_category' => [$random_category],
            'post_date' => $post_date,
            'post_date_gmt' => $post_date_gmt,
            'meta_input' => [
                '_kaib_generated' => '1',
                '_kaib_topic' => $topic,
                '_kaib_generated_date' => current_time('mysql'),
                '_kaib_backdate' => $backdate ? '1' : '0',
                '_yoast_wpseo_metadesc' => $seo_data['meta_description'] ?? '',
                '_yoast_wpseo_focuskw' => $seo_data['focus_keyword'] ?? '',
                // Also support Rank Math
                'rank_math_description' => $seo_data['meta_description'] ?? '',
                'rank_math_focus_keyword' => $seo_data['focus_keyword'] ?? '',
            ]
        ];
        
        $this->log("Inserting post into database...");
        $post_id = wp_insert_post($post_data, true);
        
        if (is_wp_error($post_id)) {
            $this->log('Failed to create post: ' . $post_id->get_error_message());
            return false;
        }
        
        // Set featured image
        if ($image_id) {
            set_post_thumbnail($post_id, $image_id);
            $this->log("Featured image {$image_id} attached to post");
        }
        
        // Add tags
        if (!empty($seo_data['tags'])) {
            wp_set_post_tags($post_id, $seo_data['tags']);
        }
        
        // Mark topic as used
        $this->mark_topic_used($topic);
        
        $date_msg = $backdate ? " (backdated to {$post_date})" : "";
        $this->log("Successfully created post ID: {$post_id}{$date_msg}");
        
        // Auto-post to social media if enabled and post is published
        if (get_option('kaib_post_status', 'draft') === 'publish') {
            $this->auto_post_to_social_media($post_id);
        }
        
        return $post_id;
    }
    
    public function handle_post_publish($new_status, $old_status, $post) {
        // Only auto-post when transitioning to publish status
        if ($new_status === 'publish' && $old_status !== 'publish') {
            // Check if this is a generated post
            if (get_post_meta($post->ID, '_kaib_generated', true) === '1') {
                $this->auto_post_to_social_media($post->ID);
            }
        }
    }
    
    private function auto_post_to_social_media($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return;
        }
        
        // Auto-post to Facebook if enabled
        if (get_option('kaib_facebook_auto_post', 0)) {
            $this->post_to_facebook($post_id, true);
        }
        
        // Auto-post to LinkedIn if enabled
        if (get_option('kaib_linkedin_auto_post', 0)) {
            $this->post_to_linkedin($post_id, true);
        }
    }
    
    private function post_to_facebook($post_id, $is_auto = false) {
        $page_id = get_option('kaib_facebook_page_id', '');
        $access_token = get_option('kaib_facebook_access_token', '');
        
        if (empty($page_id) || empty($access_token)) {
            if (!$is_auto) {
                $this->log('Facebook: Page ID or Access Token not configured');
            }
            return false;
        }
        
        $post = get_post($post_id);
        if (!$post) {
            return false;
        }
        
        $post_url = get_permalink($post_id);
        $post_title = get_the_title($post_id);
        $post_excerpt = get_the_excerpt($post_id);
        
        // Get featured image
        $image_url = '';
        $thumbnail_id = get_post_thumbnail_id($post_id);
        if ($thumbnail_id) {
            $image_url = wp_get_attachment_image_url($thumbnail_id, 'large');
        }
        
        // Create message (Facebook allows up to 5000 characters, but we'll keep it concise)
        $message = $post_title . "\n\n" . wp_trim_words(strip_tags($post_excerpt), 30) . "\n\n" . $post_url;
        
        // Prepare post data
        $post_data = [
            'message' => $message,
            'link' => $post_url
        ];
        
        // Add image if available
        if ($image_url) {
            $post_data['picture'] = $image_url;
        }
        
        // Post to Facebook Graph API
        $url = "https://graph.facebook.com/v18.0/{$page_id}/feed";
        $response = wp_remote_post($url, [
            'timeout' => 30,
            'body' => array_merge($post_data, ['access_token' => $access_token])
        ]);
        
        if (is_wp_error($response)) {
            $this->log('Facebook: API Error - ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        
        if (isset($decoded['error'])) {
            $this->log('Facebook: Error - ' . $decoded['error']['message']);
            return false;
        }
        
        if (isset($decoded['id'])) {
            // Store the Facebook post ID
            update_post_meta($post_id, '_kaib_facebook_post_id', $decoded['id']);
            $this->log("Facebook: Successfully posted to page. Post ID: {$decoded['id']}");
            return $decoded['id'];
        }
        
        $this->log('Facebook: Unexpected response format');
        return false;
    }
    
    private function post_to_linkedin($post_id, $is_auto = false) {
        $org_id = get_option('kaib_linkedin_org_id', '');
        $access_token = get_option('kaib_linkedin_access_token', '');
        
        if (empty($org_id) || empty($access_token)) {
            if (!$is_auto) {
                $this->log('LinkedIn: Organization ID or Access Token not configured');
            }
            return false;
        }
        
        $post = get_post($post_id);
        if (!$post) {
            return false;
        }
        
        $post_url = get_permalink($post_id);
        $post_title = get_the_title($post_id);
        $post_excerpt = get_the_excerpt($post_id);
        
        // Get featured image
        $image_url = '';
        $thumbnail_id = get_post_thumbnail_id($post_id);
        if ($thumbnail_id) {
            $image_url = wp_get_attachment_image_url($thumbnail_id, 'large');
        }
        
        // LinkedIn API requires specific format (v2 API)
        $share_content = [
            'shareCommentary' => [
                'text' => $post_title . "\n\n" . wp_trim_words(strip_tags($post_excerpt), 50) . "\n\n" . $post_url
            ],
            'shareMediaCategory' => 'ARTICLE'
        ];
        
        // Add media if image is available
        if ($image_url) {
            $share_content['media'] = [
                [
                    'status' => 'READY',
                    'description' => [
                        'text' => wp_trim_words(strip_tags($post_excerpt), 30)
                    ],
                    'media' => $image_url,
                    'originalUrl' => $post_url,
                    'title' => [
                        'text' => $post_title
                    ]
                ]
            ];
        }
        
        $content = [
            'author' => "urn:li:organization:{$org_id}",
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => $share_content
            ],
            'visibility' => [
                'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC'
            ]
        ];
        
        // Post to LinkedIn API
        $url = "https://api.linkedin.com/v2/shares";
        $response = wp_remote_post($url, [
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
                'X-Restli-Protocol-Version' => '2.0.0'
            ],
            'body' => json_encode($content)
        ]);
        
        if (is_wp_error($response)) {
            $this->log('LinkedIn: API Error - ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        
        if (isset($decoded['serviceErrorCode'])) {
            $this->log('LinkedIn: Error - ' . ($decoded['message'] ?? 'Unknown error'));
            return false;
        }
        
        if (isset($decoded['id'])) {
            // Store the LinkedIn post ID
            update_post_meta($post_id, '_kaib_linkedin_post_id', $decoded['id']);
            $this->log("LinkedIn: Successfully posted to organization. Post ID: {$decoded['id']}");
            return $decoded['id'];
        }
        
        $this->log('LinkedIn: Unexpected response format');
        return false;
    }
    
    public function generate_backfill_posts($num_posts, $start_date = null) {
        if (empty($this->openai_api_key)) {
            $this->log('Backfill failed: OpenAI API key not configured');
            return ['error' => 'OpenAI API key not configured'];
        }
        
        $results = [
            'success' => [],
            'failed' => []
        ];
        
        // Default start date is today minus num_posts days
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime("-{$num_posts} days"));
        }
        
        try {
            $current_date = new DateTime($start_date);
            $today = new DateTime(current_time('Y-m-d'));
        } catch (Exception $e) {
            $this->log('Backfill failed: Invalid date - ' . $e->getMessage());
            return ['error' => 'Invalid date format'];
        }
        
        $this->log("Starting backfill: {$num_posts} posts from {$start_date}");
        
        for ($i = 0; $i < $num_posts; $i++) {
            // Don't create future posts
            if ($current_date > $today) {
                $this->log('Stopping backfill: reached future date');
                break;
            }
            
            // Random time between 6 AM and 6 PM
            $hour = rand(6, 18);
            $minute = rand(0, 59);
            $second = rand(0, 59);
            $backdate = $current_date->format('Y-m-d') . sprintf(" %02d:%02d:%02d", $hour, $minute, $second);
            
            $this->log("Backfill generating post " . ($i + 1) . " of {$num_posts} for {$backdate}");
            
            $post_id = $this->generate_post($backdate);
            
            if ($post_id && !is_wp_error($post_id)) {
                $results['success'][] = [
                    'post_id' => $post_id,
                    'date' => $backdate,
                    'title' => get_the_title($post_id)
                ];
                $this->log("Backfill post {$post_id} created for {$backdate}");
            } else {
                $results['failed'][] = [
                    'date' => $backdate,
                    'error' => 'Generation failed'
                ];
                $this->log("Backfill failed for {$backdate}");
            }
            
            $current_date->modify('+1 day');
            
            // Small delay to avoid rate limiting
            if ($i < $num_posts - 1) {
                sleep(2);
            }
        }
        
        $this->log("Backfill complete: " . count($results['success']) . " created, " . count($results['failed']) . " failed");
        
        return $results;
    }
    
    private function get_next_topic() {
        $used_topics = get_option('kaib_used_topics', []);
        
        $prompt = $this->build_topic_prompt($used_topics);
        
        $response = $this->call_openai_chat([
            'model' => get_option('kaib_gpt_model', 'gpt-4o'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a marketing content strategist. Respond with only the topic name, no explanations or formatting.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 100,
            'temperature' => 0.8
        ]);
        
        if ($response && isset($response['choices'][0]['message']['content'])) {
            return trim($response['choices'][0]['message']['content']);
        }
        
        return false;
    }
    
    private function build_topic_prompt($used_topics) {
        $topics_list = !empty($used_topics) ? implode(', ', array_slice($used_topics, -50)) : 'none yet';
        
        $business_name = get_option('kaib_business_name', '');
        $business_desc = get_option('kaib_business_description', '');
        $target_audience = get_option('kaib_target_audience', '');
        
        $context = '';
        if ($business_name) {
            $context .= "Business: {$business_name}. ";
        }
        if ($business_desc) {
            $context .= "Description: {$business_desc}. ";
        }
        if ($target_audience) {
            $context .= "Target Audience: {$target_audience}. ";
        }
        
        return "Generate a single, unique marketing topic for a blog post. 
        
{$context}

The topic should cover one of these marketing areas (rotate through them):
- Digital Marketing Strategy
- Content Marketing
- Social Media Marketing
- Email Marketing
- SEO & SEM
- Brand Building
- Marketing Analytics
- Customer Acquisition
- Conversion Optimization
- Marketing Automation
- Influencer Marketing
- Video Marketing
- PPC Advertising
- Local Marketing
- B2B Marketing
- Marketing Psychology
- Growth Hacking
- Community Building
- Customer Retention
- Marketing Technology

Topics already covered (avoid these): {$topics_list}

Respond with ONLY the topic name, formatted as a compelling blog post topic. Example: 'How to Build a Content Calendar That Actually Works'";
    }
    
    /**
     * Replace placeholders in custom prompts with actual values
     * 
     * @param string $prompt The prompt template with placeholders
     * @param string $topic The blog post topic
     * @param string $context The business context string
     * @param int $word_count Target word count
     * @param string $business_name Business name
     * @param string $business_desc Business description
     * @param string $target_audience Target audience
     * @return string Prompt with placeholders replaced
     */
    private function replace_prompt_placeholders($prompt, $topic, $context, $word_count, $business_name, $business_desc, $target_audience) {
        $replacements = [
            '{topic}' => $topic,
            '{context}' => $context,
            '{word_count}' => $word_count,
            '{business_name}' => $business_name,
            '{business_description}' => $business_desc,
            '{target_audience}' => $target_audience,
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $prompt);
    }
    
    private function generate_article_content($topic) {
        $word_count = get_option('kaib_word_count', 1500);
        $business_name = get_option('kaib_business_name', '');
        $business_desc = get_option('kaib_business_description', '');
        $target_audience = get_option('kaib_target_audience', '');
        
        $context = '';
        if ($business_name) {
            $context .= "Writing for: {$business_name}. ";
        }
        if ($business_desc) {
            $context .= "Business focus: {$business_desc}. ";
        }
        if ($target_audience) {
            $context .= "Target readers: {$target_audience}. ";
        }
        
        // Get custom prompts or use defaults
        $custom_system_prompt = get_option('kaib_custom_system_prompt', '');
        $custom_prompt = get_option('kaib_custom_prompt', '');
        
        // Default system prompt
        $default_system_prompt = 'You are a 35-year-old senior marketing specialist with 12+ years of hands-on experience across digital marketing, brand strategy, and growth. You\'ve worked with startups and Fortune 500 companies alike. Your writing style is:

- Conversational yet authoritative - you speak from experience, not theory
- Trend-aware - you reference current tools like ChatGPT, TikTok trends, AI marketing tools, and platform algorithm changes
- Practical - every piece of advice comes with "here\'s exactly how to do it" steps
- Data-driven - you cite real statistics and case studies
- Relatable - you share lessons learned from failures, not just successes
- Forward-thinking - you anticipate where marketing is headed

You stay current on:
- Social media algorithm changes (Instagram, TikTok, LinkedIn, X/Twitter)
- AI and automation tools for marketing
- Privacy changes (cookie deprecation, iOS updates)
- Gen Z and millennial consumer behavior
- Emerging platforms and technologies
- SEO updates and Google algorithm changes
- Content formats that are trending (short-form video, podcasts, newsletters)

Write like you\'re sharing insider knowledge with a colleague over coffee - confident, helpful, and genuinely invested in their success. Always respond with valid JSON.';
        
        // Default user prompt
        $default_prompt = "Write a comprehensive, SEO-optimized blog post about: {$topic}

{$context}

Requirements:
1. Create an engaging, SEO-friendly title (H1)
2. Write approximately {$word_count} words
3. Use proper heading hierarchy (H2, H3) for sections
4. Include an engaging introduction with a hook
5. Add practical, actionable tips and examples
6. Include relevant statistics or data points where appropriate
7. Write a compelling conclusion with a call-to-action
8. Use short paragraphs for readability
9. Include transition sentences between sections
10. Reference current marketing trends and tools (2024-2025)
11. Include real-world examples from successful brands
12. Address common misconceptions or challenges

Format the response as JSON with this structure:
{
    \"title\": \"The SEO-optimized blog title\",
    \"body\": \"The full HTML-formatted article content with proper heading tags\"
}";
        
        // Use custom prompts if provided, otherwise use defaults
        $system_prompt = !empty($custom_system_prompt) ? $custom_system_prompt : $default_system_prompt;
        $user_prompt = !empty($custom_prompt) ? $custom_prompt : $default_prompt;
        
        // Replace placeholders in prompts
        $system_prompt = $this->replace_prompt_placeholders($system_prompt, $topic, $context, $word_count, $business_name, $business_desc, $target_audience);
        $user_prompt = $this->replace_prompt_placeholders($user_prompt, $topic, $context, $word_count, $business_name, $business_desc, $target_audience);

        $response = $this->call_openai_chat([
            'model' => get_option('kaib_gpt_model', 'gpt-4o'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $system_prompt
                ],
                [
                    'role' => 'user',
                    'content' => $user_prompt
                ]
            ],
            'max_tokens' => 4000,
            'temperature' => 0.7
        ]);
        
        if ($response && isset($response['choices'][0]['message']['content'])) {
            $content = $response['choices'][0]['message']['content'];
            
            // Clean up potential markdown code blocks
            $content = preg_replace('/^```json\s*/', '', $content);
            $content = preg_replace('/\s*```$/', '', $content);
            
            $decoded = json_decode($content, true);
            
            if ($decoded && isset($decoded['title']) && isset($decoded['body'])) {
                return $decoded;
            }
        }
        
        return false;
    }
    
    private function generate_seo_metadata($topic, $content) {
        $prompt = "Based on this blog post topic and content, generate SEO metadata.

Topic: {$topic}
Title: {$content['title']}
Content Preview: " . substr(strip_tags($content['body']), 0, 500) . "...

Generate JSON with:
{
    \"meta_description\": \"155-character max meta description with primary keyword\",
    \"focus_keyword\": \"primary SEO keyword phrase\",
    \"tags\": [\"tag1\", \"tag2\", \"tag3\", \"tag4\", \"tag5\"],
    \"image_prompt\": \"A detailed prompt for generating a professional featured image for this article\"
}";

        $response = $this->call_openai_chat([
            'model' => get_option('kaib_gpt_model', 'gpt-4o'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an SEO expert. Respond only with valid JSON.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 500,
            'temperature' => 0.5
        ]);
        
        if ($response && isset($response['choices'][0]['message']['content'])) {
            $content = $response['choices'][0]['message']['content'];
            $content = preg_replace('/^```json\s*/', '', $content);
            $content = preg_replace('/\s*```$/', '', $content);
            
            $decoded = json_decode($content, true);
            if ($decoded) {
                return $decoded;
            }
        }
        
        // Fallback SEO data
        return [
            'meta_description' => substr($topic, 0, 155),
            'focus_keyword' => $topic,
            'tags' => ['marketing'],
            'image_prompt' => $topic
        ];
    }
    
    private function generate_featured_image($topic, $image_prompt) {
        if (empty($this->openrouter_api_key)) {
            $this->log('OpenRouter API key not configured for image generation');
            return false;
        }
        
        $model = get_option('kaib_image_model', 'black-forest-labs/flux');
        
        // Realistic prompt for AI-generated images - professional quality without contradictory "real photography" requirements
        $full_prompt = "Create a high-quality, professional AI-generated featured image for a marketing blog post about: {$image_prompt}

CRITICAL REQUIREMENTS:
- ABSOLUTELY NO TEXT, WORDS, LETTERS, NUMBERS, OR TYPOGRAPHY OF ANY KIND IN THE IMAGE
- NO logos, watermarks, labels, captions, titles, or any written content
- NO generic stock photo aesthetics - avoid overly posed, staged, or clichéd corporate imagery
- Professional, polished AI-generated visual style with sophisticated composition
- High-quality rendering with attention to detail, proper lighting, and visual balance
- Editorial-style composition - thoughtful framing, interesting angles, visual storytelling
- Sophisticated color palette with professional color grading - avoid overly saturated or artificial colors
- Modern, visually engaging aesthetic suitable for professional business content
- Avoid: generic business clichés, fake smiles, staged corporate scenarios, sterile backgrounds
- Instead: visually compelling, well-composed imagery that represents the marketing concept effectively
- The image should be visually striking and professional while being clearly AI-generated art, not attempting to mimic real photography

Style: High-quality AI-generated professional illustration or visual art that avoids stock photo clichés. The image should be visually compelling, well-composed, and suitable for editorial use without any text elements.";

        $response = $this->call_openrouter_images([
            'model' => $model,
            'prompt' => $full_prompt,
            'topic' => $topic
        ]);
        
        if ($response && isset($response['data'][0]['url'])) {
            $image_url = $response['data'][0]['url'];
            return $this->upload_image_to_media_library($image_url, $topic);
        }
        
        $this->log('Failed to generate featured image');
        return false;
    }
    
    private function upload_image_to_media_library($image_url, $title) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        // Download the image
        $tmp = download_url($image_url);
        
        if (is_wp_error($tmp)) {
            $this->log('Failed to download image: ' . $tmp->get_error_message());
            return false;
        }
        
        $file_array = [
            'name' => sanitize_file_name($title) . '-' . time() . '.png',
            'tmp_name' => $tmp
        ];
        
        // Upload to media library
        $attachment_id = media_handle_sideload($file_array, 0, $title);
        
        // Clean up temp file
        @unlink($tmp);
        
        if (is_wp_error($attachment_id)) {
            $this->log('Failed to upload image: ' . $attachment_id->get_error_message());
            return false;
        }
        
        // Add alt text
        update_post_meta($attachment_id, '_wp_attachment_image_alt', $title);
        
        return $attachment_id;
    }
    
    private function call_openai_chat($data) {
        return $this->call_openai_api('https://api.openai.com/v1/chat/completions', $data);
    }
    
    private function call_openai_images($data) {
        return $this->call_openai_api('https://api.openai.com/v1/images/generations', $data);
    }
    
    private function call_openrouter_images($data) {
        $model = $data['model'] ?? 'black-forest-labs/flux';
        $prompt = $data['prompt'] ?? '';
        $topic = $data['topic'] ?? 'Generated Image';
        
        // OpenRouter image generation endpoint
        // Different models may use different endpoints, but most use /v1/images/generations
        $request_data = [
            'model' => $model,
            'prompt' => $prompt,
            'n' => 1,
            'size' => '1024x1024'
        ];
        
        $response = $this->call_openrouter_api('https://openrouter.ai/api/v1/images/generations', $request_data);
        
        if ($response && isset($response['data'][0]['url'])) {
            return $response;
        }
        
        // Some models might return base64 images instead of URLs
        if ($response && isset($response['data'][0]['b64_json'])) {
            // Convert base64 to temporary file and return URL
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            
            $image_data = base64_decode($response['data'][0]['b64_json']);
            $tmp_file = wp_tempnam('kaib-image-');
            file_put_contents($tmp_file, $image_data);
            
            // Upload to media library directly
            $file_array = [
                'name' => sanitize_file_name($topic) . '-' . time() . '.png',
                'tmp_name' => $tmp_file
            ];
            
            $attachment_id = media_handle_sideload($file_array, 0, $topic);
            @unlink($tmp_file);
            
            if (!is_wp_error($attachment_id)) {
                $image_url = wp_get_attachment_url($attachment_id);
                return ['data' => [['url' => $image_url]]];
            }
        }
        
        return false;
    }
    
    private function call_openai_api($endpoint, $data) {
        $response = wp_remote_post($endpoint, [
            'timeout' => 120,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->openai_api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($data)
        ]);
        
        if (is_wp_error($response)) {
            $this->log('API Error: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        
        if (isset($decoded['error'])) {
            $this->log('OpenAI Error: ' . $decoded['error']['message']);
            return false;
        }
        
        return $decoded;
    }
    
    private function call_openrouter_api($endpoint, $data) {
        $response = wp_remote_post($endpoint, [
            'timeout' => 120,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->openrouter_api_key,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => home_url(),
                'X-Title' => 'AI Blogger'
            ],
            'body' => json_encode($data)
        ]);
        
        if (is_wp_error($response)) {
            $this->log('OpenRouter API Error: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        
        if (isset($decoded['error'])) {
            $this->log('OpenRouter Error: ' . $decoded['error']['message']);
            return false;
        }
        
        return $decoded;
    }
    
    private function mark_topic_used($topic) {
        $used_topics = get_option('kaib_used_topics', []);
        $used_topics[] = $topic;
        
        // Keep only last 200 topics
        if (count($used_topics) > 200) {
            $used_topics = array_slice($used_topics, -200);
        }
        
        update_option('kaib_used_topics', $used_topics);
    }
    
    private function log($message) {
        $log = get_option('kaib_log', []);
        $log[] = [
            'time' => current_time('mysql'),
            'message' => $message
        ];
        
        // Keep only last 100 log entries
        if (count($log) > 100) {
            $log = array_slice($log, -100);
        }
        
        update_option('kaib_log', $log);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[AI Blogger] ' . $message);
        }
    }
    
    public function ajax_generate_now() {
        check_ajax_referer('kaib_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $post_id = $this->generate_post();
        
        if ($post_id) {
            wp_send_json_success([
                'post_id' => $post_id,
                'edit_url' => get_edit_post_link($post_id, 'raw'),
                'view_url' => get_permalink($post_id),
                'title' => get_the_title($post_id)
            ]);
        } else {
            $log = get_option('kaib_log', []);
            $last_error = end($log);
            wp_send_json_error($last_error['message'] ?? 'Failed to generate post');
        }
    }
    
    public function ajax_test_connection() {
        check_ajax_referer('kaib_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $response = $this->call_openai_chat([
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'user', 'content' => 'Say "Connection successful!" in exactly those words.']
            ],
            'max_tokens' => 20
        ]);
        
        if ($response && isset($response['choices'][0]['message']['content'])) {
            wp_send_json_success('API connection successful!');
        } else {
            wp_send_json_error('API connection failed. Please check your API key.');
        }
    }
    
    public function get_log() {
        return get_option('kaib_log', []);
    }
    
    public function clear_log() {
        update_option('kaib_log', []);
    }
    
    public function clear_used_topics() {
        update_option('kaib_used_topics', []);
    }
    
    public function ajax_clear_topics() {
        check_ajax_referer('kaib_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $this->clear_used_topics();
        wp_send_json_success('Topics cleared');
    }
    
    public function ajax_clear_log() {
        check_ajax_referer('kaib_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $this->clear_log();
        wp_send_json_success('Log cleared');
    }
    
    public function ajax_generate_backfill() {
        check_ajax_referer('kaib_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }
        
        $this->log('Starting bulk backfill generation');
        
        $num_posts = isset($_POST['num_posts']) ? absint($_POST['num_posts']) : 5;
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : null;
        
        // Limit to prevent abuse
        $num_posts = min($num_posts, 30);
        
        $this->log("Bulk backfill: {$num_posts} posts starting from {$start_date}");
        
        $results = $this->generate_backfill_posts($num_posts, $start_date);
        
        if (isset($results['error'])) {
            $this->log('Backfill error: ' . $results['error']);
            wp_send_json_error($results['error']);
            return;
        }
        
        $this->log('Backfill complete: ' . count($results['success']) . ' success, ' . count($results['failed']) . ' failed');
        wp_send_json_success($results);
    }
    
    public function ajax_generate_single_backdate() {
        check_ajax_referer('kaib_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }
        
        $date = isset($_POST['backdate']) ? sanitize_text_field($_POST['backdate']) : null;
        
        $this->log('Single backdate request received for date: ' . ($date ?: 'none'));
        
        if (!$date) {
            wp_send_json_error('No date provided');
            return;
        }
        
        // Validate date format
        $date_check = DateTime::createFromFormat('Y-m-d', $date);
        if (!$date_check) {
            $this->log('Invalid date format: ' . $date);
            wp_send_json_error('Invalid date format. Please use YYYY-MM-DD.');
            return;
        }
        
        // Add random time
        $hour = rand(6, 18);
        $minute = rand(0, 59);
        $second = rand(0, 59);
        $backdate = $date . sprintf(" %02d:%02d:%02d", $hour, $minute, $second);
        
        $this->log('Generating backdated post for: ' . $backdate);
        
        $post_id = $this->generate_post($backdate);
        
        if ($post_id && !is_wp_error($post_id)) {
            $this->log('Backdated post created successfully: ' . $post_id);
            wp_send_json_success([
                'post_id' => $post_id,
                'edit_url' => get_edit_post_link($post_id, 'raw'),
                'view_url' => get_permalink($post_id),
                'title' => get_the_title($post_id),
                'date' => $backdate
            ]);
        } else {
            $log = get_option('kaib_log', []);
            $last_error = end($log);
            $error_msg = $last_error['message'] ?? 'Failed to generate post - check activity log';
            $this->log('Backdated post generation failed: ' . $error_msg);
            wp_send_json_error($error_msg);
        }
    }
    
    public function ajax_post_to_facebook() {
        check_ajax_referer('kaib_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }
        
        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        
        if (!$post_id) {
            wp_send_json_error('No post ID provided');
            return;
        }
        
        $result = $this->post_to_facebook($post_id, false);
        
        if ($result) {
            wp_send_json_success([
                'message' => 'Successfully posted to Facebook',
                'post_id' => $result
            ]);
        } else {
            $log = get_option('kaib_log', []);
            $last_error = end($log);
            wp_send_json_error($last_error['message'] ?? 'Failed to post to Facebook');
        }
    }
    
    public function ajax_post_to_linkedin() {
        check_ajax_referer('kaib_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }
        
        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        
        if (!$post_id) {
            wp_send_json_error('No post ID provided');
            return;
        }
        
        $result = $this->post_to_linkedin($post_id, false);
        
        if ($result) {
            wp_send_json_success([
                'message' => 'Successfully posted to LinkedIn',
                'post_id' => $result
            ]);
        } else {
            $log = get_option('kaib_log', []);
            $last_error = end($log);
            wp_send_json_error($last_error['message'] ?? 'Failed to post to LinkedIn');
        }
    }
}

// Initialize the plugin
function kaib_init() {
    return Kre8iv_AI_Blogger::get_instance();
}
add_action('plugins_loaded', 'kaib_init');