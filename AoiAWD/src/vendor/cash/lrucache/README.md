LRU Cache
============

[![Build Status](https://travis-ci.org/cash/LRUCache.png)](http://travis-ci.org/cash/LRUCache)

Implements a non-persistent memory-based Least Recently Used cache.

The keys can be integers or strings. The values can be anything. Because this
library uses array(), keys that are strings that contain an integer ("7") are
cast to an integer. Therefore, there is no difference between the key "7" and the
key 7.

```php
$cache = new LRUCache(10);
$cache->put('line1', 'roses are red');
$cache->put('line2', 'violets are blue');
$line1 = $cache->get('line1');
```
