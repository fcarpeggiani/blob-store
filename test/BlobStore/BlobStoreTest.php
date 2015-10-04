<?php

namespace BlobStore;

class BlobStoreTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function storeData()
    {
        $storage = new Storage\SimpleFileStorage(__DIR__, 3);
        $f = fopen(__FILE__, 'r');
        $key = $storage->saveData('FOO', $f);
        $this->assertTrue(file_exists(__DIR__ . DIRECTORY_SEPARATOR . $key));

        $data = file_get_contents(__FILE__);
        $retrievedData = stream_get_contents($storage->getData($key));
        $this->assertEquals($retrievedData, $data);
        //unlink(__DIR__ . DIRECTORY_SEPARATOR . $key);
    }
}