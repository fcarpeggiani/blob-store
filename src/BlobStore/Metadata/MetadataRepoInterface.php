<?php

namespace BlobStore\Metadata;

/**
 * Stores metadata informations about blobs
 * @author davide
 */
interface MetadataRepoInterface
{
    /**
     * Saves metadata
     *
     * @param array $metadata
     */
    public function saveMetadata($uuid, $metadata);

    /**
     * Find by some criteria
     *
     * NOTE: implementors must index the resulting array by uuid!
     *
     * @param array $criteria
     *
     * @return array or array like object for large resultsets
     */
    public function findBy($criteria);
}