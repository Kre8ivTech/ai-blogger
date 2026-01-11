<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap kaib-wrap">
    <div class="kaib-header">
        <h1>
            <span class="dashicons dashicons-backup"></span>
            Generated Post History
        </h1>
    </div>

    <?php if (!empty($generated_posts)): ?>
    <div class="kaib-history-stats">
        <div class="kaib-stat">
            <span class="kaib-stat-number"><?php echo count($generated_posts); ?></span>
            <span class="kaib-stat-label">Total Posts</span>
        </div>
        <?php
        $published = 0;
        $drafts = 0;
        foreach ($generated_posts as $post) {
            if ($post->post_status === 'publish') $published++;
            if ($post->post_status === 'draft') $drafts++;
        }
        ?>
        <div class="kaib-stat">
            <span class="kaib-stat-number"><?php echo $published; ?></span>
            <span class="kaib-stat-label">Published</span>
        </div>
        <div class="kaib-stat">
            <span class="kaib-stat-number"><?php echo $drafts; ?></span>
            <span class="kaib-stat-label">Drafts</span>
        </div>
    </div>

    <table class="wp-list-table widefat fixed striped kaib-history-table">
        <thead>
            <tr>
                <th class="column-thumbnail">Image</th>
                <th class="column-title">Title</th>
                <th class="column-topic">Marketing Topic</th>
                <th class="column-status">Status</th>
                <th class="column-date">Generated</th>
                <th class="column-actions">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($generated_posts as $post): ?>
            <tr>
                <td class="column-thumbnail">
                    <?php if (has_post_thumbnail($post->ID)): ?>
                        <img src="<?php echo get_the_post_thumbnail_url($post->ID, 'thumbnail'); ?>" 
                             alt="" 
                             class="kaib-thumbnail">
                    <?php else: ?>
                        <div class="kaib-no-image">
                            <span class="dashicons dashicons-format-image"></span>
                        </div>
                    <?php endif; ?>
                </td>
                <td class="column-title">
                    <strong>
                        <a href="<?php echo get_edit_post_link($post->ID); ?>">
                            <?php echo esc_html($post->post_title); ?>
                        </a>
                    </strong>
                    <div class="row-actions">
                        <span class="edit">
                            <a href="<?php echo get_edit_post_link($post->ID); ?>">Edit</a> |
                        </span>
                        <span class="view">
                            <a href="<?php echo get_permalink($post->ID); ?>" target="_blank">View</a> |
                        </span>
                        <span class="trash">
                            <a href="<?php echo get_delete_post_link($post->ID); ?>" class="submitdelete">Trash</a>
                        </span>
                    </div>
                </td>
                <td class="column-topic">
                    <?php 
                    $topic = get_post_meta($post->ID, '_kaib_topic', true);
                    echo esc_html($topic ?: '—');
                    ?>
                </td>
                <td class="column-status">
                    <span class="kaib-status-badge kaib-status-<?php echo esc_attr($post->post_status); ?>">
                        <?php echo ucfirst($post->post_status); ?>
                    </span>
                </td>
                <td class="column-date">
                    <?php 
                    $generated_date = get_post_meta($post->ID, '_kaib_generated_date', true);
                    echo $generated_date ? date('M j, Y g:i A', strtotime($generated_date)) : get_the_date('M j, Y g:i A', $post);
                    ?>
                </td>
                <td class="column-actions">
                    <a href="<?php echo get_edit_post_link($post->ID); ?>" class="button button-small" title="Edit">
                        <span class="dashicons dashicons-edit"></span>
                    </a>
                    <a href="<?php echo get_permalink($post->ID); ?>" class="button button-small" target="_blank" title="View">
                        <span class="dashicons dashicons-visibility"></span>
                    </a>
                    <?php if ($post->post_status === 'publish'): ?>
                        <button type="button" 
                                class="button button-small kaib-post-facebook" 
                                data-post-id="<?php echo $post->ID; ?>"
                                title="Post to Facebook">
                            <span class="dashicons dashicons-facebook-alt"></span>
                        </button>
                        <button type="button" 
                                class="button button-small kaib-post-linkedin" 
                                data-post-id="<?php echo $post->ID; ?>"
                                title="Post to LinkedIn">
                            <span class="dashicons dashicons-linkedin"></span>
                        </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="kaib-empty-state">
        <span class="dashicons dashicons-admin-post"></span>
        <h2>No Posts Generated Yet</h2>
        <p>AI-generated posts will appear here once you start creating content.</p>
        <a href="<?php echo admin_url('admin.php?page=kre8iv-ai-blogger'); ?>" class="button button-primary button-hero">
            Generate Your First Post
        </a>
    </div>
    <?php endif; ?>

    <!-- Used Topics Section -->
    <div class="kaib-section kaib-topics-section">
        <h2>
            <span class="dashicons dashicons-tag"></span>
            Used Marketing Topics
        </h2>
        <?php $used_topics = get_option('kaib_used_topics', []); ?>
        <?php if (!empty($used_topics)): ?>
        <div class="kaib-topics-cloud">
            <?php foreach (array_reverse($used_topics) as $topic): ?>
            <span class="kaib-topic-tag"><?php echo esc_html($topic); ?></span>
            <?php endforeach; ?>
        </div>
        <p class="kaib-muted">These topics have been covered and will be avoided in future generations.</p>
        <?php else: ?>
        <p class="kaib-muted">No topics have been used yet.</p>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Post to Facebook
    $('.kaib-post-facebook').on('click', function() {
        var $btn = $(this);
        var postId = $btn.data('post-id');
        
        if (!confirm('Post this to your Facebook Business Page?')) {
            return;
        }
        
        $btn.prop('disabled', true).html('<span class="spinner is-active"></span>');
        
        $.post(ajaxurl, {
            action: 'kaib_post_to_facebook',
            post_id: postId,
            nonce: kaib_ajax.nonce
        }, function(response) {
            if (response.success) {
                $btn.html('<span class="dashicons dashicons-yes-alt"></span>')
                    .removeClass('button-small')
                    .addClass('button-primary')
                    .prop('disabled', true);
                alert('Successfully posted to Facebook!');
            } else {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-facebook-alt"></span>');
                alert('Error: ' + (response.data || 'Failed to post to Facebook'));
            }
        });
    });
    
    // Post to LinkedIn
    $('.kaib-post-linkedin').on('click', function() {
        var $btn = $(this);
        var postId = $btn.data('post-id');
        
        if (!confirm('Post this to your LinkedIn Business Page?')) {
            return;
        }
        
        $btn.prop('disabled', true).html('<span class="spinner is-active"></span>');
        
        $.post(ajaxurl, {
            action: 'kaib_post_to_linkedin',
            post_id: postId,
            nonce: kaib_ajax.nonce
        }, function(response) {
            if (response.success) {
                $btn.html('<span class="dashicons dashicons-yes-alt"></span>')
                    .removeClass('button-small')
                    .addClass('button-primary')
                    .prop('disabled', true);
                alert('Successfully posted to LinkedIn!');
            } else {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-linkedin"></span>');
                alert('Error: ' + (response.data || 'Failed to post to LinkedIn'));
            }
        });
    });
});
</script>
