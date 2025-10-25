<?php
/**
 * Template Extractor View
 * 
 * UI for importing Framer templates into Etch.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$saved_templates = isset( $saved_templates ) ? $saved_templates : array();
$nonce           = isset( $nonce ) ? $nonce : wp_create_nonce( 'efs_nonce' );
?>

<div class="efs-template-extractor" data-efs-template-extractor>
	<header class="efs-section-header">
		<h2><?php esc_html_e( 'Framer Template Extractor', 'etch-fusion-suite' ); ?></h2>
		<p><?php esc_html_e( 'Import Framer templates directly into Etch. Extract from a live Framer URL or paste HTML code.', 'etch-fusion-suite' ); ?></p>
	</header>

	<!-- Input Section -->
	<section class="efs-card">
		<div class="efs-tabs">
			<button class="efs-tab active" data-efs-tab="url"><?php esc_html_e( 'From URL', 'etch-fusion-suite' ); ?></button>
			<button class="efs-tab" data-efs-tab="html"><?php esc_html_e( 'From HTML', 'etch-fusion-suite' ); ?></button>
		</div>

		<!-- URL Tab -->
		<div class="efs-tab-content active" data-efs-tab-content="url">
			<form data-efs-extract-url-form>
				<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>">
				<div class="efs-form-group">
					<label for="framer_url"><?php esc_html_e( 'Framer Website URL', 'etch-fusion-suite' ); ?></label>
					<input 
						type="url" 
						id="framer_url" 
						name="framer_url" 
						class="efs-input" 
						placeholder="https://example.framer.website/"
						required
					>
					<p class="efs-help-text"><?php esc_html_e( 'Enter the full URL of your published Framer website.', 'etch-fusion-suite' ); ?></p>
				</div>
				<button type="submit" class="button button-primary">
					<?php esc_html_e( 'Extract Template', 'etch-fusion-suite' ); ?>
				</button>
			</form>
		</div>

		<!-- HTML Tab -->
		<div class="efs-tab-content" data-efs-tab-content="html">
			<form data-efs-extract-html-form>
				<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>">
				<div class="efs-form-group">
					<label for="framer_html"><?php esc_html_e( 'Framer HTML Code', 'etch-fusion-suite' ); ?></label>
					<textarea 
						id="framer_html" 
						name="framer_html" 
						class="efs-textarea" 
						rows="10"
						placeholder="<html>...</html>"
						required
					></textarea>
					<p class="efs-help-text"><?php esc_html_e( 'Paste the complete HTML source code from your Framer page.', 'etch-fusion-suite' ); ?></p>
				</div>
				<button type="submit" class="button button-primary">
					<?php esc_html_e( 'Extract from HTML', 'etch-fusion-suite' ); ?>
				</button>
			</form>
		</div>
	</section>

	<!-- Progress Section -->
	<section class="efs-card hidden" data-efs-template-progress>
		<h3><?php esc_html_e( 'Extraction Progress', 'etch-fusion-suite' ); ?></h3>
		<div class="efs-progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
			<span class="efs-progress-fill" data-progress-bar style="width: 0%;"></span>
		</div>
		<p class="efs-status-text" data-status-text><?php esc_html_e( 'Starting extraction...', 'etch-fusion-suite' ); ?></p>
		<ol class="efs-steps-list" data-steps-list></ol>
	</section>

	<!-- Preview Section -->
	<section class="efs-card hidden" data-efs-template-preview>
		<h3><?php esc_html_e( 'Template Preview', 'etch-fusion-suite' ); ?></h3>
		
		<div class="efs-template-metadata" data-template-metadata>
			<!-- Metadata populated by JS -->
		</div>

		<div class="efs-blocks-preview" data-blocks-preview>
			<!-- Block preview populated by JS -->
		</div>

		<div class="efs-form-group">
			<label for="template_name"><?php esc_html_e( 'Template Name', 'etch-fusion-suite' ); ?></label>
			<input 
				type="text" 
				id="template_name" 
				name="template_name" 
				class="efs-input" 
				placeholder="<?php esc_attr_e( 'My Framer Template', 'etch-fusion-suite' ); ?>"
			>
		</div>

		<button type="button" class="button button-primary" data-save-template-btn>
			<?php esc_html_e( 'Save Template', 'etch-fusion-suite' ); ?>
		</button>
	</section>

	<!-- Saved Templates Section -->
	<section class="b2e-card">
		<h3><?php esc_html_e( 'Saved Templates', 'etch-fusion-suite' ); ?></h3>
		<div class="b2e-saved-templates" data-saved-templates-list>
			<p class="b2e-loading"><?php esc_html_e( 'Loading templates...', 'etch-fusion-suite' ); ?></p>
		</div>
	</section>
</div>

<style>
.b2e-template-extractor { max-width: 1200px; margin: 20px 0; }
.b2e-section-header { margin-bottom: 20px; }
.b2e-section-header h2 { margin: 0 0 10px; }
.b2e-section-header p { color: #666; margin: 0; }

.b2e-tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #ddd; }
.b2e-tab { padding: 10px 20px; background: none; border: none; border-bottom: 2px solid transparent; cursor: pointer; font-size: 14px; }
.b2e-tab.active { border-bottom-color: #2271b1; color: #2271b1; font-weight: 600; }
.b2e-tab-content { display: none; padding: 20px 0; }
.b2e-tab-content.active { display: block; }

.b2e-form-group { margin-bottom: 20px; }
.b2e-form-group label { display: block; margin-bottom: 8px; font-weight: 600; }
.b2e-input, .b2e-textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
.b2e-textarea { font-family: monospace; font-size: 13px; }
.b2e-help-text { margin: 8px 0 0; font-size: 13px; color: #666; }

.b2e-progress-bar { width: 100%; height: 30px; background: #f0f0f0; border-radius: 4px; overflow: hidden; margin: 15px 0; }
.b2e-progress-fill { display: block; height: 100%; background: #2271b1; transition: width 0.3s ease; }
.b2e-status-text { margin: 10px 0; font-weight: 600; }
.b2e-steps-list { list-style: none; padding: 0; margin: 15px 0; }
.b2e-steps-list li { padding: 8px 12px; margin: 5px 0; background: #f5f5f5; border-radius: 4px; }
.b2e-steps-list li.active { background: #2271b1; color: #fff; }

.b2e-template-metadata { padding: 15px; background: #f9f9f9; border-radius: 4px; margin-bottom: 20px; }
.b2e-template-metadata h3 { margin: 0 0 10px; }
.template-stats { display: flex; gap: 20px; margin-top: 10px; font-size: 13px; color: #666; }

.b2e-blocks-preview { margin: 20px 0; }
.block-preview { padding: 15px; background: #f5f5f5; border-left: 3px solid #2271b1; margin: 10px 0; font-family: monospace; font-size: 12px; overflow-x: auto; }

.b2e-saved-templates { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px; margin-top: 15px; }
.template-item { padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 4px; }
.template-item h4 { margin: 0 0 8px; font-size: 16px; }
.template-date { margin: 0 0 12px; font-size: 13px; color: #666; }
.template-actions { display: flex; gap: 8px; }
.no-templates { text-align: center; padding: 40px; color: #999; }

.hidden { display: none !important; }
</style>

<script>
// Tab switching
document.querySelectorAll('[data-tab]').forEach(tab => {
	tab.addEventListener('click', () => {
		const tabName = tab.dataset.tab;
		document.querySelectorAll('[data-tab]').forEach(t => t.classList.remove('active'));
		document.querySelectorAll('[data-tab-content]').forEach(c => c.classList.remove('active'));
		tab.classList.add('active');
		document.querySelector(`[data-tab-content="${tabName}"]`)?.classList.add('active');
	});
});
</script>
