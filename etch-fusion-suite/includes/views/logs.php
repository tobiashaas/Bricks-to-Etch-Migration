<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$efs_container   = function_exists( 'efs_container' ) ? efs_container() : null;
$efs_audit_logger = null;

if ( $efs_container && $efs_container->has( 'audit_logger' ) ) {
	$efs_audit_logger = $efs_container->get( 'audit_logger' );
} elseif ( class_exists( '\Bricks2Etch\Security\EFS_Audit_Logger' ) ) {
	$efs_audit_logger = \Bricks2Etch\Security\EFS_Audit_Logger::get_instance();
}

$efs_logs = $efs_audit_logger ? $efs_audit_logger->get_security_logs() : array();
?>
<section class="efs-card efs-card--logs" data-efs-log-panel>
	<header class="efs-card__header">
		<div class="efs-card__title">
			<h2><?php esc_html_e( 'Recent Logs', 'etch-fusion-suite' ); ?></h2>
			<p class="efs-card__subtitle"><?php esc_html_e( 'Security and migration activity from Etch Fusion Suite.', 'etch-fusion-suite' ); ?></p>
		</div>
		<div class="efs-card__actions">
			<button type="button" class="button button-secondary" data-efs-clear-logs>
				<?php esc_html_e( 'Clear Logs', 'etch-fusion-suite' ); ?>
			</button>
		</div>
	</header>

	<div class="efs-logs" data-efs-logs>
		<?php if ( empty( $efs_logs ) ) : ?>
			<p class="efs-empty-state"><?php esc_html_e( 'No logs yet. Migration activity will appear here.', 'etch-fusion-suite' ); ?></p>
		<?php else : ?>
			<?php foreach ( $efs_logs as $efs_log_entry ) :
				$efs_entry_level     = isset( $efs_log_entry['severity'] ) ? $efs_log_entry['severity'] : 'info';
				$efs_entry_timestamp = isset( $efs_log_entry['timestamp'] ) ? $efs_log_entry['timestamp'] : '';
				$efs_entry_code      = isset( $efs_log_entry['event_type'] ) ? $efs_log_entry['event_type'] : '';
				$efs_entry_message   = isset( $efs_log_entry['message'] ) ? $efs_log_entry['message'] : '';
				$efs_entry_context   = isset( $efs_log_entry['context'] ) && is_array( $efs_log_entry['context'] ) ? $efs_log_entry['context'] : array();
				?>
				<article class="efs-log-entry efs-log-entry--<?php echo esc_attr( $efs_entry_level ); ?>">
					<header class="efs-log-entry__header">
						<div class="efs-log-entry__meta">
							<?php if ( $efs_entry_timestamp ) : ?>
								<time datetime="<?php echo esc_attr( $efs_entry_timestamp ); ?>">
									<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $efs_entry_timestamp ) ) ); ?>
								</time>
							<?php endif; ?>
							<?php if ( $efs_entry_code ) : ?>
								<span class="efs-log-entry__code"><?php echo esc_html( $efs_entry_code ); ?></span>
							<?php endif; ?>
						</div>
						<span class="efs-log-entry__badge efs-log-entry__badge--<?php echo esc_attr( $efs_entry_level ); ?>">
							<?php echo esc_html( ucfirst( $efs_entry_level ) ); ?>
						</span>
					</header>
					<p class="efs-log-entry__message"><?php echo esc_html( $efs_entry_message ); ?></p>
					<?php if ( ! empty( $efs_entry_context ) ) : ?>
						<dl class="efs-log-entry__context">
							<?php foreach ( $efs_entry_context as $context_key => $context_value ) : ?>
								<div class="efs-log-entry__context-item">
									<dt><?php echo esc_html( $context_key ); ?></dt>
									<dd><?php echo esc_html( is_scalar( $context_value ) ? (string) $context_value : wp_json_encode( $context_value ) ); ?></dd>
								</div>
							<?php endforeach; ?>
						</dl>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</section>
