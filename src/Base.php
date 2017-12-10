<?php

declare(strict_types=1);

namespace vakata\WebSocket;

/**
 * An trait used in both the server and client classes.
 *
 * It handles all encoding / decoding / masking / socket operations.
 */
trait Base {
	/**
	 * all available opcodes
	 * @var array
	 */
	protected static $opcodes = [
		'continuation' => 0,
		'text'         => 1,
		'binary'       => 2,
		'close'        => 8,
		'ping'         => 9,
		'pong'         => 10,
	];
	/**
	 * buffer size for all operations in bytes (defaults to 4096)
	 * @var integer
	 */
	protected static $fragmentSize = 4096;
	/**
	 * the magic key used to generate websocket keys (per specs)
	 * @var string
	 */
	protected static $magic = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

	/**
	 * Send data to a socket in clear form (basically fwrite)
	 *
	 * @param  resource &$socket the socket to write to
	 * @param  string   $data    the data to send
	 *
	 * @return bool             was the send successful
	 */
	public
	function sendClear(&$socket, string $data) {
		return fwrite($socket, $data) > 0;
	}

	/**
	 * Send data to a socket.
	 *
	 * @param  resource &$socket the socket to send to
	 * @param  string   $data    the data to send
	 * @param  string   $opcode  one of the opcodes (defaults to "text")
	 * @param  boolean  $masked  should the data be masked (per specs the server should not mask, defaults to false)
	 *
	 * @return bool           was the send successful
	 */
	public
	function send(&$socket, string $data, string $opcode = 'text', bool $masked = FALSE) {
		while (strlen($data)) {
			$temp = substr($data, 0, static::$fragmentSize);
			$data = substr($data, static::$fragmentSize);
			$temp = $this->encode($temp, $opcode, $masked, strlen($data) === 0);

			if (!is_resource($socket) || get_resource_type($socket) !== "stream") {
				return FALSE;
			}
			$meta = stream_get_meta_data($socket);
			if ($meta['timed_out']) {
				return FALSE;
			}
			if (fwrite($socket, $temp) === FALSE) {
				return FALSE;
			}
			$opcode = 'continuation';
		}

		return TRUE;
	}

	/**
	 * @param        $data
	 * @param string $opcode
	 * @param bool   $masked
	 * @param bool   $final
	 *
	 * @return string
	 */
	protected
	function encode($data, string $opcode = 'text', bool $masked = TRUE, bool $final = TRUE) {
		$length = strlen($data);

		$head = '';
		$head .= $final ? '1' : '0';
		$head .= '000';
		$head .= sprintf('%04b', static::$opcodes[ $opcode ]);
		$head .= $masked ? '1' : '0';
		if ($length > 65535) {
			$head .= decbin(127);
			$head .= sprintf('%064b', $length);
		} elseif ($length > 125) {
			$head .= decbin(126);
			$head .= sprintf('%016b', $length);
		} else {
			$head .= sprintf('%07b', $length);
		}

		$frame = '';
		foreach (str_split($head, 8) as $binstr) {
			$frame .= chr(bindec($binstr));
		}
		if ($masked) {
			$mask = '';
			for ($i = 0; $i < 4; ++$i) {
				$mask .= chr(rand(0, 255));
			}
			$frame .= $mask;
		}
		for ($i = 0; $i < $length; ++$i) {
			$frame .= ($masked) ? $data[ $i ] ^ $mask[ $i % 4 ] : $data[ $i ];
		}

		return $frame;
	}

	/**
	 * Read clear data from a socket (basically a fread).
	 *
	 * @param  resource &$socket the socket to read from
	 *
	 * @return string                the data that was read
	 */
	public
	function receiveClear(&$socket) {
		$data = '';
		$read = static::$fragmentSize;
		do {
			$buff = fread($socket, $read);
			if ($buff === FALSE) {
				return FALSE;
			}
			$data .= $buff;
			$meta = stream_get_meta_data($socket);
			$read = min((int)$meta['unread_bytes'], static::$fragmentSize);
			usleep(1000);
		} while (!feof($socket) && (int)$meta['unread_bytes'] > 0);
		if (strlen($data) === 1) {
			$data .= $this->receiveClear($socket);
		}

		return $data;
	}

	/**
	 * Read data from a socket (in websocket format)
	 *
	 * @param  resource &$socket the socket to read from
	 *
	 * @return string           the read data (decoded)
	 */
	public
	function receive(&$socket) {
		$data = fread($socket, 2);
		if (strlen($data) === 1) {
			$data .= fread($socket, 1);
		}
		if ($data === FALSE || strlen($data) < 2) {
			return FALSE;
		}
		$final  = (bool)(ord($data[0]) & 1 << 7);
		$rsv1   = (bool)(ord($data[0]) & 1 << 6); //TODO: implement RSV check!
		$rsv2   = (bool)(ord($data[0]) & 1 << 5); //TODO: these three are unused!
		$rsv3   = (bool)(ord($data[0]) & 1 << 4);
		$opcode = ord($data[0]) & 31;
		$masked = (bool)(ord($data[1]) >> 7);

		$payload = '';
		$length  = (int)(ord($data[1]) & 127); // Bits 1-7 in byte 1
		if ($length > 125) {
			$temp = $length === 126 ? fread($socket, 2) : fread($socket, 8);
			if ($temp === FALSE) {
				return FALSE;
			}
			$length = '';
			for ($i = 0; $i < strlen($temp); ++$i) {
				$length .= sprintf('%08b', ord($temp[ $i ]));
			}
			$length = bindec($length);
		}

		if ($masked) {
			$mask = fread($socket, 4);
			if ($mask === FALSE) {
				return FALSE;
			}
		}
		if ($length > 0) {
			$temp = '';
			do {
				$buff = fread($socket, min($length, static::$fragmentSize));
				if ($buff === FALSE) {
					return FALSE;
				}
				$temp .= $buff;
			} while (strlen($temp) < $length);
			if ($masked) {
				for ($i = 0; $i < $length; ++$i) {
					$payload .= ($temp[ $i ] ^ $mask[ $i % 4 ]);
				}
			} else {
				$payload = $temp;
			}
		}

		if ($opcode === static::$opcodes['close']) {
			return FALSE;
		}

		return $final ? $payload : $payload . $this->receive($socket);
	}
}
