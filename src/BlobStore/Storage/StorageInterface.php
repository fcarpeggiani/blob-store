<?php

namespace BlobStore\Storage;

/**
 *
 * @author davide
 */
interface StorageInterface
{
    /**
     *
     * @param string    $id
     * @param resource  $data
     *
     * @return string the storage key for later retrieve
     */
    public function saveData($id, $data);

    /**
     *
     * @param string $storageKey
     */
    public function getData($storageKey);
}