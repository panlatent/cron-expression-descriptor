Cron Expression Descriptor
===========================
Converts cron expressions into human readable descriptions in PHP.

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

This will install the library and all required dependencies. The project requires **PHP 7.1** or newer.

Usage
-----

```php
echo (new Panlatent\CronExpressionDescriptor\ExpressionDescriptor('23 12 * JAN *'))->getDescription();
// OUTPUT: At 12:23 PM, only in January
```

Ports
------

This library has been ported to several other languages.

 - C# - [https://github.com/bradymholt/cron-expression-descriptor](https://github.com/bradymholt/cron-expression-descriptor)
 - JavaScript - [https://github.com/bradymholt/cRonstrue](https://github.com/bradymholt/cRonstrue)
 - Java - [https://github.com/RedHogs/cron-parser](https://github.com/RedHogs/cron-parser)
 - Java - [https://github.com/voidburn/cron-expression-descriptor](https://github.com/voidburn/cron-expression-descriptor)
 - Ruby - [https://github.com/alpinweis/cronex](https://github.com/alpinweis/cronex)
 - Python - [https://github.com/Salamek/cron-descriptor](https://github.com/Salamek/cron-descriptor)
 - Go - [https://github.com/lnquy/cron](https://github.com/lnquy/cron)
 
License
-------
The Cron Expression Descriptor is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
