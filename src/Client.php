<?php

declare(strict_types=1);

namespace vakata\websocket;

use Closure;

class Client
{
    use Base {
        Base::send as protected _send;
    }

    /**
     * @var resource
     */
    protected mixed $socket = null;
    protected string $address = 'ws://127.0.0.1:8080';
    /**
     * @var array<string,string>
     */
    protected array $headers = [];
    protected ?Closure $message = null;
    protected ?Closure $tick = null;
    protected bool $disconnected = true;

    /**
     * Create an instance.
     * @param  string               $address address to bind to, defaults to `"ws://127.0.0.1:8080"`
     * @param  array<string,string> $headers optional array of headers to pass when connecting
     */
    public function __construct(string $address = 'ws://127.0.0.1:8080', array $headers = [])
    {
        $this->address = $address;
        $this->headers = $headers;
    }

    protected function preconnect(): void
    {
        $addr = parse_url($this->address);
        if ($addr === false || !isset($addr['host']) || !isset($addr['port'])) {
            throw new WebSocketException('Invalid address');
        }
        $headers = $this->headers;
        $this->socket = fsockopen(
            (isset($addr['scheme']) && in_array($addr['scheme'], ['ssl', 'tls', 'wss']) ? 'tls://' : '') .
                $addr['host'],
            $addr['port']
        ) ?: throw new WebSocketException('Could not connect');

        $key = $this->generateKey();
        $headers = array_merge(
            $this->normalizeHeaders([
                'Host' => $addr['host'] . ':' . $addr['port'],
                'Connection' => 'Upgrade',
                'Upgrade' => 'websocket',
                'Sec-Websocket-Key' => $key,
                'Sec-Websocket-Version' => '13',
            ]),
            $this->normalizeHeaders($headers)
        );
        $key = $headers['Sec-Websocket-Key'];
        foreach ($headers as $name => $value) {
            $headers[$name] = $name . ': ' . $value;
        }
        array_unshift(
            $headers,
            'GET ' . (isset($addr['path']) && strlen($addr['path']) ? $addr['path'] : '/') . ' HTTP/1.1'
        );
        $this->sendClear($this->socket, implode("\r\n", $headers) . "\r\n");

        $data = $this->receiveClear($this->socket);
        if (!preg_match('(Sec-Websocket-Accept:\s*(.*)$)mUi', $data, $matches)) {
            throw new WebSocketException('Bad response');
        }
        if (trim($matches[1]) !== base64_encode(pack('H*', sha1($key . self::$magic)))) {
            throw new WebSocketException('Bad key');
        }
        $this->disconnected = false;
    }

    protected function generateKey(): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!"$&/()=[]{}0123456789';
        $key = '';
        $chars_length = strlen($chars);
        for ($i = 0; $i < 16; ++$i) {
            $key .= $chars[mt_rand(0, $chars_length - 1)];
        }

        return base64_encode($key);
    }
    /**
     *
     * @param array<string,string> $headers
     * @return array<string,string>
     */
    protected function normalizeHeaders(array $headers): array
    {
        $cleaned = [];
        foreach ($headers as $name => $value) {
            if (strncmp($name, 'HTTP_', 5) === 0) {
                $name = substr($name, 5);
            }
            if ($name !== false) {
                $name = str_replace('_', ' ', strtolower($name));
                $name = str_replace('-', ' ', strtolower($name));
                $name = str_replace(' ', '-', ucwords($name));
                $cleaned[$name] = $value;
            }
        }

        return $cleaned;
    }
    /**
     * Set a callback to execute when a message arrives.
     *
     * The callable will receive the message string and the server instance.
     * @param  callable  $callback the callback
     * @return $this
     */
    public function onMessage(callable $callback): static
    {
        $this->message = Closure::fromCallable($callback);

        return $this;
    }
    /**
     * Set a callback to execute every few milliseconds.
     *
     * The callable will receive the server instance. If it returns boolean `false` the client will stop listening.
     * @param  callable  $callback the callback
     * @return $this
     */
    public function onTick(callable $callback): static
    {
        $this->tick = Closure::fromCallable($callback);

        return $this;
    }
    /**
     * Send a message to the server.
     * @param  string $data   the data to send
     * @param  string $opcode the data opcode, defaults to `"text"`
     * @return bool was the send successful
     */
    public function send(string $data, string $opcode = 'text'): bool
    {
        return $this->_send($this->socket, $data, $opcode, true);
    }
    public function disconnect(): void
    {
        $this->disconnected = true;
    }
    /**
     * Start listening.
     */
    public function connect(): void
    {
        if ($this->disconnected) {
            $this->preconnect();
        }
        while (true) {
            if (isset($this->tick)) {
                if (call_user_func($this->tick, $this) === false) {
                    break;
                }
            }
            $changed = [$this->socket];
            $write = [];
            $except = [];
            if (@stream_select($changed, $write, $except, null) > 0) {
                foreach ($changed as $socket) {
                    try {
                        $message = $this->receive($socket);
                        if (isset($this->message)) {
                            call_user_func($this->message, $message, $this);
                        }
                    } catch (WebSocketException $ignore) {
                        break 2;
                    }
                }
            }
            if ($this->disconnected) {
                break;
            }
            usleep(5000);
        }
        $this->disconnected = true;
        @fclose($this->socket);
    }
}
