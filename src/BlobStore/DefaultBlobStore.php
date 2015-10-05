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

    private $storage;
    private $metadataRepo;

    public function __construct($storage, $metadataRepo)
    {
        $this->storage = $storage;
        $this->metadataRepo = $metadataRepo;
    }

    public function put($data, $metadata)
    {
        $uuid = (string)Uuid::uuid4();
        $storageKey = $this->storage->saveData($uuid, $data);
        $metadata[self::STORAGE_KEY_ATTR] = $storageKey;
        $this->metadataRepo->saveMetadata($uuid, $metadata);
        $blob = new Blob();
        $blob->setId($uuid);
        $blob->setData($data);
        $blob->setMetadata($metadata);
        
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
            $blob->setData($data);
            $blob->setMetadata($metadata);

            return $blob;
        }
        return null;
    }

    public function findBy($criteria)
    {
        
    }
}