# vakata\websocket\Client


## Methods

| Name | Description |
|------|-------------|
|[__construct](#vakata\websocket\client__construct)|Create an instance.|
|[onMessage](#vakata\websocket\clientonmessage)|Set a callback to execute when a message arrives.|
|[onTick](#vakata\websocket\clientontick)|Set a callback to execute every few milliseconds.|
|[send](#vakata\websocket\clientsend)|Send a message to the server.|
|[run](#vakata\websocket\clientrun)|Start listening.|
|[sendClear](#vakata\websocket\clientsendclear)|Send data to a socket in clear form (basically fwrite)|
|[receiveClear](#vakata\websocket\clientreceiveclear)|Read clear data from a socket (basically a fread).|
|[receive](#vakata\websocket\clientreceive)|Read data from a socket (in websocket format)|

---



### vakata\websocket\Client::__construct
Create an instance.  


```php
public function __construct (  
    string $address,  
    array $headers  
)   
```

|  | Type | Description |
|-----|-----|-----|
| `$address` | `string` | address to bind to, defaults to `"ws://127.0.0.1:8080"` |
| `$headers` | `array` | optional array of headers to pass when connecting |

---


### vakata\websocket\Client::onMessage
Set a callback to execute when a message arrives.  
The callable will receive the message string and the server instance.

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


### vakata\websocket\Client::onTick
Set a callback to execute every few milliseconds.  
The callable will receive the server instance. If it returns boolean `false` the client will stop listening.

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


### vakata\websocket\Client::send
Send a message to the server.  


```php
public function send (  
    string $data,  
    string $opcode  
) : bool    
```

|  | Type | Description |
|-----|-----|-----|
| `$data` | `string` | the data to send |
| `$opcode` | `string` | the data opcode, defaults to `"text"` |
|  |  |  |
| `return` | `bool` | was the send successful |

---


### vakata\websocket\Client::run
Start listening.  


```php
public function run ()   
```


---


### vakata\websocket\Client::sendClear
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


### vakata\websocket\Client::receiveClear
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


### vakata\websocket\Client::receive
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

