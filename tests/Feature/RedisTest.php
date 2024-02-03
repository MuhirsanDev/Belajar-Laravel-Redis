<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Redis;
use Predis\Command\Argument\Geospatial\ByRadius;
use Predis\Command\Argument\Geospatial\FromLonLat;
use Tests\TestCase;


class RedisTest extends TestCase
{
    public function testPing()
    {
        $response = Redis::command("ping");
        self::assertEquals("PONG", $response);

        $response = Redis::ping();
        self::assertEquals("PONG", $response);
    }




    public function testString()
    {
        Redis::setex("name", 2, "Ican");
        $response = Redis::get("name");
        self::assertEquals("Ican", $response);

        sleep(5);

        $response = Redis::get("name");
        self::assertNull($response);
    }



    public function testList()
    {
        Redis::del("names");

        Redis::rpush("names", "Ican");
        Redis::rpush("names", "Irsan");
        Redis::rpush("names", "Muhirsan");

        $response = Redis::lrange("names", 0, -1);
        self::assertEquals(["Ican", "Irsan", "Muhirsan"], $response);

        self::assertEquals("Ican", Redis::lpop("names"));
        self::assertEquals("Irsan", Redis::lpop("names"));
        self::assertEquals("Muhirsan", Redis::lpop("names"));

    }



    public function testSet()
    {
        Redis::del("names");

        Redis::sadd("names", "Ican");
        Redis::sadd("names", "Ican");
        Redis::sadd("names", "Irsan");
        Redis::sadd("names", "Irsan");
        Redis::sadd("names", "Muhirsan");
        Redis::sadd("names", "Muhirsan");

        $response = Redis::smembers("names");
        self::assertEquals(["Ican", "Irsan", "Muhirsan"], $response);
    }



    public function testSortedSet()
    {

        Redis::del("names");

        Redis::zadd("names", 100, "Ican");
        Redis::zadd("names", 100, "Ican");
        Redis::zadd("names", 85, "Irsan");
        Redis::zadd("names", 85, "Irsan");
        Redis::zadd("names", 95, "Muhirsan");
        Redis::zadd("names", 95, "Muhirsan");

        $response = Redis::zrange("names", 0, -1);
        self::assertEquals(["Muhirsan", "Irsan", "Ican"], $response);
    }



    public function testHash()
    {
        Redis::del("user:1");

        Redis::hset("user:1", "name", "Ican");
        Redis::hset("user:1", "email", "ican@gmail.com");
        Redis::hset("user:1", "age", 20);

        $response = Redis::hgetall("user:1");
        self::assertEquals([
            "name" => "Ican",
            "email" => "ican@gmail.com",
            "age" => "20"
        ], $response);
    }



    public function testGeoPoint()
    {
        Redis::del("sellers");

        Redis::geoadd("sellers", 106.820990, -6.174704, "Toko A");
        Redis::geoadd("sellers", 106.822696, -6.176870, "Toko B");

        $result = Redis::geodist("sellers", "Toko A", "Toko B", "km");
        self::assertEquals(0.3061, $result);

        $result = Redis::geosearch("sellers", new FromLonLat(106.821666, -6.175494), new ByRadius(5, "km"));
        self::assertEquals(["Toko A", "Toko B"], $result);
    }



    public function testHyperLogLog()
    {
        Redis::pfadd("visitors", "eko", "kurniawan", "khannedy");
        Redis::pfadd("visitors", "eko", "budi", "joko");
        Redis::pfadd("visitors", "rully", "budi", "joko");

        $result = Redis::pfcount("visitors");
        self::assertEquals(6, $result);

    }



    public function testPipeline()
    {
        Redis::pipeline(function ($pipeline){
            $pipeline->setex("name", 2, "Ican");
            $pipeline->setex("address", 2, "Indonesia");
        });

        $response = Redis::get("name");
        self::assertEquals("Ican", $response);
        $response = Redis::get("address");
        self::assertEquals("Indonesia", $response);
    }



    public function testTransaction()
    {
        Redis::transaction(function ($transaction){
            $transaction->setex("name", 2, "Ican");
            $transaction->setex("address", 2, "Indonesia");
        });

        $response = Redis::get("name");
        self::assertEquals("Ican", $response);
        $response = Redis::get("address");
        self::assertEquals("Indonesia", $response);
    }



    public function testPublish()
    {
        for ($i = 0; $i < 10; $i++) {
            Redis::publish("channel-1", "Hello World $i");
            Redis::publish("channel-2", "Good Bye $i");
        }
        self::assertTrue(true);
    }



    public function testPublishStream()
    {
        for ($i = 0; $i < 10; $i++) {
            Redis::xadd("members", "*", [
                "name" => "Ican $i",
                "address" => "Indonesia"
            ]);
        }
        self::assertTrue(true);
    }



    public function testCreateConsumer()
    {
        Redis::xgroup("create", "members", "group1", "0");
        Redis::xgroup("createconsumer", "members", "group1", "consumer-1");
        Redis::xgroup("createconsumer", "members", "group1", "consumer-2");
        self::assertTrue(true);

    }



    public function testConsumerStream()
    {
        $result = Redis::xreadgroup("group1", "consumer-1", ["members" => ">"], 3, 3000);

        self::assertNotNull($result);
        echo json_encode($result, JSON_PRETTY_PRINT);
    }


}
