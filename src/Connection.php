<?php
declare(strict_types=1);

namespace vakata\websocket;

/**
 * This class represents each connection we establish.
 *
 * @package vakata\websocket
 */
final class Connection
{
    use Base {
        Base::send as private _send;
        Base::sendClear as private;
        Base::encode as private;
        Base::receive as private;
        Base::receiveClear as private;
    }

    protected $socket;
    protected $header;
    protected $request;
    protected $cookies;

    private $id;

    /**
     * Creates a new connection object with an ID.
     *
     * @param resource $socket  The socket that this connection represents.
     * @param array    $header  Header data used on the connection handshake.
     * @param string   $request Request given via GET.
     * @param array    $cookies Given cookies from the user browser.
     */
    public function __construct($socket, array $header, string $request, array $cookies)
    {
        $this->id      = uniqid('', true);
        $this->socket  = $socket;
        $this->header  = $header;
        $this->request = $request;
        $this->cookies = $cookies;
    }

    /**
     * Returns the socket this connection object represents.
     *
     * @return resource
     */
    public function getSocket()
    {
        return $this->socket;
    }

    /**
     * Returns the connection ID.
     *
     * @return string
     */
    public function getID()
    {
        return $this->id;
    }

    /**
     * Returns the host given by the handshake.
     * Useful for validation.
     *
     * @return mixed|null
     */
    public function getHost()
    {
        return $this->header['host'] ?? null;
    }

    /**
     * Returns the user agent given by the handshake.
     * Useful for validation.
     *
     * @return mixed|null
     */
    public function getUserAgent()
    {
        return $this->header['user-agent'] ?? null;
    }

    /**
     * Returns the origin given by the handshake.
     * Useful for validation. (used to prevent Cross-Site WebSocket Hijacking (CSWSH)
     *
     * @return mixed|null
     */
    public function getOrigin()
    {
        return $this->header['origin'] ?? null;
    }

    /**
     * Returns the extensions given by the handshake.
     * Useful for validation.
     *
     * @return mixed|null
     */
    public function getExtensions()
    {
        return $this->header['sec-websocket-extensions'] ?? null;
    }

    /**
     * Returns the request given by the handshake.
     * Useful for serving the client different data based on the requested "channel".
     *
     * @return mixed|null
     */
    public function getRequest()
    {
        return $this->request;
    }

    // TODO: add more getters


    /**
     * @param string $data   The data to send to the client.
     * @param string $opcode The optional opcode to send in different formats.
     * @param bool   $masked Whether the packet should be XOR-encoded to prevent attacks.
     *
     * @return bool Returns true if sending the message was successful.
     */
    public function send(string $data, string $opcode = 'text', bool $masked = false)
    {
        return $this->_send($this->socket, $data, $opcode, $masked);
    }
}
