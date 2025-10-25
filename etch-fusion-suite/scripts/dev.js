#!/usr/bin/env node

const { spawn } = require('child_process');
const { join } = require('path');
const waitForWordPress = require('./wait-for-wordpress');

const WP_ENV_CMD = process.platform === 'win32' ? 'wp-env.cmd' : 'wp-env';

function runCommand(command, args, options = {}) {
  return new Promise((resolve, reject) => {
    const child = spawn(command, args, { stdio: 'inherit', ...options });

    child.on('error', reject);

    child.on('exit', (code) => {
      if (code === 0) {
        resolve();
      } else {
        reject(new Error(`${command} ${args.join(' ')} exited with code ${code}`));
      }
    });
  });
}

function runCommandQuiet(command, args, options = {}) {
  return new Promise((resolve, reject) => {
    const child = spawn(command, args, { stdio: 'pipe', ...options });
    let stdout = '';
    let stderr = '';

    child.stdout?.on('data', (data) => {
      stdout += data.toString();
    });

    child.stderr?.on('data', (data) => {
      stderr += data.toString();
    });

    child.on('error', reject);

    child.on('exit', (code) => {
      resolve({ code, stdout, stderr });
    });
  });
}

async function checkComposerInContainer() {
  console.log('▶ Checking for Composer in wp-env container...');
  const result = await runCommandQuiet(WP_ENV_CMD, ['run', 'cli', 'composer', '--version']);
  return result.code === 0;
}

async function main() {
  console.log('▶ Starting WordPress environments via wp-env...');

  await runCommand(WP_ENV_CMD, ['start']);

  console.log('⏳ Waiting for Bricks instance (port 8888)...');
  await waitForWordPress({ port: 8888 });

  console.log('⏳ Waiting for Etch instance (port 8889)...');
  await waitForWordPress({ port: 8889 });

  console.log('▶ Installing Composer dependencies...');
  const hasComposer = await checkComposerInContainer();
  
  if (hasComposer) {
    console.log('✓ Composer found in container, installing dependencies...');
    await runCommand(WP_ENV_CMD, [
      'run',
      'cli',
      '--env-cwd=wp-content/plugins/etch-fusion-suite',
      'composer',
      'install',
      '--no-dev',
      '--optimize-autoloader'
    ]);
  } else {
    console.log('⚠ Composer not found in container, attempting host installation...');
    const { join } = require('path');
    const pluginDir = join(__dirname, '..');
    
    try {
      await runCommand('composer', ['install', '--no-dev', '--optimize-autoloader'], { cwd: pluginDir });
      console.log('✓ Composer dependencies installed from host');
    } catch (error) {
      throw new Error(
        'Composer is not available in the wp-env container or on the host.\n' +
        'Please install Composer locally or bootstrap it in the container.\n' +
        'See README for details.'
      );
    }
  }

  console.log('▶ Activating required plugins and themes...');
  await runCommand('node', [join('scripts', 'activate-plugins.js')]);

  console.log('▶ Generating application password on Etch instance...');
  try {
    await runCommand(WP_ENV_CMD, [
      'run',
      'tests-cli',
      'wp',
      'user',
      'application-password',
      'create',
      'admin',
      'b2e-migration',
      '--porcelain'
    ]);
  } catch (error) {
    console.warn('⚠ Failed to create application password automatically. You may need to create one manually.');
    console.warn(error.message);
  }

  console.log('\n✅ Bricks (Source): http://localhost:8888/wp-admin (admin/password)');
  console.log('✅ Etch (Target): http://localhost:8889/wp-admin (admin/password)');
  console.log('✅ Use npm run wp / npm run wp:etch for WP-CLI access');
}

main().catch((error) => {
  console.error('\n✗ Setup failed:', error.message);
  process.exit(1);
});
