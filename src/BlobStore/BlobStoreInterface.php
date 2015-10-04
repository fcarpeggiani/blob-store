<?php

namespace BlobStore;

/**
 * Simple interface to a binary + metadata storage facility
 *
 * @author davide
 */
interface BlobStoreInterface
{
    /**
     * Store data and metadata in this blobstore
     *
     * @param resource|\Psr\Http\Message\StreamInterface $data
     * @param array $metadata
     *
     * @return Blob
     */
    public function put($data, $metadata);

    /**
     * Retrieve a Blob
     *
     * @param string $id
     *
     * @return Blob or null if not found
     */
    public function get($id);

    /**
     * Find data by metadata
     *
     * @param array $criteria
     *
     * @return Blob[] a Traversable of Blobs
     */
    public function findBy($criteria);
}