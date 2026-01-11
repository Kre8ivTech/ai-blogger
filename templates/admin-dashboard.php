<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap kaib-wrap">
    <div class="kaib-header">
        <h1>
            <span class="dashicons dashicons-edit-page"></span>
            AI Blogger
        </h1>
        <p class="kaib-tagline">Automated SEO-optimized marketing content powered by AI</p>
    </div>

    <?php if (!$api_configured): ?>
    <div class="kaib-notice kaib-notice-warning">
        <span class="dashicons dashicons-warning"></span>
        <div>
            <strong>API Key Required</strong>
            <p>Please configure your OpenAI API key in <a href="<?php echo admin_url('admin.php?page=kre8iv-ai-blogger-settings'); ?>">Settings</a> to start generating posts.</p>
        </div>
    </div>
    <?php endif; ?>

    <div class="kaib-dashboard">
        <!-- Status Cards -->
        <div class="kaib-cards">
            <div class="kaib-card">
                <div class="kaib-card-icon">
                    <span class="dashicons dashicons-clock"></span>
                </div>
                <div class="kaib-card-content">
                    <h3>Next Scheduled Post</h3>
                    <?php if ($next_scheduled && $auto_enabled): ?>
                        <p class="kaib-value"><?php echo date('M j, Y', $next_scheduled); ?></p>
                        <p class="kaib-subvalue"><?php echo date('g:i A', $next_scheduled); ?> (Server Time)</p>
                    <?php elseif (!$auto_enabled): ?>
                        <p class="kaib-value kaib-disabled">Auto-posting disabled</p>
                        <p class="kaib-subvalue">Enable in Settings</p>
                    <?php else: ?>
                        <p class="kaib-value kaib-disabled">Not scheduled</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="kaib-card">
                <div class="kaib-card-icon">
                    <span class="dashicons dashicons-chart-bar"></span>
                </div>
                <div class="kaib-card-content">
                    <h3>Posts Generated</h3>
                    <?php
                    $total_posts = count(get_posts([
                        'post_type' => 'post',
                        'posts_per_page' => -1,
                        'meta_key' => '_kaib_generated',
                        'meta_value' => '1'
                    ]));
                    ?>
                    <p class="kaib-value"><?php echo $total_posts; ?></p>
                    <p class="kaib-subvalue">Total AI-generated articles</p>
                </div>
            </div>

            <div class="kaib-card">
                <div class="kaib-card-icon <?php echo $api_configured ? 'kaib-status-active' : 'kaib-status-inactive'; ?>">
                    <span class="dashicons dashicons-admin-network"></span>
                </div>
                <div class="kaib-card-content">
                    <h3>API Status</h3>
                    <p class="kaib-value <?php echo $api_configured ? 'kaib-active' : 'kaib-inactive'; ?>">
                        <?php echo $api_configured ? 'Configured' : 'Not Configured'; ?>
                    </p>
                    <p class="kaib-subvalue">
                        <?php if ($api_configured): ?>
                            <button type="button" class="button button-small" id="kaib-test-connection">Test Connection</button>
                        <?php else: ?>
                            <a href="<?php echo admin_url('admin.php?page=kre8iv-ai-blogger-settings'); ?>">Configure Now</a>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="kaib-card">
                <div class="kaib-card-icon">
                    <span class="dashicons dashicons-list-view"></span>
                </div>
                <div class="kaib-card-content">
                    <h3>Topics Used</h3>
                    <?php $used_topics = get_option('kaib_used_topics', []); ?>
                    <p class="kaib-value"><?php echo count($used_topics); ?></p>
                    <p class="kaib-subvalue">Unique marketing topics</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="kaib-section">
            <h2>Quick Actions</h2>
            <div class="kaib-actions">
                <button type="button" class="button button-primary button-hero" id="kaib-generate-now" <?php echo !$api_configured ? 'disabled' : ''; ?>>
                    <span class="dashicons dashicons-admin-post"></span>
                    Generate Post Now
                </button>
                <a href="<?php echo admin_url('admin.php?page=kre8iv-ai-blogger-settings'); ?>" class="button button-secondary button-hero">
                    <span class="dashicons dashicons-admin-settings"></span>
                    Configure Settings
                </a>
                <a href="<?php echo admin_url('admin.php?page=kre8iv-ai-blogger-history'); ?>" class="button button-secondary button-hero">
                    <span class="dashicons dashicons-backup"></span>
                    View History
                </a>
            </div>
            <div id="kaib-generate-status" class="kaib-status-message"></div>
        </div>

        <!-- Backdate Post Generation -->
        <div class="kaib-section">
            <h2>
                <span class="dashicons dashicons-calendar"></span>
                Generate Past Posts
            </h2>
            <p class="kaib-section-description">Create posts with past dates to build your content archive quickly.</p>
            
            <div class="kaib-backdate-options">
                <!-- Single Backdated Post -->
                <div class="kaib-backdate-single">
                    <h3>Single Post</h3>
                    <div class="kaib-form-row">
                        <label for="kaib-backdate-date">Post Date:</label>
                        <input type="date" id="kaib-backdate-date" max="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d', strtotime('-1 day')); ?>">
                        <button type="button" class="button button-primary" id="kaib-generate-backdated" <?php echo !$api_configured ? 'disabled' : ''; ?>>
                            Generate Backdated Post
                        </button>
                    </div>
                </div>
                
                <!-- Bulk Backfill -->
                <div class="kaib-backdate-bulk">
                    <h3>Bulk Backfill</h3>
                    <p class="description">Generate multiple posts with consecutive dates. Great for building initial content.</p>
                    <div class="kaib-form-row">
                        <label for="kaib-backfill-count">Number of Posts:</label>
                        <input type="number" id="kaib-backfill-count" min="1" max="30" value="7">
                        
                        <label for="kaib-backfill-start">Starting Date:</label>
                        <input type="date" id="kaib-backfill-start" max="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d', strtotime('-7 days')); ?>">
                        
                        <button type="button" class="button button-primary" id="kaib-generate-backfill" <?php echo !$api_configured ? 'disabled' : ''; ?>>
                            Generate Bulk Posts
                        </button>
                    </div>
                    <p class="kaib-warning">
                        <span class="dashicons dashicons-warning"></span>
                        Bulk generation can take several minutes and uses more API credits. Max 30 posts per batch.
                    </p>
                </div>
            </div>
            
            <div id="kaib-backfill-status" class="kaib-status-message"></div>
            <div id="kaib-backfill-progress" class="kaib-progress-container" style="display:none;">
                <div class="kaib-progress-bar">
                    <div class="kaib-progress-fill"></div>
                </div>
                <div class="kaib-progress-text">Generating posts...</div>
            </div>
        </div>

        <!-- Recent Posts -->
        <div class="kaib-section">
            <h2>Recent Generated Posts</h2>
            <?php
            $recent_posts = get_posts([
                'post_type' => 'post',
                'posts_per_page' => 5,
                'meta_key' => '_kaib_generated',
                'meta_value' => '1',
                'orderby' => 'date',
                'order' => 'DESC'
            ]);
            ?>
            <?php if (!empty($recent_posts)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Topic</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_posts as $post): ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($post->post_title); ?></strong>
                        </td>
                        <td>
                            <?php echo esc_html(get_post_meta($post->ID, '_kaib_topic', true)); ?>
                        </td>
                        <td>
                            <span class="kaib-status-badge kaib-status-<?php echo $post->post_status; ?>">
                                <?php echo ucfirst($post->post_status); ?>
                            </span>
                        </td>
                        <td>
                            <?php echo get_the_date('M j, Y', $post); ?>
                        </td>
                        <td>
                            <a href="<?php echo get_edit_post_link($post->ID); ?>" class="button button-small">Edit</a>
                            <a href="<?php echo get_permalink($post->ID); ?>" class="button button-small" target="_blank">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="kaib-empty-state">
                <span class="dashicons dashicons-admin-post"></span>
                <p>No posts generated yet. Click "Generate Post Now" to create your first AI-powered marketing article!</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Activity Log -->
        <div class="kaib-section">
            <h2>Activity Log</h2>
            <?php $log = Kre8iv_AI_Blogger::get_instance()->get_log(); ?>
            <?php if (!empty($log)): ?>
            <div class="kaib-log">
                <?php foreach (array_reverse(array_slice($log, -10)) as $entry): ?>
                <div class="kaib-log-entry">
                    <span class="kaib-log-time"><?php echo esc_html($entry['time']); ?></span>
                    <span class="kaib-log-message"><?php echo esc_html($entry['message']); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="kaib-muted">No activity logged yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
