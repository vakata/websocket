# websocket

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)

PHP websocket server and client. Supports secure sockets.

## Install

Via Composer

``` bash
$ composer require vakata/websocket
```

## Server usage

``` php
// this handler will forward each message to all clients (except the sender)
$server = new \vakata\websocket\Server('ws://127.0.0.1:8080');
$server->onMessage(function ($sender, $message, $server) {
    foreach ($server->getClients() as $client) {
        if ($client !== $sender) {
            $client->send($message);
        }
    }
});
$server->run();
```

## Client usage

``` php
// this handler will echo each message to standard output
$client = new \vakata\websocket\Client('ws://127.0.0.1:8080');
$client->onMessage(function ($message, $client) {
    echo $message . "\r\n";
});
$client->connect();
```

## Usage in HTML

``` js
var sock = new WebSocket('ws://127.0.0.1:8080/');
sock.send("TEST");
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email github@vakata.com instead of using the issue tracker.

## Credits

- [vakata][link-author]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information. 

[ico-version]: https://img.shields.io/packagist/v/vakata/websocket.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/vakata/websocket
[link-downloads]: https://packagist.org/packages/vakata/websocket
[link-author]: https://github.com/vakata

