<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$efs_logger        = class_exists( '\\Bricks2Etch\\Security\\EFS_Security_Logger' )
	? \Bricks2Etch\Security\EFS_Security_Logger::get_instance()
	: ( class_exists( 'B2E_Security_Logger' ) ? B2E_Security_Logger::get_instance() : null );

$efs_logs          = $efs_logger ? $efs_logger->get_security_logs() : array();
$efs_filter_level  = isset( $_GET['level'] ) ? sanitize_text_field( wp_unslash( $_GET['level'] ) ) : 'all';
$efs_filter_time   = isset( $_GET['timestamp'] ) ? sanitize_text_field( wp_unslash( $_GET['timestamp'] ) ) : 'all';
$efs_filter_code   = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : 'all';
$efs_filter_search = isset( $_GET['message'] ) ? sanitize_text_field( wp_unslash( $_GET['message'] ) ) : '';
?>
<section class="b2e-card b2e-card--logs">
	<header class="b2e-card__header">
		<h2><?php esc_html_e( 'Recent Logs', 'etch-fusion-suite' ); ?></h2>
		<div class="b2e-card__actions">
			<button type="button" class="button" data-efs-clear-logs>
				<?php esc_html_e( 'Clear Logs', 'etch-fusion-suite' ); ?>
			</button>
		</div>
	</header>

	<div class="b2e-logs" data-efs-logs>
		<?php if ( empty( $efs_logs ) ) : ?>
			<p class="b2e-log-empty"><?php esc_html_e( 'No logs yet. Migration activity will appear here.', 'etch-fusion-suite' ); ?></p>
		<?php else : ?>
			<?php
			foreach ( $efs_logs as $efs_log_entry ) :
				$efs_entry_level     = isset( $efs_log_entry['level'] ) ? $efs_log_entry['level'] : 'info';
				$efs_entry_timestamp = isset( $efs_log_entry['timestamp'] ) ? $efs_log_entry['timestamp'] : '';
				$efs_entry_code      = isset( $efs_log_entry['code'] ) ? $efs_log_entry['code'] : '';
				$efs_entry_message   = isset( $efs_log_entry['message'] ) ? $efs_log_entry['message'] : '';
				?>
				<article class="b2e-log-entry b2e-log-entry--<?php echo esc_attr( $efs_entry_level ); ?>">
					<header class="b2e-log-entry__header">
						<?php if ( $efs_entry_timestamp ) : ?>
							<time datetime="<?php echo esc_attr( $efs_entry_timestamp ); ?>">
								<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $efs_entry_timestamp ) ) ); ?>
							</time>
						<?php endif; ?>
						<?php if ( $efs_entry_code ) : ?>
							<span class="b2e-log-entry__code"><?php echo esc_html( $efs_entry_code ); ?></span>
						<?php endif; ?>
					</header>
					<p class="b2e-log-entry__message"><?php echo esc_html( $efs_entry_message ); ?></p>
				</article>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</section>
