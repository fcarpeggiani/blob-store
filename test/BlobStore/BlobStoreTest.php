<?php

namespace BlobStore;

class BlobStoreTest extends \PHPUnit_Framework_TestCase
{
    /**
     * -----test
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
        unlink(__DIR__ . DIRECTORY_SEPARATOR . $key);
    }

    /**
     * @test
     */
    public function storeMetadata()
    {
        $config = new \Doctrine\DBAL\Configuration();
        $connectionParams = array(
            'url' => 'sqlite:///:memory:',
        );
        $conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
        $conn->executeUpdate(file_get_contents(__DIR__ . '/../../metadata_db.sql'));
        $m = new Metadata\DBALMetadataRepo($conn);
        $m->saveMetadata('xxxxxx', [
            'foo' => 'bar',
            'goofy' => 'mickey',
            'donald' => 'daisy'
        ]);
        $get = $m->findBy(['uuid' => 'xxxxxx']);
        $this->assertCount(1, $get);
        $this->assertEquals($get['xxxxxx']['foo'], 'bar');
        $this->assertEquals($get['xxxxxx']['goofy'], 'mickey');
        $this->assertEquals($get['xxxxxx']['donald'], 'daisy');
        
        $get = $m->findBy(['foo' => 'bar', 'donald' => 'daisy']);
        $this->assertCount(1, $get);
        $this->assertEquals($get['xxxxxx']['foo'], 'bar');
        $this->assertEquals($get['xxxxxx']['goofy'], 'mickey');
        $this->assertEquals($get['xxxxxx']['donald'], 'daisy');
        $get = $m->findBy(['unknown' => 'daisy']);
        $this->assertCount(0, $get);
    }

    /**
     * @test
     */
    public function defaultStore()
    {
        $config = new \Doctrine\DBAL\Configuration();
        $connectionParams = array(
            'url' => 'sqlite:///:memory:',
        );
        $conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
        $conn->executeUpdate(file_get_contents(__DIR__ . '/../../metadata_db.sql'));
        $m = new Metadata\DBALMetadataRepo($conn);

        $storage = new Storage\SimpleFileStorage(__DIR__);
        $expected = file_get_contents(__FILE__);
        $f = fopen(__FILE__, 'r');

        $store = new DefaultBlobStore($storage, $m);

        $blob = $store->put($f, ['foo' => 'bar']);
        $this->assertEquals($blob->getMetadata()['foo'], 'bar');
        $this->assertNotNull($blob->getId());
        $this->assertEquals($expected, $blob->getDataAsString());

        $uuid = $blob->getId();
        $blob = $store->get($uuid);
        $this->assertEquals($blob->getMetadata()['foo'], 'bar');
        $this->assertNotNull($blob->getId());
        $this->assertEquals($expected, $blob->getDataAsString());
    }
}