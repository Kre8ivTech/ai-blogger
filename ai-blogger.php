<?php
/**
 * Plugin Name: Kre8iv AI Marketing Blogger
 * Plugin URI: https://kre8ivdesigns.com
 * Description: Automatically generates daily SEO-optimized blog posts about marketing concepts with AI-generated featured images using OpenAI.
 * Version: 1.2.0
 * Author: Kre8iv Designs
 * Author URI: https://kre8ivdesigns.com
 * License: GPL v2 or later
 * Text Domain: kre8iv-ai-blogger
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('KAIB_VERSION', '1.2.0');
define('KAIB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('KAIB_PLUGIN_URL', plugin_dir_url(__FILE__));

class Kre8iv_AI_Blogger {
    
    private static $instance = null;
    private $openai_api_key;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->openai_api_key = get_option('kaib_openai_api_key', '');
        
        // Initialize hooks
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('kaib_daily_post_event', [$this, 'generate_daily_post']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
        
        // AJAX handlers
        add_action('wp_ajax_kaib_generate_now', [$this, 'ajax_generate_now']);
        add_action('wp_ajax_kaib_test_connection', [$this, 'ajax_test_connection']);
        add_action('wp_ajax_kaib_clear_topics', [$this, 'ajax_clear_topics']);
        add_action('wp_ajax_kaib_clear_log', [$this, 'ajax_clear_log']);
        add_action('wp_ajax_kaib_generate_backfill', [$this, 'ajax_generate_backfill']);
        add_action('wp_ajax_kaib_generate_single_backdate', [$this, 'ajax_generate_single_backdate']);
        
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
            'Kre8iv AI Blogger',
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
            'default' => 'dall-e-3'
        ]);
        register_setting('kaib_settings', 'kaib_image_style', [
            'default' => 'vivid'
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
        register_setting('kaib_settings', 'kaib_enable_auto_post', [
            'default' => 0
        ]);
    }
    
    public function enqueue_admin_styles($hook) {
        if (strpos($hook, 'kre8iv-ai-blogger') === false) {
            return;
        }
        
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
        
        return $post_id;
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
        
        $prompt = "Write a comprehensive, SEO-optimized blog post about: {$topic}

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

        $response = $this->call_openai_chat([
            'model' => get_option('kaib_gpt_model', 'gpt-4o'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a 35-year-old senior marketing specialist with 12+ years of hands-on experience across digital marketing, brand strategy, and growth. You\'ve worked with startups and Fortune 500 companies alike. Your writing style is:

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

Write like you\'re sharing insider knowledge with a colleague over coffee - confident, helpful, and genuinely invested in their success. Always respond with valid JSON.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
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
        $style = get_option('kaib_image_style', 'vivid');
        $model = get_option('kaib_image_model', 'dall-e-3');
        
        $full_prompt = "Create a professional, modern featured image for a marketing blog post about: {$image_prompt}

CRITICAL REQUIREMENTS:
- ABSOLUTELY NO TEXT, WORDS, LETTERS, NUMBERS, OR TYPOGRAPHY OF ANY KIND IN THE IMAGE
- NO logos, watermarks, labels, captions, titles, or any written content
- Pure visual imagery only - abstract shapes, icons, illustrations, or photographs
- Clean, professional aesthetic suitable for a business blog
- Modern, visually engaging composition
- Corporate-appropriate color palette
- High-quality, polished look

Style: Abstract professional illustration or clean photography representing the marketing concept visually without any text elements.";

        $response = $this->call_openai_images([
            'model' => $model,
            'prompt' => $full_prompt,
            'n' => 1,
            'size' => '1792x1024',
            'quality' => 'standard',
            'style' => $style
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
            error_log('[Kre8iv AI Blogger] ' . $message);
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
}

// Initialize the plugin
function kaib_init() {
    return Kre8iv_AI_Blogger::get_instance();
}
add_action('plugins_loaded', 'kaib_init');
