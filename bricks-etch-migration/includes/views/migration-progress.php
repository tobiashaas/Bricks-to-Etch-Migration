<?php
if (!defined('ABSPATH')) {
    exit;
}
$progress = isset($progress_data) && is_array($progress_data) ? $progress_data : array();
$status = isset($progress['status']) ? $progress['status'] : esc_html__('Awaiting migration start.', 'bricks-etch-migration');
$percentage = isset($progress['percentage']) ? (float) $progress['percentage'] : 0;
$steps = isset($progress['steps']) && is_array($progress['steps']) ? $progress['steps'] : array();
?>
<section class="b2e-card b2e-card--progress">
    <header class="b2e-card__header">
        <h2><?php esc_html_e('Migration Progress', 'bricks-etch-migration'); ?></h2>
        <p data-b2e-current-step><?php echo esc_html($status); ?></p>
    </header>

    <div class="b2e-progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr($percentage); ?>" data-b2e-progress data-b2e-progress-value="<?php echo esc_attr($percentage); ?>">
        <span class="b2e-progress-fill" style="width: <?php echo esc_attr($percentage); ?>%;"></span>
    </div>

    <?php if (!empty($steps)) : ?>
        <ol class="b2e-steps" data-b2e-steps>
            <?php foreach ($steps as $step) :
                $label = isset($step['label']) ? $step['label'] : (isset($step['slug']) ? $step['slug'] : '');
                $is_active = !empty($step['active']);
                $is_complete = !empty($step['completed']);
                ?>
                <li class="b2e-migration-step<?php echo $is_active ? ' is-active' : ''; ?><?php echo $is_complete ? ' is-complete' : ''; ?>">
                    <?php echo esc_html($label); ?>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php else : ?>
        <ol class="b2e-steps" data-b2e-steps></ol>
    <?php endif; ?>

    <footer class="b2e-card__footer">
        <button type="button" class="button" data-b2e-cancel-migration><?php esc_html_e('Cancel Migration', 'bricks-etch-migration'); ?></button>
    </footer>
</section>
