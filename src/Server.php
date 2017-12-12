<?php
declare(strict_types=1);

namespace vakata\websocket;

/**
 * Class Server
 *
 * @package vakata\websocket
 */
abstract class Server
{
    use Base;

    protected $address     = '';
    protected $server      = null;
    protected $sockets     = [];
    protected $connections = [];

    /**
     * Create an instance.
     *
     * @param  string $address where to create the server, defaults to "ws://127.0.0.1:8080"
     * @param  string $cert    optional PEM encoded public and private keys to secure the server with (if `wss` is used)
     * @param  string $pass    optional password for the PEM certificate
     *
     * @throws WebSocketException
     */
    public function __construct(string $address = 'ws://127.0.0.1:8080', string $cert = null, string $pass = null)
    {
        $addr = parse_url($address);
        if ($addr === false || !isset($addr['scheme']) || !isset($addr['host']) || !isset($addr['port'])) {
            throw new WebSocketException('Invalid address');
        }

        $context = stream_context_create();
        if ($cert !== null) {
            stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
            stream_context_set_option($context, 'ssl', 'verify_peer', false);
            stream_context_set_option($context, 'ssl', 'local_cert', $cert);
            if ($pass !== null) {
                stream_context_set_option($context, 'ssl', 'passphrase', $pass);
            }
        }

        $this->address = $address;
        $ern           = null;
        $ers           = null;
        $this->server  = stream_socket_server(
            (in_array($addr['scheme'], ['wss', 'tls']) ? 'tls' : 'tcp') . '://' . $addr['host'] . ':' . $addr['port'],
            $ern,
            $ers,
            STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
            $context
        );
        if ($this->server === false) {
            throw new WebSocketException('Could not create server');
        }
    }

    /**
     * Start processing requests. This method runs in an infinite loop.
     *
     * @throws WebSocketException
     */
    public function run()
    {
        $this->sockets[] = $this->server;
        while (true) {
            if (!$this->onTick()) {
                break;
            }
            $changed    = $this->sockets;
            $write      = [];
            $except     = [];
            $changedNum = stream_select($changed, $write, $except, 0);

            if (false === $changedNum) {
                throw new WebSocketException('Could not select streams.');
            } elseif ($changedNum > 0) {
                $messages = [];
                foreach ($changed as $id => $socket) {
                    if ($socket === $this->server) {
                        $temp = stream_socket_accept($this->server);
                        if ($temp !== false) {
                            if ($this->connect($temp)) {
                                $this->onConnect($this->connections[$id]);
                            }
                        }
                    } else {
                        try {
                            $message    = $this->receive($socket);
                            $messages[] = [
                                'connection' => $this->connections[$id],
                                'message'    => $message,
                            ];
                        } catch (WebSocketException $e) {
                            $this->onDisconnect($this->connections[$id]);
                            $this->disconnect($socket);
                        }
                    }
                }
                foreach ($messages as $message) {
                    $this->onMessage($message['connection'], $message['message']);
                }
            }
            usleep(5000);
        }
    }

    /**
     * @return bool
     */
    abstract protected function onTick(): bool;

    /**
     * Connects a socket.
     *
     * @param resource $socket The socket to connect.
     *
     * @return bool
     */
    protected function connect(&$socket): bool
    {
        $headers = $this->receiveClear($socket);
        if (!strlen($headers)) {
            return false;
        }
        $headers = str_replace(["\r\n", "\n"], ["\n", "\r\n"], $headers);
        $headers = array_filter(explode("\r\n", preg_replace("(\r\n\s+)", ' ', $headers)));
        $request = explode(' ', array_shift($headers));
        if (strtoupper($request[0]) !== 'GET') {
            $this->sendClear($socket, "HTTP/1.1 405 Method Not Allowed\r\n\r\n");

            return false;
        }
        $temp = [];
        foreach ($headers as $header) {
            $header                             = explode(':', $header, 2);
            $temp[trim(strtolower($header[0]))] = trim($header[1]);
        }
        $headers = $temp;
        if (!isset($headers['sec-websocket-key']) ||
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
                $v                    = explode('=', $v, 2);
                $cookies[trim($v[0])] = $v[1];
            }
        }
        $client = new Connection($socket, $headers, $request[1], $cookies);
        if (!$this->validateConnection($client)) {
            $this->sendClear($socket, "HTTP/1.1 400 Bad Request\r\n\r\n");

            return false;
        }

        $response   = [];
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

        $this->connections[]             = $client;
        $this->sockets[$client->getID()] = $socket;

        return $this->sendClear($socket, implode("\r\n", $response) . "\r\n\r\n");
    }

    /**
     * @param Connection $connection
     *
     * @return bool
     */
    abstract protected function validateConnection(Connection $connection): bool;

    /**
     * @param Connection $connection
     *
     * @return mixed
     */
    abstract protected function onConnect(Connection $connection);

    /**
     * @param Connection $connection
     *
     * @return mixed
     */
    abstract protected function onDisconnect(Connection $connection);

    /**
     * @param Connection $connection
     */
    public function disconnect(Connection $connection)
    {
        unset($this->connections[$connection->getID()], $this->sockets[$connection->getID()]);
    }

    /**
     * @param Connection $connection
     * @param string     $message
     *
     * @return mixed
     */
    abstract protected function onMessage(Connection $connection, string $message);

    /**
     * Get an array of all connections.
     *
     * @return array    the clients
     */
    public function getConnections(): array
    {
        return $this->connections;
    }

    /**
     * Get the server socket.
     *
     * @return resource    the socket
     */
    public function getServer()
    {
        return $this->server;
    }
}
