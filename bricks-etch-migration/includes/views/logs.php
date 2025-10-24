<?php
if (!defined('ABSPATH')) {
    exit;
}
$logs = isset($logs) && is_array($logs) ? $logs : array();
?>
<section class="b2e-card b2e-card--logs">
    <header class="b2e-card__header">
        <h2><?php esc_html_e('Recent Logs', 'bricks-etch-migration'); ?></h2>
        <div class="b2e-card__actions">
            <button type="button" class="button" data-b2e-clear-logs>
                <?php esc_html_e('Clear Logs', 'bricks-etch-migration'); ?>
            </button>
        </div>
    </header>

    <div class="b2e-logs" data-b2e-logs>
        <?php if (empty($logs)) : ?>
            <p class="b2e-log-empty"><?php esc_html_e('No logs yet. Migration activity will appear here.', 'bricks-etch-migration'); ?></p>
        <?php else : ?>
            <?php foreach ($logs as $log) :
                $level = isset($log['level']) ? $log['level'] : 'info';
                $timestamp = isset($log['timestamp']) ? $log['timestamp'] : '';
                $code = isset($log['code']) ? $log['code'] : '';
                $message = isset($log['message']) ? $log['message'] : '';
                ?>
                <article class="b2e-log-entry b2e-log-entry--<?php echo esc_attr($level); ?>">
                    <header class="b2e-log-entry__header">
                        <?php if ($timestamp) : ?>
                            <time datetime="<?php echo esc_attr($timestamp); ?>">
                                <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($timestamp))); ?>
                            </time>
                        <?php endif; ?>
                        <?php if ($code) : ?>
                            <span class="b2e-log-entry__code"><?php echo esc_html($code); ?></span>
                        <?php endif; ?>
                    </header>
                    <p class="b2e-log-entry__message"><?php echo esc_html($message); ?></p>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
