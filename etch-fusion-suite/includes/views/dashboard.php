<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap b2e-wrap">
	<h1><?php esc_html_e( 'Etch Fusion Suite', 'etch-fusion-suite' ); ?></h1>

	<?php if ( ! $is_bricks_site && ! $is_etch_site ) : ?>
		<div class="b2e-card b2e-card--warning">
			<h2><?php esc_html_e( 'No Compatible Builder Detected', 'etch-fusion-suite' ); ?></h2>
			<p><?php esc_html_e( 'Etch Fusion Suite requires either Bricks Builder or Etch PageBuilder running on the source site.', 'etch-fusion-suite' ); ?></p>
			<ul class="b2e-list">
				<li><?php esc_html_e( 'Install and activate Bricks Builder on the source WordPress site.', 'etch-fusion-suite' ); ?></li>
				<li><?php esc_html_e( 'Install and activate Etch PageBuilder on the target WordPress site.', 'etch-fusion-suite' ); ?></li>
			</ul>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>">
					<?php esc_html_e( 'Go to Plugins', 'etch-fusion-suite' ); ?>
				</a>
			</p>
		</div>
	<?php else : ?>
		<section class="b2e-environment">
			<h2><?php esc_html_e( 'Environment Summary', 'etch-fusion-suite' ); ?></h2>
			<ul class="b2e-status-list">
				<li class="<?php echo $is_bricks_site ? 'is-active' : 'is-inactive'; ?>">
					<span class="b2e-status-label"><?php esc_html_e( 'Bricks Builder', 'etch-fusion-suite' ); ?></span>
					<span class="b2e-status-value"><?php echo $is_bricks_site ? esc_html__( 'Detected', 'etch-fusion-suite' ) : esc_html__( 'Not detected', 'etch-fusion-suite' ); ?></span>
				</li>
				<li class="<?php echo $is_etch_site ? 'is-active' : 'is-inactive'; ?>">
					<span class="b2e-status-label"><?php esc_html_e( 'Etch PageBuilder', 'etch-fusion-suite' ); ?></span>
					<span class="b2e-status-value"><?php echo $is_etch_site ? esc_html__( 'Detected', 'etch-fusion-suite' ) : esc_html__( 'Not detected', 'etch-fusion-suite' ); ?></span>
				</li>
				<li>
					<span class="b2e-status-label"><?php esc_html_e( 'Site URL', 'etch-fusion-suite' ); ?></span>
					<span class="b2e-status-value"><?php echo esc_html( $site_url ); ?></span>
				</li>
			</ul>
		</section>

		<div class="b2e-dashboard-layout">
			<?php if ( $is_bricks_site ) : ?>
				<?php require __DIR__ . '/bricks-setup.php'; ?>
			<?php endif; ?>

			<?php if ( $is_etch_site ) : ?>
				<?php require __DIR__ . '/etch-setup.php'; ?>
			<?php endif; ?>
		</div>

		<section class="b2e-dashboard-panels">
			<?php require __DIR__ . '/migration-progress.php'; ?>
			<?php require __DIR__ . '/logs.php'; ?>
		</section>

		<?php if ( $is_etch_site ) : ?>
			<section class="b2e-dashboard-template-extractor">
				<?php
				$extractor_nonce   = isset( $nonce ) ? $nonce : wp_create_nonce( 'b2e_nonce' );
				$saved_templates    = isset( $saved_templates ) ? $saved_templates : array();
				require __DIR__ . '/template-extractor.php';
				?>
			</section>
		<?php endif; ?>
	<?php endif; ?>
</div>
