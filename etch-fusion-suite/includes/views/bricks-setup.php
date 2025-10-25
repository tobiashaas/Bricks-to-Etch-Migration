<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$settings = isset( $settings ) && is_array( $settings ) ? $settings : array();
$nonce    = isset( $nonce ) ? $nonce : '';
?>
<section class="b2e-card b2e-card--source">
	<header class="b2e-card__header">
		<h2><?php esc_html_e( 'Bricks Site Migration Setup', 'etch-fusion-suite' ); ?></h2>
		<p><?php esc_html_e( 'Configure the connection to your Etch target site and start the migration process.', 'etch-fusion-suite' ); ?></p>
	</header>

	<form method="post" class="b2e-form" data-b2e-settings-form>
		<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>" />
		<div class="b2e-field" data-b2e-field>
			<label for="b2e-target-url"><?php esc_html_e( 'Etch Site URL', 'etch-fusion-suite' ); ?></label>
			<input type="url" id="b2e-target-url" name="target_url" value="<?php echo isset( $settings['target_url'] ) ? esc_url( $settings['target_url'] ) : ''; ?>" required />
		</div>
		<div class="b2e-field" data-b2e-field>
			<label for="b2e-api-key"><?php esc_html_e( 'Application Password', 'etch-fusion-suite' ); ?></label>
			<input type="password" id="b2e-api-key" name="api_key" value="<?php echo isset( $settings['api_key'] ) ? esc_attr( $settings['api_key'] ) : ''; ?>" required />
		</div>
		<div class="b2e-actions">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Connection Settings', 'etch-fusion-suite' ); ?></button>
		</div>
	</form>

	<form method="post" class="b2e-inline-form" data-b2e-test-connection>
		<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>" />
		<input type="hidden" name="target_url" value="<?php echo isset( $settings['target_url'] ) ? esc_url( $settings['target_url'] ) : ''; ?>" />
		<input type="hidden" name="api_key" value="<?php echo isset( $settings['api_key'] ) ? esc_attr( $settings['api_key'] ) : ''; ?>" />
		<button type="submit" class="button"><?php esc_html_e( 'Test Connection', 'etch-fusion-suite' ); ?></button>
	</form>

	<section class="b2e-card__section">
		<h3><?php esc_html_e( 'Migration Key', 'etch-fusion-suite' ); ?></h3>
		<form method="post" class="b2e-inline-form" data-b2e-generate-key>
			<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>" />
			<div class="b2e-field" data-b2e-field>
				<label for="b2e-migration-key"><?php esc_html_e( 'Paste Migration Key from Etch', 'etch-fusion-suite' ); ?></label>
				<textarea id="b2e-migration-key" name="migration_key" rows="4" data-b2e-migration-key><?php echo isset( $settings['migration_key'] ) ? esc_textarea( $settings['migration_key'] ) : ''; ?></textarea>
			</div>
			<div class="b2e-actions">
				<button type="submit" class="button"><?php esc_html_e( 'Generate New Key', 'etch-fusion-suite' ); ?></button>
				<button type="button" class="button" data-b2e-copy-button data-b2e-target="#b2e-migration-key" data-toast-success="<?php echo esc_attr__( 'Migration key copied.', 'etch-fusion-suite' ); ?>">
					<?php esc_html_e( 'Copy Key', 'etch-fusion-suite' ); ?>
				</button>
			</div>
		</form>
	</section>

	<section class="b2e-card__section">
		<h3><?php esc_html_e( 'Start Migration', 'etch-fusion-suite' ); ?></h3>
		<form method="post" class="b2e-form" data-b2e-migration-form>
			<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>" />
			<div class="b2e-field" data-b2e-field>
				<label for="b2e-migration-token"><?php esc_html_e( 'Migration Token', 'etch-fusion-suite' ); ?></label>
				<input type="text" id="b2e-migration-token" name="migration_token" required />
			</div>
			<div class="b2e-field" data-b2e-field>
				<label for="b2e-migration-batch-size"><?php esc_html_e( 'Batch Size', 'etch-fusion-suite' ); ?></label>
				<input type="number" id="b2e-migration-batch-size" name="batch_size" value="50" min="1" />
			</div>
			<div class="b2e-actions">
				<button type="submit" class="button button-primary" data-b2e-start-migration>
					<?php esc_html_e( 'Start Migration', 'etch-fusion-suite' ); ?>
				</button>
				<button type="button" class="button" data-b2e-cancel-migration>
					<?php esc_html_e( 'Cancel', 'etch-fusion-suite' ); ?>
				</button>
			</div>
		</form>
	</section>
</section>
