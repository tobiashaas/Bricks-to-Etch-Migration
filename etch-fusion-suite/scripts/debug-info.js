#!/usr/bin/env node

const { spawnSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const WP_ENV_CMD = process.platform === 'win32' ? 'wp-env.cmd' : 'wp-env';

function runCommand(command, args, options = {}) {
  const result = spawnSync(command, args, { encoding: 'utf8', ...options });

  if (result.error) {
    throw result.error;
  }

  return {
    code: result.status,
    stdout: result.stdout.trim(),
    stderr: result.stderr.trim()
  };
}

function runWpEnv(args) {
  const { code, stdout, stderr } = runCommand(WP_ENV_CMD, args);
  if (code !== 0) {
    throw new Error(stderr || stdout || `wp-env ${args.join(' ')} failed`);
  }
  return stdout;
}

function section(title, content) {
  return `## ${title}\n${content}\n`;
}

function capture(name, fn) {
  try {
    const output = fn();
    return section(name, `
	i) ${output.replace(/\n/g, '\n\t')}
`.trim());
  } catch (error) {
    return section(name, `Error: ${error.message}`);
  }
}

function main() {
  const report = [];
  const now = new Date().toISOString();

  report.push(`# Bricks to Etch Debug Report\nGenerated: ${now}\n`);

  report.push(capture('System Information', () => {
    const node = process.version;
    const npm = runCommand('npm', ['-v']).stdout;
    const docker = runCommand('docker', ['version', '--format', '{{.Server.Version}}']).stdout || 'Unavailable';
    const platform = `${process.platform} ${process.arch}`;

    return `Node: ${node}\nNPM: ${npm}\nDocker: ${docker}\nPlatform: ${platform}`;
  }));

  report.push(capture('wp-env Status', () => runCommand(WP_ENV_CMD, ['status']).stdout));

  report.push(capture('WordPress Versions', () => {
    const bricks = runWpEnv(['run', 'cli', 'wp', 'core', 'version']);
    const etch = runWpEnv(['run', 'tests-cli', 'wp', 'core', 'version']);
    return `Bricks: ${bricks}\nEtch: ${etch}`;
  }));

  report.push(capture('Active Plugins (Bricks)', () => runWpEnv(['run', 'cli', 'wp', 'plugin', 'list', '--status=active'])));
  report.push(capture('Active Plugins (Etch)', () => runWpEnv(['run', 'tests-cli', 'wp', 'plugin', 'list', '--status=active'])));

  report.push(capture('Active Themes', () => {
    const bricks = runWpEnv(['run', 'cli', 'wp', 'theme', 'status']);
    const etch = runWpEnv(['run', 'tests-cli', 'wp', 'theme', 'status']);
    return `Bricks:\n${bricks}\n\nEtch:\n${etch}`;
  }));

  report.push(capture('PHP Info', () => {
    const version = runWpEnv(['run', 'cli', 'php', '-v']);
    const modules = runWpEnv(['run', 'cli', 'php', '-m']);
    return `Version:\n${version}\n\nModules:\n${modules}`;
  }));

  report.push(capture('Plugin Settings', () => {
    const settings = runWpEnv(['run', 'cli', 'wp', 'option', 'get', 'b2e_migration_settings']);
    const progress = runWpEnv(['run', 'cli', 'wp', 'option', 'get', 'b2e_migration_progress']);
    return `Settings:\n${settings}\n\nProgress:\n${progress}`;
  }));

  report.push(capture('Composer Packages', () => {
    const composerPath = path.resolve(__dirname, '../vendor/composer/installed.json');
    if (!fs.existsSync(composerPath)) {
      return 'Composer dependencies not installed (vendor/composer/installed.json missing).';
    }
    const data = fs.readFileSync(composerPath, 'utf8');
    return data.length > 2000 ? `${data.slice(0, 2000)}... [truncated]` : data;
  }));

  report.push(capture('Debug Logs', () => runWpEnv(['run', 'cli', 'sh', '-c', 'tail -n 100 wp-content/debug.log'])));

  report.push(capture('wp-env Logs', () => runCommand(WP_ENV_CMD, ['logs']).stdout));

  const reportContent = report.join('\n');
  const outDir = path.resolve(__dirname, '..');
  const outPath = path.join(outDir, `debug-report-${Date.now()}.txt`);
  fs.writeFileSync(outPath, reportContent, 'utf8');

  console.log(`Debug report written to ${outPath}`);
}

main().catch((error) => {
  console.error('Failed to collect debug info:', error.message);
  process.exit(1);
});
