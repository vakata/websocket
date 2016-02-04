<?php
namespace vakata\websocket;

class Client extends Base
{
	private $socket = null;
	private $message = null;

	public function __construct($address = 'ws://127.0.0.1:8080', array $headers = [])
	{
		$addr = parse_url($address);
		if ($addr === false || !isset($addr['host']) || !isset($addr['port'])) {
			throw new WebSocketException('Invalid address');
		}

		$this->socket = fsockopen(
			(isset($addr['scheme']) && in_array($addr['scheme'], ['ssl','tls','wss']) ? 'tls://' : '') . $addr['host'],
			$addr['port']
		);
		if ($this->socket === false) {
			throw new WebSocketException('Could not connect');
		}

		$key = $this->generateKey();
		$headers = array_merge(
			$this->normalizeHeaders([
				'Host' => $addr['host'] . ':' . $addr['port'],
				'Connection' => 'Upgrade',
				'Upgrade' => 'websocket',
				'Sec-WebSocket-Key' => $key,
				'Sec-WebSocket-Version' => '13'
			]),
			$this->normalizeHeaders($headers)
		);
		$key = $headers['Sec-WebSocket-Key'];
		array_unshift(
			$headers,
			'GET ' . (isset($addr['path']) && strlen($addr['path']) ? $addr['path'] : '/' ).' HTTP/1.1'
		);
		$this->sendClear($this->socket, implode("\r\n", $headers) . "\r\n");

		$data = $this->receiveClear($this->socket);
		if (!preg_match('(Sec-WebSocket-Accept:\s*(.*)$)mUi', $data, $matches)) {
			throw new WebSocketException('Bad response');
		}
		if (trim($matches[1]) !== base64_encode(pack('H*', sha1($key . self::$magic)))) {
			throw new WebSocketException('Bad key');
		}
	}
	protected function generateKey()
	{
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!"$&/()=[]{}0123456789';
		$key = '';
		$chars_length = strlen($chars);
		for ($i = 0; $i < 16; $i++) {
			$key .= $chars[mt_rand(0, $chars_length-1)];
		}
		return base64_encode($key);
	}
	protected function normalizeHeaders($headers)
	{
		$cleaned = [];
		foreach ($headers as $name => $value) {
			if (strncmp($name, 'HTTP_', 5) === 0) {
				$name = substr($name, 5);
			}
			$name = str_replace('_', ' ', strtolower($name));
			$name = str_replace('-', ' ', strtolower($name));
			$name = str_replace(' ', '-', ucwords($name));
			$cleaned[$name] = $value;
		}
		return $cleaned;
	}

	public function onMessage(callable $callback)
	{
		$this->message = $callback;
		return $this;
	}
	public function send($data, $opcode = 'text')
	{
		return parent::send($this->socket, $data, $opcode, true);
	}
	public function listen()
	{
		while (true) {
			$changed = [ $this->socket ];
			if (@stream_select($changed, $write = null, $except = null, null) > 0) {
				foreach ($changed as $socket) {
					$message = $this->receive($socket);
					if ($message !== false && isset($this->message)) {
						call_user_func($this->message, $message, $this);
					}
				}
			}
			usleep(5000);
		}
	}
}
