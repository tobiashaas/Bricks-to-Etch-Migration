# GitHub Actions Workflows

**Last Updated:** 2025-10-25

This directory contains CI/CD workflows for the Etch Fusion Suite plugin.

---

## 📋 Workflows Overview

### 1. CI Pipeline (`ci.yml`)

Runs on every push to `main`/`develop` and all pull requests.

**Jobs:**
- **Lint** - WordPress Coding Standards (WPCS) check on PHP 8.1
- **Compatibility** - PHP compatibility check across PHP 7.4, 8.1, 8.2, 8.3, 8.4
- **Test** - PHPUnit tests across all PHP versions

**Local Reproduction:**
```bash
cd etch-fusion-suite

# Run linting
composer lint

# Fix linting issues
composer lint:fix

# Run PHPUnit (requires WP test suite)
composer test

# Run PHPUnit with coverage
composer test:coverage

# Optional: LocalWP AJAX/CSS regression suite
php ../tests/run-local-tests.php
```

### 2. CodeQL Security Scanning (`codeql.yml`)

Runs on:
- Push to `main`
- All pull requests
- Weekly schedule (Monday 6:00 UTC)

**Purpose:** Automated security vulnerability detection using GitHub's CodeQL engine.

**Configuration:** `.github/codeql/codeql-config.yml`

**Reviewing Findings:**
1. Go to repository **Security** tab
2. Click **Code scanning alerts**
3. Review findings and mark false positives
4. Fix genuine security issues

### 3. Dependency Review (`dependency-review.yml`)

Runs on all pull requests.

**Purpose:** Checks new dependencies for:
- Known security vulnerabilities (moderate+ severity)
- License compatibility (allows GPL-2.0-or-later, MIT, BSD-3-Clause)
- Blocks AGPL-3.0 licenses

**Bypass:** Add `dependencies-reviewed` label to PR if manually verified.

### 4. Release Automation (`release.yml`)

Triggers on Git tags matching `v*` (e.g., `v1.0.0`).

**Process:**
1. Validates plugin headers match tag version
2. Installs production dependencies
3. Creates plugin ZIP (excludes dev files)
4. Extracts changelog for release notes
5. Creates GitHub Release with ZIP attachment

**Creating a Release:**
```bash
# 1. Update version in bricks-etch-migration.php
# 2. Update CHANGELOG.md with new version
# 3. Commit changes
git add .
git commit -m "Release v1.0.0"

# 4. Create and push tag
git tag v1.0.0
git push origin v1.0.0

# 5. GitHub Actions automatically creates release
```

---

## 🔒 Security Hardening

All workflows follow security best practices:

### SHA Pinning

Actions are pinned to commit SHAs (not tags) for immutability:
- `actions/checkout@08eba0b27e820071cde6df949e0beb9ba4906955` (v4.3.0)
- `shivammathur/setup-php@bf6b4fbd49ca58e4608c9c89fba0b8d90bd2a39f` (2.35.5)
- `actions/cache@0057852bfaa89a56745cba8c7296529d2fc39830` (v4.3.0)
- `github/codeql-action/*@42213152a85ae7569bdb6bec7bcd74cd691bfe41` (v3.30.9)
- `actions/dependency-review-action@40c09b7dc99638e5ddb0bfd91c1673effc064d8a` (v4.8.1)

### Least-Privilege Permissions

Each workflow declares minimal required permissions:
- CI: `contents: read`
- CodeQL: `actions: read`, `contents: read`, `security-events: write`
- Dependency Review: `contents: read`, `pull-requests: write`
- Release: `contents: write`

---

## 🔄 Dependabot

Automated dependency updates via `.github/dependabot.yml`:

**Update Schedule:**
- Composer dependencies: Weekly (Monday)
- npm dependencies: Weekly (Monday)
- GitHub Actions: Weekly (Monday)

**Grouping:** Minor and patch updates are grouped to reduce PR noise.

**Review Process:**
1. Dependabot creates PR
2. CI runs automatically
3. Review changes and test locally if needed
4. Merge when CI passes

---

## 📊 CI/CD Badges

Add these badges to `README.md`:

```markdown
![CI](https://github.com/[username]/Bricks2Etch/workflows/CI/badge.svg)
![CodeQL](https://github.com/[username]/Bricks2Etch/workflows/CodeQL/badge.svg)
![PHP Version](https://img.shields.io/badge/PHP-7.4%20%7C%208.1%20%7C%208.2%20%7C%208.3%20%7C%208.4-blue)
```

Replace `[username]` with your GitHub username/organization.

---

## 🐛 Troubleshooting

### PHPCS Violations

**Error:** `WordPress.Security.EscapeOutput.OutputNotEscaped`

**Fix:**
```php
// Before
echo $variable;

// After
echo esc_html($variable);
```

**Run locally:**
```bash
composer lint:fix
```

### Test Failures

**Error:** `Failed asserting that false is true`

**Debug:**
1. Run tests locally: `composer test`
2. Check test output for details
3. Fix failing test or code
4. Commit and push

### CodeQL Findings

**False Positive:**
1. Go to Security → Code scanning alerts
2. Click on the alert
3. Click "Dismiss alert" → "False positive"
4. Add comment explaining why

**Genuine Issue:**
1. Review the code path
2. Fix the vulnerability
3. Commit and push
4. CodeQL will re-scan and close alert

### Failed Release

**Error:** `Version mismatch`

**Fix:**
1. Ensure `bricks-etch-migration.php` version matches tag
2. Ensure `CHANGELOG.md` has entry for version
3. Delete tag: `git tag -d v1.0.0 && git push origin :refs/tags/v1.0.0`
4. Fix versions and recreate tag

---

## 📚 Additional Resources

- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [CodeQL for PHP](https://codeql.github.com/docs/codeql-language-guides/codeql-for-php/)
