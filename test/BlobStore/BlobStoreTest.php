<?php

namespace BlobStore;

use BlobStore\Metadata\DBALMetadataRepo;
use BlobStore\Storage\SimpleFileStorage;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use PHPUnit_Framework_TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class BlobStoreTest extends PHPUnit_Framework_TestCase
{
    private $dataDir;
    private $store;

    protected function setUp()
    {
        $tmp = tempnam(sys_get_temp_dir(), 'BLOBTEST');
        unlink($tmp);
        mkdir($tmp);
        $this->dataDir = $tmp;

        $config = new Configuration();
        $connectionParams = array(
            'url' => 'sqlite:///:memory:',
        );
        $conn = DriverManager::getConnection($connectionParams, $config);
        $conn->executeUpdate(file_get_contents(__DIR__ . '/../../metadata_db.sql'));
        $m = new DBALMetadataRepo($conn);

        $storage = new SimpleFileStorage($this->dataDir);

        $store = new DefaultBlobStore($storage, $m);

        $this->store = $store;
    }

    protected function tearDown()
    {
        system("rm -rf ".escapeshellarg($this->dataDir));
    }

    /**
     * @test
     */
    public function storeData()
    {
        $storage = new SimpleFileStorage($this->dataDir, 3);
        $f = \GuzzleHttp\Psr7\stream_for(fopen(__FILE__, 'r'));
        $key = $storage->saveData('FOO', $f);
        $this->assertTrue(file_exists($this->dataDir . DIRECTORY_SEPARATOR . $key));

        $data = file_get_contents(__FILE__);
        $retrievedData = (string)$storage->getData($key);
        $this->assertEquals($retrievedData, $data);
        unlink($this->dataDir . DIRECTORY_SEPARATOR . $key);
    }

    /**
     * @test
     */
    public function storeMetadata()
    {
        $config = new Configuration();
        $connectionParams = array(
            'url' => 'sqlite::memory:',
        );
        $conn = DriverManager::getConnection($connectionParams, $config);
        $conn->executeUpdate(file_get_contents(__DIR__ . '/../../metadata_db.sql'));
        $m = new DBALMetadataRepo($conn);
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
        $store = $this->store;
        $expected = file_get_contents(__FILE__);
        $f = fopen(__FILE__, 'r');
        $blob = $store->put($f, ['foo' => 'bar']);
        $this->assertEquals($blob->getMetadata()['foo'], 'bar');
        $this->assertNotNull($blob->getId());
        $this->assertEquals($expected, $blob->getDataAsString());

        $uuid = $blob->getId();
        $blob = $store->get($uuid);
        $this->assertEquals($blob->getMetadata()['foo'], 'bar');
        $this->assertNotNull($blob->getId());
        $this->assertEquals($expected, $blob->getDataAsString());

        unlink($this->dataDir . DIRECTORY_SEPARATOR . $blob->getMetadata()[DefaultBlobStore::STORAGE_KEY_ATTR]);
    }

    /**
     * @test
     */
    public function defaultStoreFind()
    {
        $store = $this->store;
        // put some data
        foreach (new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(__DIR__ . "/../../src", \FilesystemIterator::CURRENT_AS_SELF)
            ) as $file) {
            if ($file->isFile() && !$file->isDot()) {
                $data = fopen($file->getPathname(), 'r');
                $metadata = [
                    'pathname' => $file->getPathname(),
                    'filename' => $file->getFilename(),
                    'extension' => $file->getExtension(),
                ];
                $store->put($data, $metadata);
            }
        }
        $blobs = $store->findBy(['filename' => 'Blob.php']);
        $this->assertCount(1, $blobs);
        $this->assertRegExp('/.*class Blob.*/', $blobs[0]->getDataAsString());
        $blobs = $store->findBy(['extension' => 'php']);        
        $this->assertTrue(count($blobs) >= 6);
    }
}
