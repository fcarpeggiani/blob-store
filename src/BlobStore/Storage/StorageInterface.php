<?php

namespace BlobStore\Storage;

use Psr\Http\Message\StreamInterface;

/**
 *
 * @author davide
 */
interface StorageInterface
{
    /**
     *
     * @param string    $id
     * @param StreamInterface  $data
     *
     * @return string the storage key for later retrieve
     */
    public function saveData($id, StreamInterface $data);

    /**
     *
     * @param string $storageKey
     *
     * @return StreamInterface
     */
    public function getData($storageKey);
}