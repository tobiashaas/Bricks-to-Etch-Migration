<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$settings = isset( $settings ) && is_array( $settings ) ? $settings : array();
$nonce    = isset( $nonce ) ? $nonce : '';
?>
<section class="b2e-card b2e-card--source">
	<header class="b2e-card__header">
		<h2><?php esc_html_e( 'EFS Site Migration Setup', 'etch-fusion-suite' ); ?></h2>
		<p><?php esc_html_e( 'Configure the connection to your Etch target site and start the migration process.', 'etch-fusion-suite' ); ?></p>
	</header>

	<form method="post" class="b2e-form" data-efs-settings-form>
		<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>" />
		<div class="b2e-field" data-efs-field>
			<label for="efs-target-url"><?php esc_html_e( 'Etch Site URL', 'etch-fusion-suite' ); ?></label>
			<input type="url" id="efs-target-url" name="target_url" value="<?php echo isset( $settings['target_url'] ) ? esc_url( $settings['target_url'] ) : ''; ?>" required />
		</div>
		<div class="b2e-field" data-efs-field>
			<label for="efs-api-key"><?php esc_html_e( 'Application Password', 'etch-fusion-suite' ); ?></label>
			<input type="password" id="efs-api-key" name="api_key" value="<?php echo isset( $settings['api_key'] ) ? esc_attr( $settings['api_key'] ) : ''; ?>" required />
		</div>
		<div class="b2e-actions">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Connection Settings', 'etch-fusion-suite' ); ?></button>
		</div>
	</form>

	<form method="post" class="b2e-inline-form" data-efs-test-connection>
		<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>" />
		<input type="hidden" name="target_url" value="<?php echo isset( $settings['target_url'] ) ? esc_url( $settings['target_url'] ) : ''; ?>" />
		<input type="hidden" name="api_key" value="<?php echo isset( $settings['api_key'] ) ? esc_attr( $settings['api_key'] ) : ''; ?>" />
		<button type="submit" class="button"><?php esc_html_e( 'Test Connection', 'etch-fusion-suite' ); ?></button>
	</form>

	<section class="b2e-card__section">
		<h3><?php esc_html_e( 'Migration Key', 'etch-fusion-suite' ); ?></h3>
		<form method="post" class="b2e-inline-form" data-efs-generate-key>
			<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>" />
			<div class="b2e-field" data-efs-field>
				<label for="efs-migration-key"><?php esc_html_e( 'Paste Migration Key from Etch', 'etch-fusion-suite' ); ?></label>
				<textarea id="efs-migration-key" name="migration_key" rows="4" data-efs-migration-key><?php echo isset( $settings['migration_key'] ) ? esc_textarea( $settings['migration_key'] ) : ''; ?></textarea>
			</div>
			<div class="b2e-actions">
				<button type="submit" class="button"><?php esc_html_e( 'Generate New Key', 'etch-fusion-suite' ); ?></button>
				<button type="button" class="button" data-efs-copy-button data-efs-target="#efs-migration-key" data-toast-success="<?php echo esc_attr__( 'Migration key copied.', 'etch-fusion-suite' ); ?>">
					<?php esc_html_e( 'Copy Key', 'etch-fusion-suite' ); ?>
				</button>
			</div>
		</form>
	</section>

	<section class="b2e-card__section">
		<h3><?php esc_html_e( 'Start Migration', 'etch-fusion-suite' ); ?></h3>
		<form method="post" class="b2e-form" data-efs-migration-form>
			<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>" />
			<div class="b2e-field" data-efs-field>
				<label for="efs-migration-token"><?php esc_html_e( 'Migration Token', 'etch-fusion-suite' ); ?></label>
				<input type="text" id="efs-migration-token" name="migration_token" required />
			</div>
			<div class="b2e-field" data-efs-field>
				<label for="efs-migration-batch-size"><?php esc_html_e( 'Batch Size', 'etch-fusion-suite' ); ?></label>
				<input type="number" id="efs-migration-batch-size" name="batch_size" value="50" min="1" />
			</div>
			<div class="b2e-actions">
				<button type="submit" class="button button-primary" data-efs-start-migration>
					<?php esc_html_e( 'Start Migration', 'etch-fusion-suite' ); ?>
				</button>
				<button type="button" class="button" data-efs-cancel-migration>
					<?php esc_html_e( 'Cancel', 'etch-fusion-suite' ); ?>
				</button>
			</div>
		</form>
	</section>
</section>
