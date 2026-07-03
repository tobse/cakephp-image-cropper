# Contributing

Thanks for your interest in improving **cakephp-image-cropper**! This document
describes how to set up a development environment and the checks your changes
need to pass.

## Getting started

1. Fork the repository and clone your fork.
2. Install the PHP dependencies:

   ```bash
   composer install
   ```

3. Install the Node dependencies (only needed when changing the front-end
   sources under `resources/`):

   ```bash
   npm install
   ```

## Development workflow

- The PHP source lives in `src/`, tests in `tests/`, and the front-end sources
  in `resources/`.
- The compiled assets in `webroot/` are committed. If you change anything under
  `resources/`, rebuild and commit the output:

  ```bash
  npm run build
  ```

- Keep changes focused and add or update tests for any behaviour you change.

## Quality checks

All pull requests are validated by CI. Please run the full suite locally before
opening a pull request:

```bash
composer check      # runs cs-check, stan and test
```

Individually:

```bash
composer cs-check   # coding standard (CakePHP), auto-fix with: composer cs-fix
composer stan       # PHPStan static analysis
composer test       # PHPUnit
```

The project uses:

- **Strict types** in every PHP file (`declare(strict_types=1);`).
- The **CakePHP coding standard** (`cakephp/cakephp-codesniffer`).
- **PHPStan** at the level defined in `phpstan.neon`.

## Pull requests

- Target the default branch.
- Describe the motivation and the change clearly.
- Make sure CI is green.

## Reporting bugs

Open an issue with a clear description, the CakePHP and PHP versions you are
using, and a minimal reproduction if possible.

## License

By contributing, you agree that your contributions will be licensed under the
[MIT License](LICENSE).
