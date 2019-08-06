Cron Expression Descriptor
===========================
Converts cron expressions into human readable descriptions.

[![Build Status](https://travis-ci.org/panlatent/cron-expression-descriptor.svg)](https://travis-ci.org/panlatent/cron-expression-descriptor)
[![Coverage Status](https://coveralls.io/repos/github/panlatent/cron-expression-descriptor/badge.svg)](https://coveralls.io/github/panlatent/cron-expression-descriptor)
[![Latest Stable Version](https://poser.pugx.org/panlatent/cron-expression-descriptor/v/stable.svg)](https://packagist.org/packages/panlatent/cron-expression-descriptor)
[![Total Downloads](https://poser.pugx.org/panlatent/cron-expression-descriptor/downloads.svg)](https://packagist.org/packages/panlatent/cron-expression-descriptor) 
[![Latest Unstable Version](https://poser.pugx.org/panlatent/cron-expression-descriptor/v/unstable.svg)](https://packagist.org/packages/panlatent/cron-expression-descriptor)
[![License](https://poser.pugx.org/panlatent/cron-expression-descriptor/license.svg)](https://packagist.org/packages/panlatent/cron-expression-descriptor)

The library is PHP version of [bradymholt/cron-expression-descriptor (C#)](https://github.com/bradymholt/cron-expression-descriptor).

Installation
------------
It's recommended that you use [Composer](https://getcomposer.org/) to install this project.

```bash
$ composer require panlatent/cron-expression-descriptor
```

This will install the library and all required dependencies. The project requires **PHP 7.0** or newer.

Usage
-----

```php
echo (new Panlatent\CronExpressionDescriptor\ExpressionDescriptor('23 12 * JAN *'))->getDescription();
// OUTPUT: At 12:23 PM, only in January
```

License
-------
The Cron Expression Descriptor is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).