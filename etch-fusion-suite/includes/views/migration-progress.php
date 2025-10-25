<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$efs_progress   = isset( $progress_data ) && is_array( $progress_data ) ? $progress_data : array();
$efs_status     = isset( $efs_progress['status'] ) ? $efs_progress['status'] : esc_html__( 'Awaiting migration start.', 'etch-fusion-suite' );
$efs_percentage = isset( $efs_progress['percentage'] ) ? (float) $efs_progress['percentage'] : 0;
$efs_steps      = isset( $efs_progress['steps'] ) && is_array( $efs_progress['steps'] ) ? $efs_progress['steps'] : array();
?>
<section class="b2e-card b2e-card--progress">
	<header class="b2e-card__header">
		<h2><?php esc_html_e( 'Migration Progress', 'etch-fusion-suite' ); ?></h2>
		<p data-efs-current-step><?php echo esc_html( $efs_status ); ?></p>
	</header>

	<div class="b2e-progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( $efs_percentage ); ?>" data-efs-progress data-efs-progress-value="<?php echo esc_attr( $efs_percentage ); ?>">
		<span class="b2e-progress-fill" style="width: <?php echo esc_attr( $efs_percentage ); ?>%;"></span>
	</div>

	<?php if ( ! empty( $efs_steps ) ) : ?>
		<ol class="b2e-steps" data-efs-steps>
			<?php
			foreach ( $efs_steps as $efs_step ) :
				$efs_step_label      = isset( $efs_step['label'] ) ? $efs_step['label'] : ( isset( $efs_step['slug'] ) ? $efs_step['slug'] : '' );
				$efs_step_is_active   = ! empty( $efs_step['active'] );
				$efs_step_is_complete = ! empty( $efs_step['completed'] );
				?>
				<li class="b2e-migration-step<?php echo $efs_step_is_active ? ' is-active' : ''; ?><?php echo $efs_step_is_complete ? ' is-complete' : ''; ?>">
					<?php echo esc_html( $efs_step_label ); ?>
				</li>
			<?php endforeach; ?>
		</ol>
	<?php else : ?>
		<ol class="b2e-steps" data-efs-steps></ol>
	<?php endif; ?>

	<footer class="b2e-card__footer">
		<button type="button" class="button" data-efs-cancel-migration><?php esc_html_e( 'Cancel Migration', 'etch-fusion-suite' ); ?></button>
	</footer>
</section>
