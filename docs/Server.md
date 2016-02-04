# vakata\websocket\Server
A websocket server class.

## Methods

| Name | Description |
|------|-------------|
|[__construct](#vakata\websocket\server__construct)|Create an instance.|
|[run](#vakata\websocket\serverrun)|Start processing requests. This method runs in an infinite loop.|
|[getClients](#vakata\websocket\servergetclients)|Get an array of all connected clients.|
|[getServer](#vakata\websocket\servergetserver)|Get the server socket.|
|[validateClient](#vakata\websocket\servervalidateclient)|Set a callback to be executed when a client connects, returning `false` will prevent the client from connecting.|
|[onConnect](#vakata\websocket\serveronconnect)|Set a callback to be executed when a client is connected.|
|[onDisconnect](#vakata\websocket\serverondisconnect)|Set a callback to execute when a client disconnects.|
|[onMessage](#vakata\websocket\serveronmessage)|Set a callback to execute when a client sends a message.|
|[onTick](#vakata\websocket\serverontick)|Set a callback to execute every few milliseconds.|
|[sendClear](#vakata\websocket\serversendclear)|Send data to a socket in clear form (basically fwrite)|
|[send](#vakata\websocket\serversend)|Send data to a socket.|
|[receiveClear](#vakata\websocket\serverreceiveclear)|Read clear data from a socket (basically a fread).|
|[receive](#vakata\websocket\serverreceive)|Read data from a socket (in websocket format)|

---



### vakata\websocket\Server::__construct
Create an instance.  


```php
public function __construct (  
    string $address,  
    string $cert,  
    string $pass  
)   
```

|  | Type | Description |
|-----|-----|-----|
| `$address` | `string` | where to create the server, defaults to "ws://127.0.0.1:8080" |
| `$cert` | `string` | optional PEM encoded public and private keys to secure the server with (if `wss` is used) |
| `$pass` | `string` | optional password for the PEM certificate |

---


### vakata\websocket\Server::run
Start processing requests. This method runs in an infinite loop.  


```php
public function run ()   
```


---


### vakata\websocket\Server::getClients
Get an array of all connected clients.  


```php
public function getClients () : array    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `array` | the clients |

---


### vakata\websocket\Server::getServer
Get the server socket.  


```php
public function getServer () : resource    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `resource` | the socket |

---


### vakata\websocket\Server::validateClient
Set a callback to be executed when a client connects, returning `false` will prevent the client from connecting.  
The callable will receive:  
 - an associative array with client data  
 - the current server instance  
The callable should return `true` if the client should be allowed to connect or `false` otherwise.

```php
public function validateClient (  
    callable $callback  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$callback` | `callable` | the callback to execute when a client connects |
|  |  |  |
| `return` | `self` |  |

---


### vakata\websocket\Server::onConnect
Set a callback to be executed when a client is connected.  
The callable will receive:  
 - an associative array with client data  
 - the current server instance

```php
public function onConnect (  
    callable $callback  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$callback` | `callable` | the callback to execute |
|  |  |  |
| `return` | `self` |  |

---


### vakata\websocket\Server::onDisconnect
Set a callback to execute when a client disconnects.  
The callable will receive:  
 - an associative array with client data  
 - the current server instance

```php
public function onDisconnect (  
    callable $callback  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$callback` | `callable` | the callback |
|  |  |  |
| `return` | `self` |  |

---


### vakata\websocket\Server::onMessage
Set a callback to execute when a client sends a message.  


```php
public function onMessage (  
    callable $callback  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$callback` | `callable` | the callback |
|  |  |  |
| `return` | `self` |  |

---


### vakata\websocket\Server::onTick
Set a callback to execute every few milliseconds.  
The callable will receive the server instance. If it returns boolean `false` the server will stop listening.

```php
public function onTick (  
    callable $callback  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$callback` | `callable` | the callback |
|  |  |  |
| `return` | `self` |  |

---


### vakata\websocket\Server::sendClear
Send data to a socket in clear form (basically fwrite)  


```php
public function sendClear (  
    resource ,  
    string $data  
) : bool    
```

|  | Type | Description |
|-----|-----|-----|
| `` | `resource` | &$socket the socket to write to |
| `$data` | `string` | the data to send |
|  |  |  |
| `return` | `bool` | was the send successful |

---


### vakata\websocket\Server::send
Send data to a socket.  


```php
public function send (  
    resource ,  
    string $data,  
    string $opcode,  
    boolean $masked  
) : bool    
```

|  | Type | Description |
|-----|-----|-----|
| `` | `resource` | &$socket the socket to send to |
| `$data` | `string` | the data to send |
| `$opcode` | `string` | one of the opcodes (defaults to "text") |
| `$masked` | `boolean` | should the data be masked (per specs the server should not mask, defaults to false) |
|  |  |  |
| `return` | `bool` | was the send successful |

---


### vakata\websocket\Server::receiveClear
Read clear data from a socket (basically a fread).  


```php
public function receiveClear (  
    resource   
) : string    
```

|  | Type | Description |
|-----|-----|-----|
| `` | `resource` | &$socket the socket to read from |
|  |  |  |
| `return` | `string` | the data that was read |

---


### vakata\websocket\Server::receive
Read data from a socket (in websocket format)  


```php
public function receive (  
    resource   
) : string    
```

|  | Type | Description |
|-----|-----|-----|
| `` | `resource` | &$socket the socket to read from |
|  |  |  |
| `return` | `string` | the read data (decoded) |

---

