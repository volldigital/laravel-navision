# Laravel Navision Package

A small package to communicate with Microsoft Navision. You can fetch collections and single records.

## Install

Run following commands:

```php
composer require volldigital/laravel-navision
```

```php
php artisan vendor:publish --provider="VOLLdigital\LaravelNavision\LaravelNavisionServiceProvider"
```

Edit your "config/ntlm.php" file or use the ENV variables.

## Usage

After setting up your config, load the client via:

```php
$client = app(VOLLdigital\LaravelNavision\Client::class);

```

Now you are ready to recieve data from your Navision.

Examples:

```php
$client = app(VOLLdigital\LaravelNavision\Client::class);

$data = $client->fetchCollection("Events");

$event = $client->fetchOne("Events", 'Key', 'Number');

```

You can also pull data chunk-wise. The data will be written in a text file and after the request finished, it will be parsed and deleted.

```php

$client = app(VOLLdigital\LaravelNavision\Client::class);

// file will be stored in /storage/app/temp/curl_uniqueid.temp

$data = $client->fetchCollection("Events", true);

```

You want to check if your connection to UNITOP is established? You can use the ping function and check it :)

```php

$client = app(VOLLdigital\LaravelNavision\Client::class);

if ($client->ping() === false) {
    throw new RunTimeException('No connection available');
}

```


## Write data

Use `$client->writeData($url, $data);` to write data into unitop.

Example:

```php
$client->writeData(
    'Items',
    [
        'Item_Code' => 'VD',
        'Item_Description' => 'Test data'
    ]
);
```