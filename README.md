# Synerise SDK for PHP

The Synerise PHP SDK is designed to be simple to develop with, allowing you to easily integrate SyneriseSDK software into your server application. For more info about Synerise visit the [Synerise Website](http://synerise.com)


###Installing the library

You can get the library using Composer by including the following in your project's composer.json requirements and running composer update:


```json
{
    "require": {
        "synerise/php-sdk": "2.2.0"
    }
}
```


Now you can start tracking evetns, clients, payment and transactions:

```php
<?php
// import dependencies
require 'vendor/autoload.php';

$snr = Synerise\SyneriseTracker::getInstance([
	'apiKey' => 'b43c7c2c-ee52-4051-afa8-073e9c9c5f84',
	'apiVersion' => '2.1.0',
	'allowFork' => true,
]);

$snr->client->customIdentify('1');

// Track an custom event
$snr->event->track('Custom event', array(
	'Product Name' => 'iPhone 6',
	'Product Category' => 'Smartphones'));

//Add product to favorite
$snr->transaction->addFavoriteProduct(
	[
		'$categories' => ['1', '2', '3', 'apple'],
		'$sku' => 'XYZ-XYZ-XYZ-XYZ-XYZ',
		'$finalUnitPrice' => 24.99,
		'$name' => 'Apple MacBook Pro 13',
		'$imageUrl' => 'https://example/image.jpg',
		'$url' => 'https://example.com/apple-macbook-pro-13',
		'$quantity' => 2
	]
);


// Add product to cart from category Women/Shoes/Boots
$snr->transaction->addProduct(
	[
		'$categories' => ['1', '2', '3', 'apple'],
		'$sku' => 'XYZ-XYZ-XYZ-XYZ-XYZ',
		'$finalUnitPrice' => 24.99,
		'$name' => 'Apple MacBook Pro 13',
		'$imageUrl' => 'https://example/image.jpg',
		'$url' => 'https://example.com/apple-macbook-pro-13',
	]);


// Remmove product to cart from category Women/Shoes/Boots
$snr->transaction->removeProduct(
	[
		'$categories' => ['1', '2', '3', 'apple'],
		'$sku' => 'XYZ-XYZ-XYZ-XYZ-XYZ',
		'$finalUnitPrice' => 24.99,
		'$name' => 'Apple MacBook Pro 13',
		'$imageUrl' => 'https://example/image.jpg',
		'$url' => 'https://example.com/apple-macbook-pro-13',
	]);


// Track charge 
$snr->transaction->charge([
	'$source' => 'POS',  // Avaiable options: WEB_DESKTOP, WEB_MOBILE, MOBILE_APP, POS
	'$totalAmount' => 120.54,
	'$discountAmount' => 22.33,
	'$revenue' => 3.44,
	'$discountCode' => 'WL2016',
	'$locationId' => 'C.H. Bonarka CC',
	'$currency' => 'PLN',
	'$orderId' => '3DD33333333',
	'$products' => [
		[
			'$categories' => ['1', '2', '3', 'apple'],
			'$sku' => 'XYZ-XYZ-XYZ-XYZ-XYZ',
			'$finalUnitPrice' => 24.99,
			'$name' => 'Apple MacBook Pro 13',
			'$imageUrl' => 'https://example/image.jpg',
			'$url' => 'https://example.com/apple-macbook-pro-13',
			'$quantity' => 2
		],
		[
			'$categories' => ['1', '2', '3', 'apple'],
			'$sku' => 'XYZ-XYZ-XYZ-XYZ-XYZ',
			'$finalUnitPrice' => 24.99,
			'$name' => 'Apple MacBook Pro 19',
			'$imageUrl' => 'https://example/image.jpg',
			'$url' => 'https://example.com/apple-macbook-pro-19',
			'$quantity' => 2
		]
	]
]);


// Setup clinet with cutom itentify
$snr->client->customIdentify('9876');

// Setup clinet with cutom itentify and pass client data
$snr->client->customIdentify('9876', array(
	'$email' => 'john.smith@mail.com',
	'$firstname' => 'John',
	'$lastname' => 'Smith',
	'$age' => 33,
	'Client type' => 'Premium'
));

//add or update user
$this->snr->client->setData(
	[
		'$email' => 'john.smith@mail.com',
		'$firstname' => 'John',
		'$lastname' => 'Smith',
		'$age' => 33,
		'Client type' => 'Premium'
	]
);

// Event log In
$snr->client->logIn();

// Event log out
$snr->client->logOut();


// Optional - clear all cache on Client
$snr->client->reset();



```

###Logging requests and response pairs

For logging purposes a psr-3 compliant logger needs to be provided, eg Monolog. To start logging just instantiate and pass the logger to Synerise instance.

Monolog example:

```php
$logPath = __DIR__ . DIRECTORY_SEPARATOR . 'logs/synerise.log';

$logger = new \Monolog\Logger('api');

$fileHandler = new \Monolog\Handler\RotatingFileHandler(logPath, 12, \Monolog\Logger::INFO);
$fileHandler->setFormatter(new \Monolog\Formatter\LineFormatter("[%datetime%] %channel%.%level_name%:\n%message%\n", null, true));

$logger->pushHandler($fileHandler);

$snr = Synerise\SyneriseTracker::getInstance($params, $logger);
```
Note: Absolute log path is mandatory for SyneriseTracker instance.
