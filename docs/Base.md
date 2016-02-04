# vakata\websocket\Base
An trait used in both the server and client classes.

It handles all encoding / decoding / masking / socket operations.
## Methods

| Name | Description |
|------|-------------|
|[sendClear](#vakata\websocket\basesendclear)|Send data to a socket in clear form (basically fwrite)|
|[send](#vakata\websocket\basesend)|Send data to a socket.|
|[receiveClear](#vakata\websocket\basereceiveclear)|Read clear data from a socket (basically a fread).|
|[receive](#vakata\websocket\basereceive)|Read data from a socket (in websocket format)|

---



### vakata\websocket\Base::sendClear
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


### vakata\websocket\Base::send
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


### vakata\websocket\Base::receiveClear
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


### vakata\websocket\Base::receive
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

