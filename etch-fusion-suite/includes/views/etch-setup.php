<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$etch_fusion_suite_nonce    = isset( $nonce ) ? $nonce : '';
$etch_fusion_suite_is_https = isset( $is_https ) ? (bool) $is_https : is_ssl();
$etch_fusion_suite_site_url = isset( $site_url ) ? $site_url : home_url();
?>
<section class="efs-card efs-card--target">
	<header class="efs-card__header">
		<h2><?php esc_html_e( 'Etch Target Site Setup', 'etch-fusion-suite' ); ?></h2>
		<p><?php esc_html_e( 'Prepare this site to receive content from your Bricks source site.', 'etch-fusion-suite' ); ?></p>
	</header>

	<?php if ( ! $etch_fusion_suite_is_https ) : ?>
		<div class="notice notice-warning efs-notice">
			<h3><?php esc_html_e( 'HTTPS Recommended', 'etch-fusion-suite' ); ?></h3>
			<p><?php esc_html_e( 'Application Passwords work best over HTTPS. For production environments, ensure HTTPS is enabled.', 'etch-fusion-suite' ); ?></p>
		</div>
	<?php endif; ?>

	<section class="efs-card__section">
		<h3><?php esc_html_e( 'Create an Application Password', 'etch-fusion-suite' ); ?></h3>
		<ol class="efs-steps">
			<li><?php esc_html_e( 'Navigate to Users → Profile in this WordPress dashboard.', 'etch-fusion-suite' ); ?></li>
			<li><?php esc_html_e( 'Scroll to Application Passwords and add a new password.', 'etch-fusion-suite' ); ?></li>
			<li><?php esc_html_e( 'Name the password “Etch Fusion Suite” for easy identification.', 'etch-fusion-suite' ); ?></li>
			<li><?php esc_html_e( 'Copy the generated password and use it on your Bricks site.', 'etch-fusion-suite' ); ?></li>
		</ol>
		<p>
			<a class="button" href="<?php echo esc_url( admin_url( 'profile.php#application-passwords-section' ) ); ?>">
				<?php esc_html_e( 'Open Application Passwords', 'etch-fusion-suite' ); ?>
			</a>
		</p>
	</section>

	<section class="efs-card__section">
		<h3><?php esc_html_e( 'Share This Site URL', 'etch-fusion-suite' ); ?></h3>
		<p><?php esc_html_e( 'Provide this URL to the Bricks site during migration setup.', 'etch-fusion-suite' ); ?></p>
		<div class="efs-field" data-efs-field>
			<label for="efs-site-url"><?php esc_html_e( 'Site URL', 'etch-fusion-suite' ); ?></label>
			<input id="efs-site-url" type="text" readonly value="<?php echo esc_attr( $etch_fusion_suite_site_url ); ?>" />
		</div>
		<div class="efs-actions">
			<button type="button" class="button" data-efs-copy data-efs-copy="#efs-site-url" data-toast-success="<?php echo esc_attr__( 'Site URL copied to clipboard.', 'etch-fusion-suite' ); ?>">
				<?php esc_html_e( 'Copy URL', 'etch-fusion-suite' ); ?>
			</button>
		</div>
	</section>

	<section class="efs-card__section">
		<h3><?php esc_html_e( 'Generate Migration Key', 'etch-fusion-suite' ); ?></h3>
		<p><?php esc_html_e( 'Generate a migration key to share with the Bricks source site.', 'etch-fusion-suite' ); ?></p>
		<form method="post" class="efs-inline-form" data-efs-generate-key>
			<input type="hidden" name="nonce" value="<?php echo esc_attr( $etch_fusion_suite_nonce ); ?>" />
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Generate Key', 'etch-fusion-suite' ); ?></button>
		</form>
		<div class="efs-field" data-efs-field>
			<label for="efs-generated-key"><?php esc_html_e( 'Latest Generated Key', 'etch-fusion-suite' ); ?></label>
			<textarea id="efs-generated-key" rows="3" readonly data-efs-migration-key></textarea>
		</div>
		<div class="efs-actions">
			<button type="button" class="button" data-efs-copy data-efs-copy="#efs-generated-key" data-toast-success="<?php echo esc_attr__( 'Migration key copied to clipboard.', 'etch-fusion-suite' ); ?>">
				<?php esc_html_e( 'Copy Key', 'etch-fusion-suite' ); ?>
			</button>
		</div>
	</section>
</section>
