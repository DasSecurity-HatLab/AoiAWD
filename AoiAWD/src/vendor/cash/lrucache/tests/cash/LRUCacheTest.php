<?php

use cash\LRUCache;

class LRUCacheTest extends PHPUnit_Framework_TestCase {

    function setUp() {
        $this->cache = new LRUCache(4);
        $elements = array();
        for ($i = 1; $i < 4; $i++) {
            $elements[$i] = "test$i";
            $this->cache->put($i, $elements[$i]);
        }
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    function testConstructorZero() {
        $cache = new LRUCache(0);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    function testConstructorNegative() {
        $cache = new LRUCache(-1);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    function testConstructorString() {
        $cache = new LRUCache("1");
    }

    function testConstainsKey() {
        $this->assertTrue($this->cache->containsKey(2));
        $this->assertTrue($this->cache->containsKey("2"));
        $this->assertFalse($this->cache->containsKey(1000));
    }

    function testGet() {
        $this->assertSame("test1", $this->cache->get(1));
        $this->assertSame(null, $this->cache->get(0));
        $this->assertFalse($this->cache->get(0, false));
    }

    function testRemove() {
        $this->cache->remove(1);
        $this->assertEquals(2, $this->cache->size());
        $this->assertFalse($this->cache->containsKey(1));
    }

    function testPut() {
        $this->cache->put(100, 'new');
        $this->assertSame('new', $this->cache->get(100));

        // now overwrite
        $this->cache->put(100, 'new2');
        $this->assertSame('new2', $this->cache->get(100));

        // now exceed the size limit
        $this->cache->put(101, 'really new');
        $this->assertSame('really new', $this->cache->get(101));
        $this->assertFalse($this->cache->containsKey(1));
        $this->assertEquals(4, $this->cache->size());
    }

    function testAccessUpdate() {
        // fill cache, access 1st element, and exceed limit
        $this->cache->put(100, 'new');
        $this->cache->get(1);
        $this->cache->put(101, 'really new');
        $this->assertTrue($this->cache->containsKey(1));
        $this->assertFalse($this->cache->containsKey(2));
    }

    function testClear() {
        $this->cache->clear();
        $this->assertEquals(0, $this->cache->size());
        $this->assertFalse($this->cache->containsKey(1));
    }

}
