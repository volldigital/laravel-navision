# Laravel UNITOP Package

## Install

Run following commands:

```php
composer require volldigital/laravel-ntlm
```

```php
php artisan vendor:publish --provider="VOLLdigital\LaravelNtlm\LaravelNtlmServiceProvider"
```

Edit your "config/ntlm.php" file or use the ENV variables.

## Usage

After setting up your config, load the client via:

```php
$client = app(VOLLdigital\LaravelNtlm\Client::class);

```

Now you are ready to recieve data from your UNITOP System.

Examples:

```php
$client = app(VOLLdigital\LaravelNtlm\Client::class);

$data = $client->fetchCollection("Events");

$event = $client->fetchOne("Events", 'Key', 'Number');

```

You can also pull data chunkswise. The data will be written in a text file and after the request finished it will be parsed and the file deleted.

```php

$client = app(VOLLdigital\LaravelNtlm\Client::class);

// file will be stored in /storage/app/temp/curl_uniqueid.temp

$data = $client->fetchCollection("Events", true);

```

You want to check if your connection to UNITOP is established? You can use the ping function and check it :)

```php

$client = app(VOLLdigital\LaravelNtlm\Client::class);

if ($client->ping() === false) {
    throw new RunTimeException('No connection available');
}

```
