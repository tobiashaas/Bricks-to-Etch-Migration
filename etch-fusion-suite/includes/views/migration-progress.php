<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$b2e_progress   = isset( $progress_data ) && is_array( $progress_data ) ? $progress_data : array();
$b2e_status     = isset( $b2e_progress['status'] ) ? $b2e_progress['status'] : esc_html__( 'Awaiting migration start.', 'bricks-etch-migration' );
$b2e_percentage = isset( $b2e_progress['percentage'] ) ? (float) $b2e_progress['percentage'] : 0;
$b2e_steps      = isset( $b2e_progress['steps'] ) && is_array( $b2e_progress['steps'] ) ? $b2e_progress['steps'] : array();
?>
<section class="b2e-card b2e-card--progress">
	<header class="b2e-card__header">
		<h2><?php esc_html_e( 'Migration Progress', 'bricks-etch-migration' ); ?></h2>
		<p data-b2e-current-step><?php echo esc_html( $b2e_status ); ?></p>
	</header>

	<div class="b2e-progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( $b2e_percentage ); ?>" data-b2e-progress data-b2e-progress-value="<?php echo esc_attr( $b2e_percentage ); ?>">
		<span class="b2e-progress-fill" style="width: <?php echo esc_attr( $b2e_percentage ); ?>%;"></span>
	</div>

	<?php if ( ! empty( $b2e_steps ) ) : ?>
		<ol class="b2e-steps" data-b2e-steps>
			<?php
			foreach ( $b2e_steps as $b2e_step ) :
				$b2e_step_label     = isset( $b2e_step['label'] ) ? $b2e_step['label'] : ( isset( $b2e_step['slug'] ) ? $b2e_step['slug'] : '' );
				$b2e_step_is_active   = ! empty( $b2e_step['active'] );
				$b2e_step_is_complete = ! empty( $b2e_step['completed'] );
				?>
				<li class="b2e-migration-step<?php echo $b2e_step_is_active ? ' is-active' : ''; ?><?php echo $b2e_step_is_complete ? ' is-complete' : ''; ?>">
					<?php echo esc_html( $b2e_step_label ); ?>
				</li>
			<?php endforeach; ?>
		</ol>
	<?php else : ?>
		<ol class="b2e-steps" data-b2e-steps></ol>
	<?php endif; ?>

	<footer class="b2e-card__footer">
		<button type="button" class="button" data-b2e-cancel-migration><?php esc_html_e( 'Cancel Migration', 'bricks-etch-migration' ); ?></button>
	</footer>
</section>
