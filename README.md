# Network Utilities for Laravel

PHP Library for Networking Tools (IPv4 and IPv6) Use for Laravel 5

## Install

#### Via Composer

``` bash
$ composer require sentrasoft/laravel-netutils
```

#### Via edit `composer.json`

	"require": {
		"sentrasoft/laravel-netutils": "dev-master"
	}

Next, update Composer from the Terminal:

``` bash
$ composer update
```

#### Add to laravel config
Once this operation completes, the final step is to add the service provider. Open `config/app.php`, and add a new item to the providers array.

```php
'providers' => array(
    .....
    Sentrasoft\Netutils\NetutilsServiceProvider::class,
);
```

Now add the alias.

```php
'aliases' => array(
    ......
    'Netutils' => Sentrasoft\Netutils\Facades\Netutils::class,
);
```


## Usage

``` php
// Generate network object
$network = new Netutils;
// The default IP set to 127.0.0.1 and Netmask 255.255.255.0

// Set the IP and Netmask
$network::setIP('10.3.30.179');
$network::setNetmask('255.255.255.0');

// Get the IP and Netmask
$ip = $network::getIP();
$netmask = $network::getNetmask();

// Get Ping latency from current IP set
$latency = $network::ping()->ping();

// Get Ping latency from given ip
$latency = $network::ping('192.168.1.123')->ping();

// Get Network info from current IP set
$network = $network::network()->info;

// Get Network info from given IP and Netmask
$network = $network::network('192.168.1.123','255.255.255.0')->info;

// Get individual Network info
$CIDR = Network::network()->CIDR;
$broadcast = (string)Network::network()->broadcast;

// Get MAC address from target's IP
$mac = $network::network()->mac;

// Bonus Wake On Lan
// Netutils::WakeOnLan('Mac Address','Broadcast Address')->WakeUp();
$wakeOnLan = $network::WakeOnLan('74-27-ea-5e-74-59','10.3.30.255')->WakeUp();

```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
