#!/usr/bin/env node

const { spawnSync } = require('child_process');
const fs = require('fs');
const path = require('path');
const createTestContent = require('./create-test-content');

const WP_ENV_CMD = process.platform === 'win32' ? 'wp-env.cmd' : 'wp-env';

function runWpEnv(args) {
  const result = spawnSync(WP_ENV_CMD, args, { encoding: 'utf8' });

  if (result.error) {
    throw result.error;
  }

  if (result.status !== 0) {
    throw new Error(result.stderr || result.stdout || `Command failed: ${args.join(' ')}`);
  }

  return result.stdout.trim();
}

function runWpCli(environmentArgs, commandArgs) {
  return runWpEnv(['run', ...environmentArgs, 'wp', ...commandArgs]);
}

function ensureApplicationPassword() {
  console.log('▶ Creating application password on Etch instance...');
  
  // Create a unique label with timestamp to avoid conflicts
  const label = `b2e-migration-${Date.now()}`;
  
  try {
    const password = runWpCli(['tests-cli'], [
      'user',
      'application-password',
      'create',
      'admin',
      label,
      '--porcelain'
    ]);
    
    if (!password) {
      throw new Error('create --porcelain returned empty password');
    }
    
    return password;
  } catch (error) {
    throw new Error(`Failed to create application password: ${error.message}`);
  }
}

function configurePlugin(targetUrl, apiKey) {
  console.log('▶ Configuring migration settings on Bricks instance...');
  const settings = {
    target_url: targetUrl,
    api_key: apiKey,
    api_username: 'admin'
  };

  runWpCli(['cli'], ['option', 'update', 'b2e_migration_settings', JSON.stringify(settings)]);
}

function triggerMigration() {
  console.log('▶ Triggering migration via WP-CLI...');
  runWpCli(['cli'], ['eval', "do_action('b2e_start_migration_cli');"]);
}

function getProgress() {
  try {
    const progressJson = runWpCli(['cli'], ['option', 'get', 'b2e_migration_progress', '--format=json']);
    
    // Handle empty output
    if (!progressJson || progressJson.trim() === '') {
      return null;
    }
    
    return JSON.parse(progressJson);
  } catch (error) {
    if (error.message.includes('does not exist') || error.message.includes('Could not get')) {
      return null;
    }
    throw error;
  }
}

function waitForCompletion(timeoutMs = 300000, intervalMs = 5000) {
  return new Promise((resolve, reject) => {
    const start = Date.now();

    const check = () => {
      const progress = getProgress();

      if (progress && progress.status === 'completed') {
        console.log('✓ Migration completed successfully');
        resolve(progress);
        return;
      }

      if (progress && progress.status === 'failed') {
        reject(new Error(`Migration failed: ${progress.message || 'Unknown error'}`));
        return;
      }

      if (Date.now() - start > timeoutMs) {
        reject(new Error('Migration timed out'));
        return;
      }

      console.log('… Migration in progress, waiting for next update');
      setTimeout(check, intervalMs);
    };

    check();
  });
}

function collectStats() {
  const sourcePosts = runWpCli(['cli'], ['post', 'list', '--post_type=post', '--format=count']);
  const targetPosts = runWpCli(['tests-cli'], ['post', 'list', '--post_type=post', '--format=count']);

  return {
    sourcePosts: parseInt(sourcePosts, 10),
    targetPosts: parseInt(targetPosts, 10)
  };
}

async function main() {
  console.log('▶ Creating baseline content on Bricks instance...');
  await createTestContent();

  const appPassword = ensureApplicationPassword();
  configurePlugin('http://localhost:8889', appPassword);

  triggerMigration();
  const progress = await waitForCompletion();

  const stats = collectStats();
  const report = {
    timestamp: new Date().toISOString(),
    progress,
    stats
  };

  const reportDir = path.resolve(__dirname, '..');
  const reportPath = path.join(reportDir, `migration-report-${Date.now()}.json`);
  fs.writeFileSync(reportPath, JSON.stringify(report, null, 2));

  console.log('\nMigration report saved to', reportPath);
  console.log('Source posts:', stats.sourcePosts);
  console.log('Target posts:', stats.targetPosts);
}

main().catch((error) => {
  console.error('\n✗ Migration test failed:', error.message);
  process.exit(1);
});
