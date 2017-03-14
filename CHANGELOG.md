# Change Log for WordPoints Beta Tester

All notable changes to this project will be documented in this file.

This project adheres to [Semantic Versioning](http://semver.org/) and [Keep a CHANGELOG](http://keepachangelog.com/).

## [Unreleased]

Nothing documented yet.

## [1.1.0] - 2017-03-14

### Added

- POT file and l10n module file headers.

### Changed

- Updates to come from the `develop` branch of WordPoints, instead of `master`, which is now the stable branch. #16
- Stop passing `sslverify` => `true` to `wp_remote_get()`. #14

## [1.0.4] - 2016-07-28

### Fixed

- PHP notice about `Undefined index 'plugin'` when using shiny updates on WordPress 4.2+. #13
- PHP notice `Undefined property: stdClass::$name` when viewing the update details. #12
- PHP warning `failed to open dir: No such file or directory` when updating with shiny updates. #15
- No uninstall tests.
- No acceptance tests. #6
- Source files not being in `src` directory.

## [1.0.3] - 2015-02-25

### Security

- Avoid potential XSS issues by escaping output from translation strings and commit data from GitHub.

### Fixed

- Missing module headers to enable updates from WordPoints.org.

## [1.0.2] - 2014-10-14

### Fixed

- The source directory not being selected correctly during bulk upgrades.

## [1.0.1] - 2014-02-25

### Fixed

- Errors when the response is not an array.

## [1.0.0] - 2014-02-08

Initial release.

[unreleased]: https://github.com/WordPoints/beta-tester/compare/master...HEAD
[1.1.0]: https://github.com/WordPoints/beta-tester/compare/1.0.4...1.1.0
[1.0.4]: https://github.com/WordPoints/beta-tester/compare/1.0.3...1.0.4
[1.0.3]: https://github.com/WordPoints/beta-tester/compare/1.0.2...1.0.3
[1.0.2]: https://github.com/WordPoints/beta-tester/compare/1.0.1...1.0.2
[1.0.1]: https://github.com/WordPoints/beta-tester/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/WordPoints/beta-tester/compare/...1.0.0
