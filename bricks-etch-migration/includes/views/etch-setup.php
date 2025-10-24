<?php
if (!defined('ABSPATH')) {
    exit;
}
$nonce = isset($nonce) ? $nonce : '';
$is_https = isset($is_https) ? (bool) $is_https : is_ssl();
$site_url = isset($site_url) ? $site_url : home_url();
?>
<section class="b2e-card b2e-card--target">
    <header class="b2e-card__header">
        <h2><?php esc_html_e('Etch Target Site Setup', 'bricks-etch-migration'); ?></h2>
        <p><?php esc_html_e('Prepare this site to receive content from your Bricks source site.', 'bricks-etch-migration'); ?></p>
    </header>

    <?php if (!$is_https) : ?>
        <div class="notice notice-warning b2e-notice">
            <h3><?php esc_html_e('HTTPS Recommended', 'bricks-etch-migration'); ?></h3>
            <p><?php esc_html_e('Application Passwords work best over HTTPS. For production environments, ensure HTTPS is enabled.', 'bricks-etch-migration'); ?></p>
        </div>
    <?php endif; ?>

    <section class="b2e-card__section">
        <h3><?php esc_html_e('Create an Application Password', 'bricks-etch-migration'); ?></h3>
        <ol class="b2e-steps">
            <li><?php esc_html_e('Navigate to Users → Profile in this WordPress dashboard.', 'bricks-etch-migration'); ?></li>
            <li><?php esc_html_e('Scroll to Application Passwords and add a new password.', 'bricks-etch-migration'); ?></li>
            <li><?php esc_html_e('Name the password “B2E Migration” for easy identification.', 'bricks-etch-migration'); ?></li>
            <li><?php esc_html_e('Copy the generated password and use it on your Bricks site.', 'bricks-etch-migration'); ?></li>
        </ol>
        <p>
            <a class="button" href="<?php echo esc_url(admin_url('profile.php#application-passwords-section')); ?>">
                <?php esc_html_e('Open Application Passwords', 'bricks-etch-migration'); ?>
            </a>
        </p>
    </section>

    <section class="b2e-card__section">
        <h3><?php esc_html_e('Share This Site URL', 'bricks-etch-migration'); ?></h3>
        <p><?php esc_html_e('Provide this URL to the Bricks site during migration setup.', 'bricks-etch-migration'); ?></p>
        <div class="b2e-field" data-b2e-field>
            <label for="b2e-site-url"><?php esc_html_e('Site URL', 'bricks-etch-migration'); ?></label>
            <input id="b2e-site-url" type="text" readonly value="<?php echo esc_attr($site_url); ?>" />
        </div>
        <div class="b2e-actions">
            <button type="button" class="button" data-b2e-copy data-b2e-copy="#b2e-site-url" data-toast-success="<?php echo esc_attr__('Site URL copied to clipboard.', 'bricks-etch-migration'); ?>">
                <?php esc_html_e('Copy URL', 'bricks-etch-migration'); ?>
            </button>
        </div>
    </section>

    <section class="b2e-card__section">
        <h3><?php esc_html_e('Generate Migration Key', 'bricks-etch-migration'); ?></h3>
        <p><?php esc_html_e('Generate a migration key to share with the Bricks source site.', 'bricks-etch-migration'); ?></p>
        <form method="post" class="b2e-inline-form" data-b2e-generate-key>
            <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>" />
            <button type="submit" class="button button-primary"><?php esc_html_e('Generate Key', 'bricks-etch-migration'); ?></button>
        </form>
        <div class="b2e-field" data-b2e-field>
            <label for="b2e-generated-key"><?php esc_html_e('Latest Generated Key', 'bricks-etch-migration'); ?></label>
            <textarea id="b2e-generated-key" rows="3" readonly data-b2e-migration-key></textarea>
        </div>
        <div class="b2e-actions">
            <button type="button" class="button" data-b2e-copy data-b2e-copy="#b2e-generated-key" data-toast-success="<?php echo esc_attr__('Migration key copied to clipboard.', 'bricks-etch-migration'); ?>">
                <?php esc_html_e('Copy Key', 'bricks-etch-migration'); ?>
            </button>
        </div>
    </section>
</section>
