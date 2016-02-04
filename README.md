# websocket

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Code Climate][ico-cc]][link-cc]
[![Tests Coverage][ico-cc-coverage]][link-cc]

PHP websocket server and client. Supports secure sockets.

## Install

Via Composer

``` bash
$ composer require vakata/websocket
```

## Usage

``` php
$config = new \vakata\websocket\Server('ws://127.0.0.1:8000');
```

Read more in the [API docs](docs/README.md)

## Testing

``` bash
$ composer test
```


## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email github@vakata.com instead of using the issue tracker.

## Credits

- [vakata][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information. 

[ico-version]: https://img.shields.io/packagist/v/vakata/websocket.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/vakata/websocket/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/vakata/websocket.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/vakata/websocket.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/vakata/websocket.svg?style=flat-square
[ico-cc]: https://img.shields.io/codeclimate/github/vakata/websocket.svg?style=flat-square
[ico-cc-coverage]: https://img.shields.io/codeclimate/coverage/github/vakata/websocket.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/vakata/websocket
[link-travis]: https://travis-ci.org/vakata/websocket
[link-scrutinizer]: https://scrutinizer-ci.com/g/vakata/websocket/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/vakata/websocket
[link-downloads]: https://packagist.org/packages/vakata/websocket
[link-author]: https://github.com/vakata
[link-contributors]: ../../contributors
[link-cc]: https://codeclimate.com/github/vakata/websocket

