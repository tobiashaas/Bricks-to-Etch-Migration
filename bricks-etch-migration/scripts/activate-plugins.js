#!/usr/bin/env node

const { spawn } = require('child_process');
const { existsSync } = require('fs');
const { join } = require('path');

const WP_ENV_CMD = process.platform === 'win32' ? 'wp-env.cmd' : 'wp-env';

function runTask(label, args) {
  return new Promise((resolve) => {
    const child = spawn(WP_ENV_CMD, args, { stdio: 'pipe' });

    let output = '';
    let errorOutput = '';

    child.stdout.on('data', (data) => {
      output += data.toString();
    });

    child.stderr.on('data', (data) => {
      errorOutput += data.toString();
    });

    child.on('close', (code) => {
      if (code === 0) {
        console.log(`✓ ${label}`);
        resolve({ success: true, output });
      } else {
        const message = errorOutput.trim() || output.trim() || `Exit code ${code}`;
        console.warn(`⚠ ${label} (skipped) — ${message}`);
        resolve({ success: false, output: message });
      }
    });

    child.on('error', (error) => {
      console.error(`✗ ${label} — ${error.message}`);
      resolve({ success: false, output: error.message });
    });
  });
}

function getInstalledPlugins(environment) {
  return new Promise((resolve) => {
    const args = ['run', environment, 'wp', 'plugin', 'list', '--field=name', '--format=json'];
    const child = spawn(WP_ENV_CMD, args, { stdio: 'pipe' });
    
    let output = '';
    
    child.stdout.on('data', (data) => {
      output += data.toString();
    });
    
    child.on('close', (code) => {
      if (code === 0) {
        try {
          resolve(JSON.parse(output));
        } catch (error) {
          console.warn(`⚠ Failed to parse plugin list for ${environment}`);
          resolve([]);
        }
      } else {
        console.warn(`⚠ Failed to list plugins for ${environment}`);
        resolve([]);
      }
    });
    
    child.on('error', () => {
      resolve([]);
    });
  });
}

function findPluginSlug(installedPlugins, expectedNames) {
  for (const name of expectedNames) {
    const found = installedPlugins.find(plugin => 
      plugin.toLowerCase().includes(name.toLowerCase()) ||
      name.toLowerCase().includes(plugin.toLowerCase())
    );
    if (found) return found;
  }
  return null;
}

function getInstalledThemes(environment) {
  return new Promise((resolve) => {
    const args = ['run', environment, 'wp', 'theme', 'list', '--field=name', '--format=json'];
    const child = spawn(WP_ENV_CMD, args, { stdio: 'pipe' });
    
    let output = '';
    
    child.stdout.on('data', (data) => {
      output += data.toString();
    });
    
    child.on('close', (code) => {
      if (code === 0) {
        try {
          resolve(JSON.parse(output));
        } catch (error) {
          console.warn(`⚠ Failed to parse theme list for ${environment}`);
          resolve([]);
        }
      } else {
        console.warn(`⚠ Failed to list themes for ${environment}`);
        resolve([]);
      }
    });
    
    child.on('error', () => {
      resolve([]);
    });
  });
}

function findThemeSlug(installedThemes, expectedNames) {
  for (const name of expectedNames) {
    const found = installedThemes.find(theme => 
      theme.toLowerCase().includes(name.toLowerCase()) ||
      name.toLowerCase().includes(theme.toLowerCase())
    );
    if (found) return found;
  }
  return null;
}

async function main() {
  // Check if vendor/autoload.php exists before activating migration plugin
  const vendorPath = join(__dirname, '..', 'vendor', 'autoload.php');
  const hasVendor = existsSync(vendorPath);
  
  if (!hasVendor) {
    console.warn('⚠ vendor/autoload.php not found. Skipping migration plugin activation.');
    console.warn('  Run Composer install first to activate the migration plugin.');
  }

  console.log('▶ Discovering installed plugins and themes...');
  const [devPlugins, testPlugins, devThemes, testThemes] = await Promise.all([
    getInstalledPlugins('cli'),
    getInstalledPlugins('tests-cli'),
    getInstalledThemes('cli'),
    getInstalledThemes('tests-cli')
  ]);

  const tasks = [];
  
  // Development environment plugins
  const bricksSlug = findPluginSlug(devPlugins, ['bricks']);
  if (bricksSlug) {
    tasks.push({ label: 'Activate Bricks on development', args: ['run', 'cli', 'wp', 'plugin', 'activate', bricksSlug] });
  } else {
    console.warn('⚠ Bricks plugin not found in development environment');
  }
  
  const framesSlug = findPluginSlug(devPlugins, ['frames']);
  if (framesSlug) {
    tasks.push({ label: 'Activate Frames on development', args: ['run', 'cli', 'wp', 'plugin', 'activate', framesSlug] });
  } else {
    console.warn('⚠ Frames plugin not found in development environment');
  }
  
  const acssDevSlug = findPluginSlug(devPlugins, ['automatic-css', 'automatic.css', 'automattic-css']);
  if (acssDevSlug) {
    tasks.push({ label: 'Activate Automatic.css on development', args: ['run', 'cli', 'wp', 'plugin', 'activate', acssDevSlug] });
  } else {
    console.warn('⚠ Automatic.css plugin not found in development environment');
  }
  
  // Development environment themes
  const bricksChildSlug = findThemeSlug(devThemes, ['bricks-child']);
  if (bricksChildSlug) {
    tasks.push({ label: 'Activate Bricks Child on development', args: ['run', 'cli', 'wp', 'theme', 'activate', bricksChildSlug] });
  } else {
    console.warn('⚠ Bricks Child theme not found in development environment');
  }
  
  // Test environment plugins
  const etchSlug = findPluginSlug(testPlugins, ['etch']);
  if (etchSlug) {
    tasks.push({ label: 'Activate Etch on tests', args: ['run', 'tests-cli', 'wp', 'plugin', 'activate', etchSlug] });
  } else {
    console.warn('⚠ Etch plugin not found in test environment');
  }
  
  const acssTestSlug = findPluginSlug(testPlugins, ['automatic-css', 'automatic.css', 'automattic-css']);
  if (acssTestSlug) {
    tasks.push({ label: 'Activate Automatic.css on tests', args: ['run', 'tests-cli', 'wp', 'plugin', 'activate', acssTestSlug] });
  } else {
    console.warn('⚠ Automatic.css plugin not found in test environment');
  }
  
  // Test environment themes
  const etchThemeSlug = findThemeSlug(testThemes, ['etch-theme', 'etch']);
  if (etchThemeSlug) {
    tasks.push({ label: 'Activate Etch Theme on tests', args: ['run', 'tests-cli', 'wp', 'theme', 'activate', etchThemeSlug] });
  } else {
    console.warn('⚠ Etch Theme not found in test environment');
  }
  
  // Only add migration plugin activation if vendor exists
  if (hasVendor) {
    const migrationDevSlug = findPluginSlug(devPlugins, ['bricks-etch-migration']);
    const migrationTestSlug = findPluginSlug(testPlugins, ['bricks-etch-migration']);
    
    if (migrationDevSlug) {
      tasks.push({ label: 'Activate migration plugin on development', args: ['run', 'cli', 'wp', 'plugin', 'activate', migrationDevSlug] });
    }
    
    if (migrationTestSlug) {
      tasks.push({ label: 'Activate migration plugin on tests', args: ['run', 'tests-cli', 'wp', 'plugin', 'activate', migrationTestSlug] });
    }
  }

  console.log(`\n▶ Activating ${tasks.length} plugins and themes...\n`);
  for (const task of tasks) {
    await runTask(task.label, task.args);
  }
}

main().catch((error) => {
  console.error('Plugin activation failed:', error.message);
  process.exit(1);
});
