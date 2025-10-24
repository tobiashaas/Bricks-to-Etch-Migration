<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap b2e-wrap">
	<h1><?php esc_html_e( 'Bricks to Etch Migration', 'bricks-etch-migration' ); ?></h1>

	<?php if ( ! $is_bricks_site && ! $is_etch_site ) : ?>
		<div class="b2e-card b2e-card--warning">
			<h2><?php esc_html_e( 'No Compatible Builder Detected', 'bricks-etch-migration' ); ?></h2>
			<p><?php esc_html_e( 'This plugin requires either Bricks Builder or Etch PageBuilder to be active.', 'bricks-etch-migration' ); ?></p>
			<ul class="b2e-list">
				<li><?php esc_html_e( 'Install and activate Bricks Builder on the source WordPress site.', 'bricks-etch-migration' ); ?></li>
				<li><?php esc_html_e( 'Install and activate Etch PageBuilder on the target WordPress site.', 'bricks-etch-migration' ); ?></li>
			</ul>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>">
					<?php esc_html_e( 'Go to Plugins', 'bricks-etch-migration' ); ?>
				</a>
			</p>
		</div>
	<?php else : ?>
		<section class="b2e-environment">
			<h2><?php esc_html_e( 'Environment Summary', 'bricks-etch-migration' ); ?></h2>
			<ul class="b2e-status-list">
				<li class="<?php echo $is_bricks_site ? 'is-active' : 'is-inactive'; ?>">
					<span class="b2e-status-label"><?php esc_html_e( 'Bricks Builder', 'bricks-etch-migration' ); ?></span>
					<span class="b2e-status-value"><?php echo $is_bricks_site ? esc_html__( 'Detected', 'bricks-etch-migration' ) : esc_html__( 'Not detected', 'bricks-etch-migration' ); ?></span>
				</li>
				<li class="<?php echo $is_etch_site ? 'is-active' : 'is-inactive'; ?>">
					<span class="b2e-status-label"><?php esc_html_e( 'Etch PageBuilder', 'bricks-etch-migration' ); ?></span>
					<span class="b2e-status-value"><?php echo $is_etch_site ? esc_html__( 'Detected', 'bricks-etch-migration' ) : esc_html__( 'Not detected', 'bricks-etch-migration' ); ?></span>
				</li>
				<li>
					<span class="b2e-status-label"><?php esc_html_e( 'Site URL', 'bricks-etch-migration' ); ?></span>
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
	<?php endif; ?>
</div>
