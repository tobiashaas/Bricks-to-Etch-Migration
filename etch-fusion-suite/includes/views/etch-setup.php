<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$nonce    = isset( $nonce ) ? $nonce : '';
$is_https = isset( $is_https ) ? (bool) $is_https : is_ssl();
$site_url = isset( $site_url ) ? $site_url : home_url();
?>
<section class="b2e-card b2e-card--target">
	<header class="b2e-card__header">
		<h2><?php esc_html_e( 'Etch Target Site Setup', 'etch-fusion-suite' ); ?></h2>
		<p><?php esc_html_e( 'Prepare this site to receive content from your Bricks source site.', 'etch-fusion-suite' ); ?></p>
	</header>

	<?php if ( ! $is_https ) : ?>
		<div class="notice notice-warning b2e-notice">
			<h3><?php esc_html_e( 'HTTPS Recommended', 'etch-fusion-suite' ); ?></h3>
			<p><?php esc_html_e( 'Application Passwords work best over HTTPS. For production environments, ensure HTTPS is enabled.', 'etch-fusion-suite' ); ?></p>
		</div>
	<?php endif; ?>

	<section class="b2e-card__section">
		<h3><?php esc_html_e( 'Create an Application Password', 'etch-fusion-suite' ); ?></h3>
		<ol class="b2e-steps">
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

	<section class="b2e-card__section">
		<h3><?php esc_html_e( 'Share This Site URL', 'etch-fusion-suite' ); ?></h3>
		<p><?php esc_html_e( 'Provide this URL to the Bricks site during migration setup.', 'etch-fusion-suite' ); ?></p>
		<div class="b2e-field" data-b2e-field>
			<label for="b2e-site-url"><?php esc_html_e( 'Site URL', 'etch-fusion-suite' ); ?></label>
			<input id="b2e-site-url" type="text" readonly value="<?php echo esc_attr( $site_url ); ?>" />
		</div>
		<div class="b2e-actions">
			<button type="button" class="button" data-b2e-copy data-b2e-copy="#b2e-site-url" data-toast-success="<?php echo esc_attr__( 'Site URL copied to clipboard.', 'etch-fusion-suite' ); ?>">
				<?php esc_html_e( 'Copy URL', 'etch-fusion-suite' ); ?>
			</button>
		</div>
	</section>

	<section class="b2e-card__section">
		<h3><?php esc_html_e( 'Generate Migration Key', 'etch-fusion-suite' ); ?></h3>
		<p><?php esc_html_e( 'Generate a migration key to share with the Bricks source site.', 'etch-fusion-suite' ); ?></p>
		<form method="post" class="b2e-inline-form" data-b2e-generate-key>
			<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>" />
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Generate Key', 'etch-fusion-suite' ); ?></button>
		</form>
		<div class="b2e-field" data-b2e-field>
			<label for="b2e-generated-key"><?php esc_html_e( 'Latest Generated Key', 'etch-fusion-suite' ); ?></label>
			<textarea id="b2e-generated-key" rows="3" readonly data-b2e-migration-key></textarea>
		</div>
		<div class="b2e-actions">
			<button type="button" class="button" data-b2e-copy data-b2e-copy="#b2e-generated-key" data-toast-success="<?php echo esc_attr__( 'Migration key copied to clipboard.', 'etch-fusion-suite' ); ?>">
				<?php esc_html_e( 'Copy Key', 'etch-fusion-suite' ); ?>
			</button>
		</div>
	</section>
</section>
