<?php

namespace BlobStore;

use Ramsey\Uuid\Uuid;

/**
 * Implementation based on a Storage and MetadataRepo
 *
 * @author davide
 */
class DefaultBlobStore implements BlobStoreInterface
{
    const STORAGE_KEY_ATTR = 'blob-store.storage-key';

    /**
     *
     * @var Storage\StorageInterface
     */
    private $storage;

    /**
     *
     * @var Metadata\MetadataRepoInterface
     */
    private $metadataRepo;

    public function __construct($storage, $metadataRepo)
    {
        $this->storage = $storage;
        $this->metadataRepo = $metadataRepo;
    }

    public function put($data, $metadata)
    {
        $data = \GuzzleHttp\Psr7\stream_for($data);

        $uuid = (string)Uuid::uuid4();
        $storageKey = $this->storage->saveData($uuid, $data);
        $metadata[self::STORAGE_KEY_ATTR] = $storageKey;
        $this->metadataRepo->saveMetadata($uuid, $metadata);
        $blob = new Blob();
        $blob->setId($uuid);
        $blob->setDataAsPsr7Stream($data);
        $blob->setMetadata($metadata);
        $blob->setLocalFilename($this->storage->getLocalFilename($storageKey));

        return $blob;
    }

    public function get($id)
    {
        $metadata = $this->metadataRepo->findBy([
            'uuid' => $id
        ]);
        if (count($metadata) == 1) {
            $metadata = $metadata[$id];
            assert(isset($metadata[self::STORAGE_KEY_ATTR]), "DefaultBlobStore: $id metadata corrupted, cannot find storage key");
            $data = $this->storage->getData($metadata[self::STORAGE_KEY_ATTR]);
            $blob = new Blob();
            $blob->setId($id);
            $blob->setDataAsPsr7Stream($data);
            $blob->setMetadata($metadata);
            $blob->setLocalFilename($this->storage->getLocalFilename($metadata[self::STORAGE_KEY_ATTR]));

            return $blob;
        }
        return null;
    }

    /**
     *
     * @param array filter on metadata
     *
     * @return Blob[]
     */
    public function findBy($criteria)
    {
        assert(count(array_filter($criteria)) > 0, 'Criteria cannot be empty');
        $metadata = $this->metadataRepo->findBy($criteria);
        // all in memory for now.. mainly intended to find small subsets of files based on some domain specific metadata
        $ret = [];
        foreach ($metadata as $uuid => $metadata) {
            assert(isset($metadata[self::STORAGE_KEY_ATTR]), "DefaultBlobStore: $uuid metadata corrupted, cannot find storage key");
            $data = $this->storage->getData($metadata[self::STORAGE_KEY_ATTR]);
            $blob = new Blob();
            $blob->setId($uuid);
            $blob->setMetadata($metadata);
            $blob->setDataAsPsr7Stream($data);
            $blob->setLocalFilename($this->storage->getLocalFilename($metadata[self::STORAGE_KEY_ATTR]));
            
            $ret[] = $blob;
        }
        return $ret;
    }
}