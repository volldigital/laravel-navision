# Larave NTLM Package

## Install

Run following commands:

```php
composer require volldigital/laravel-ntlm
```

```php
php artisan vendor:publish
```

Publish the laravel-ntlm config file. Now you can setup your configuration for your NAV.

## Usage

After setting up your config, load the client via:

```php
$client = app(VOLLdigital\LaravelNtlm\Client::class);

```

Now you are ready to recieve data from your NAV System.

Examples:

```php
$client = app(VOLLdigital\LaravelNtlm\Client::class);

$data = $client->fetchCollection("Events");

$event = $client->fetchOne("Events", 'Key', 'Number');

```
