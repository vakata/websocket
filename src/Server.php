<?php

declare(strict_types=1);

namespace vakata\websocket;

use Closure;

/**
 * A websocket server class.
 */
class Server
{
    use Base;

    protected string $address = '';
    /**
     * @var resource
     */
    protected mixed $server = null;
    /**
     * @var array<resource>
     */
    protected array $sockets = [];
    /**
     * @var array<int,ServerClient>
     */
    protected array $clients = [];
    /**
     * @var array<string,Closure>
     */
    protected array $callbacks = [];
    protected ?Closure $tick = null;

    /**
     * Create an instance.
     * @param  string $address where to create the server, defaults to "ws://127.0.0.1:8080"
     * @param  string $cert    optional PEM encoded public and private keys to secure the server with (if `wss` is used)
     * @param  string $pass    optional password for the PEM certificate
     */
    public function __construct(
        string $address = 'ws://127.0.0.1:8080',
        string $cert = null,
        string $pass = null,
        string $private_key = null
    ) {
        $addr = $this->parseAddress($address);

        $context = stream_context_create();
        if ($cert !== null) {
            stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
            stream_context_set_option($context, 'ssl', 'verify_peer', false);
            stream_context_set_option($context, 'ssl', 'local_cert', $cert);
            if ($private_key) {
                stream_context_set_option($context, 'ssl', 'local_pk', $private_key);
            }
            if ($pass !== null) {
                stream_context_set_option($context, 'ssl', 'passphrase', $pass);
            }
        }

        $this->address = $address;
        $ern = null;
        $ers = null;
        $this->server = @stream_socket_server(
            (in_array($addr['scheme'], ['wss', 'tls']) ? 'tls' : 'tcp') . '://' . $addr['host'] . ':' . $addr['port'],
            $ern,
            $ers,
            STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
            $context
        ) ?: throw new WebSocketException('Could not create server');
    }
    /**
     * @param string $address
     * @return array<string,string|int>
     * @throws WebSocketException
     */
    protected function parseAddress(string $address): array
    {
        $addr = parse_url($address);
        if ($addr === false || !isset($addr['scheme']) || !isset($addr['host']) || !isset($addr['port'])) {
            throw new WebSocketException('Invalid address');
        }
        return $addr;
    }

    /**
     * Start processing requests. This method runs in an infinite loop.
     */
    public function run(): void
    {
        $this->sockets[] = $this->server;
        while (true) {
            if (isset($this->tick)) {
                if (call_user_func($this->tick, $this) === false) {
                    break;
                }
            }
            $changed = $this->sockets;
            $write = [];
            $except = [];
            if (@stream_select($changed, $write, $except, (isset($this->tick) ? 0 : null)) > 0) {
                $messages = [];
                foreach ($changed as $socket) {
                    if ($socket === $this->server) {
                        $temp = stream_socket_accept($this->server);
                        if ($temp !== false) {
                            if ($this->connected($temp)) {
                                if (isset($this->callbacks['connect'])) {
                                    call_user_func($this->callbacks['connect'], $this->clients[(int) $temp], $this);
                                }
                            }
                        }
                    } else {
                        try {
                            $message = $this->receive($socket);
                            if ($message !== '>PING'.chr(0)) {
                                $messages[] = [
                                    'client' => $this->clients[(int) $socket],
                                    'message' => $message,
                                ];
                            }
                        } catch (WebSocketException $e) {
                            if (isset($this->callbacks['disconnect'])) {
                                call_user_func($this->callbacks['disconnect'], $this->clients[(int) $socket], $this);
                            }
                            $this->disconnectClient($socket);
                        }
                    }
                }
                foreach ($messages as $message) {
                    if (isset($this->callbacks['message'])) {
                        call_user_func($this->callbacks['message'], $message['client'], $message['message'], $this);
                    }
                }
            }
            usleep(5000);
        }
    }
    /**
     * Get an array of all connected clients.
     * @return array<int,ServerClient>     the clients
     */
    public function getClients(): array
    {
        return $this->clients;
    }
    /**
     * Get the server socket.
     * @return resource    the socket
     */
    public function getServer(): mixed
    {
        return $this->server;
    }
    /**
     * Set a callback to be executed when a client connects, returning `false` will prevent the client from connecting.
     *
     * The callable will receive:
     *  - a ServerClient instance
     *  - the current server instance
     * The callable should return `true` if the client should be allowed to connect or `false` otherwise.
     * @param  callable       $callback the callback to execute when a client connects
     * @return $this
     */
    public function validateClient(callable $callback): static
    {
        $this->callbacks['validate'] = Closure::fromCallable($callback);

        return $this;
    }
    /**
     * Set a callback to be executed when a client is connected.
     *
     * The callable will receive:
     *  - a ServerClient instance
     *  - the current server instance
     * @param  callable  $callback the callback to execute
     * @return $this
     */
    public function onConnect(callable $callback): static
    {
        $this->callbacks['connect'] = Closure::fromCallable($callback);

        return $this;
    }
    /**
     * Set a callback to execute when a client disconnects.
     *
     * The callable will receive:
     *  - a ServerClient instance
     *  - the current server instance
     * @param  callable     $callback the callback
     * @return $this
     */
    public function onDisconnect(callable $callback): static
    {
        $this->callbacks['disconnect'] = Closure::fromCallable($callback);

        return $this;
    }
    /**
     * Set a callback to execute when a client sends a message.
     *
     * The callable will receive:
     *  - a ServerClient instance
     *  - the message string
     *  - the current server instance
     * @param  callable  $callback the callback
     * @return $this
     */
    public function onMessage(callable $callback): static
    {
        $this->callbacks['message'] = Closure::fromCallable($callback);

        return $this;
    }
    /**
     * Set a callback to execute every few milliseconds.
     *
     * The callable will receive the server instance. If it returns boolean `false` the server will stop listening.
     * @param  callable  $callback the callback
     * @return $this
     */
    public function onTick(callable $callback): static
    {
        $this->tick = Closure::fromCallable($callback);

        return $this;
    }
    /**
     * connected
     * @param resource $socket
     * @return bool
     */
    protected function connected(mixed &$socket): bool
    {
        try {
            $headers = $this->receiveClear($socket);
            if (!strlen($headers)) {
                return false;
            }
            $headers = str_replace(["\r\n", "\n"], ["\n", "\r\n"], $headers);
            $headers = array_filter(explode("\r\n", preg_replace("(\r\n\s+)", ' ', $headers) ?? ''));
            $request = explode(' ', array_shift($headers) ?? '');
            if (strtoupper($request[0]) !== 'GET') {
                $this->sendClear($socket, "HTTP/1.1 405 Method Not Allowed\r\n\r\n");
                return false;
            }
            $temp = [];
            foreach ($headers as $header) {
                $header = explode(':', $header, 2);
                $temp[trim(strtolower($header[0]))] = trim($header[1]);
            }
            $headers = $temp;
            if (
                !isset($headers['sec-websocket-key']) ||
                !isset($headers['upgrade']) ||
                !isset($headers['connection']) ||
                strtolower($headers['upgrade']) != 'websocket' ||
                strpos(strtolower($headers['connection']), 'upgrade') === false
            ) {
                $this->sendClear($socket, "HTTP/1.1 400 Bad Request\r\n\r\n");
                return false;
            }
            $cookies = [];
            if (isset($headers['cookie'])) {
                $temp = explode(';', $headers['cookie']);
                foreach ($temp as $v) {
                    if (trim($v) !== '' && strpos($v, '=') !== false) {
                        $v = explode('=', $v, 2);
                        $cookies[trim($v[0])] = $v[1];
                    }
                }
            }
            $client = new ServerClient($socket, $headers, $request[1], $cookies, $this);
            if (isset($this->callbacks['validate']) && !call_user_func($this->callbacks['validate'], $client, $this)) {
                $this->sendClear($socket, "HTTP/1.1 400 Bad Request\r\n\r\n");
                @stream_socket_shutdown($socket, STREAM_SHUT_RDWR);
                return false;
            }

            $response = [];
            $response[] = 'HTTP/1.1 101 WebSocket Protocol Handshake';
            $response[] = 'Upgrade: WebSocket';
            $response[] = 'Connection: Upgrade';
            $response[] = 'Sec-WebSocket-Version: 13';
            $response[] = 'Sec-WebSocket-Location: ' . $this->address;
            $response[] = 'Sec-WebSocket-Accept: ' .
                base64_encode(sha1($headers['sec-websocket-key'] . self::$magic, true));
            if (isset($headers['origin'])) {
                $response[] = 'Sec-WebSocket-Origin: ' . $headers['origin'];
            }

            $this->sockets[(int) $socket] = $socket;
            $this->clients[(int) $socket] = $client;

            return $this->sendClear($socket, implode("\r\n", $response) . "\r\n\r\n");
        } catch (WebSocketException $e) {
            return false;
        }
    }
    /**
     * disconnectClient
     * @param resource $socket
     * @return void
     */
    public function disconnectClient(mixed &$socket): void
    {
        @stream_socket_shutdown($socket, STREAM_SHUT_RDWR);
        unset($this->clients[(int) $socket], $this->sockets[(int) $socket], $socket);
    }
}
