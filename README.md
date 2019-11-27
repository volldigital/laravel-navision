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

Use `$client->writeData(string $url, array $data);` to write data into navision.

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

## Count items 

Use `$client->countCollection("YourCollection")` to recieve the amount of items in this collection.

Example:

```php

dd($client->countCollection('Events')); // Outputs: 100293

```

## Examples

### Fetching data

```php
    protected function fetchData(string $uri, $key, bool $chunk = false, ?callable $filter = null)
    {
        $temp = $this->client->fetchCollection($uri, $chunk);
        $data = [];

        foreach($temp as $ts) {
            if (!is_null($filter) && $filter($ts) === false) {
                continue;
            }

            $data[$ts[$key]] = $ts;
        }

        return collect($data);
    }
```

### Pagination
```php
    protected function fetchAll()
    {
        $number = $this->client->countCollection('Events');
        $pageLimit = 10000;
        $pages = (int)ceil($number / $pageLimit);
        $events = [];

        for ($i = 0; $i < $pages; $i++) {
            $skip = $i * $pageLimit;

            $temp = $this->fetchData('Events?$skip='.$skip, 'Number');

            $events = array_merge($events, $temp->toArray());
        }

        return collect($events);
    }
```