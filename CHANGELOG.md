# Changelog
The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Added
### Changed
### Deprecated
### Removed
### Fixed
### Security


## [1.0.5] - 2019-12-18
### Changed
- composer.lock has been removed from the repository.

## [1.0.4] - 2019-12-18
### Fixed
- Fixed composer dependencies for compatible with PHP 7.1.

## [1.0.3] - 2019-12-17
### Added
- Added correct processing of time intervals defined as set of values. For example both expressions "*/15 * * * *" and "0,15,30,45 * * * *" are the equal and means "Every 15 minutes". But the previous version of library didn't understand it and returned a long text with description of each time unit.
- Added correct processing of "each time unit" defined as full set of values. For example, "0 * * * *" and "0 0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23 * * *" means "Every hour". But the previous version of library didn't understand it and returned a long text with description of each time unit.
- Added the possibility to set the flag "use 24 hours format" in constructor of ExpressionDescriptor.
### Changed
- The helper functions replaced into the util's traits.
- Minor improvements of code.
