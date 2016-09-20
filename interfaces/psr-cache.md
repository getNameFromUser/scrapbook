---
layout: interface
title: PSR-6 cache
description: The PHP-FIG (Framework Interop Group) have defined this pool driver cache standard. Scrapbook has an implementation that builds on key-value-store, so it works with all adapters.
weight: 1
icon: fa fa-gears
namespace: MatthiasMullie\Scrapbook\Psr6
---

```php
// boilerplate code example with Memcached, but any
// MatthiasMullie\Scrapbook\KeyValueStore adapter will work
$client = new \Memcached();
$client->addServer('localhost', 11211);
$cache = new \MatthiasMullie\Scrapbook\Adapters\Memcached($client);

// create Pool object from cache engine
$pool = new \MatthiasMullie\Scrapbook\Psr6\Pool($cache);

// get item from Pool
$item = $pool->getItem('key');

// get item value
$value = $item->get();

// ... or change the value & store it to cache
$item->set('updated-value');
$pool->save($item);
```

<hr class="sep10">

[psr/cache](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-6-cache.md)
is an attempt to standardize how cache is implemented across frameworks &
libraries in the PHP landscape.

It's a drastically different cache model than KeyValueStore & psr/simplecache:
instead of directly querying values from the cache, psr/cache basically operates
on value objects to perform the changes, which then feed back to the cache.

This project bridges the gap between KeyValueStore based adapters & extras, and
PSR-6: any of the Scrapbook tools are accessible in a PSR-6 compatible manner.

<hr class="sep20">

## Methods Pool

<hr class="sep10">

{% include psr-6-pool.html %}

<hr class="sep20">

## Methods Item

<hr class="sep10">

{% include psr-6-item.html %}