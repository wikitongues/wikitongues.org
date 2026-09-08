# Test

Runs the full local automated test suite: lint, static analysis, and unit tests.

## Usage
```
/test
```

## Steps

1. Run all three in sequence — stop and report immediately if any fails:

   ```bash
   composer lint
   composer analyse
   composer test
   ```

2. **Report results clearly:**
   - For each command: pass (exit 0) or fail (exit non-zero)
   - On failure: show the relevant error output (not the full log — just the failing lines)
   - On full pass: one-line summary, e.g. "All checks passed — lint ✓, analyse ✓, 202 tests ✓" (use the actual PHPUnit count)

3. **On failure:** do not attempt to fix anything unless the user asks. Just report what broke and where.

## Notes
- Run from the project root (`/Applications/MAMP/htdocs/wikitongues`)
- `composer lint` runs PHPCS against custom theme + plugin code only (scoped by `phpcs.xml`)
- `composer analyse` runs PHPStan with the baseline at `phpstan-baseline.neon`. `reportUnmatchedIgnoredErrors` is on, so the baseline cuts both ways: a new error needs a baseline entry, and *fixing* a previously-suppressed error means **removing** its now-unmatched entry, or the build fails
- `composer test` runs PHPUnit 9.6 with WP_Mock; test files are in `tests/unit/`
- The WP_Mock deprecation notice on PHP 8.5 is cosmetic — not a failure
- CI runs PHP 8.2; local runs PHP 8.5 — discrepancies in type behaviour are possible but rare
