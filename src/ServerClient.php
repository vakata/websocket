<?php

declare(strict_types=1);

namespace vakata\websocket;

class ServerClient
{
    /**
     *
     * @param resource $socket
     * @param array<string,string> $headers
     * @param string $resource
     * @param array<string,string> $cookies
     * @param Server $server
     * @return void
     */
    public function __construct(
        protected mixed $socket,
        public readonly array $headers,
        public readonly string $resource,
        public readonly array $cookies,
        protected Server $server
    ) {
    }
    public function send(string $data): bool
    {
        return $this->server->send($this->socket, $data);
    }
    public function disconnect(): void
    {
        $this->server->disconnectClient($this->socket);
    }
}
