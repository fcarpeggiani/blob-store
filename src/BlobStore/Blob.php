<?php

namespace BlobStore;

/**
 * A binary object with associated metadata
 *
 * @author davide
 */
class Blob
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var resource
     */
    private $data;

    /**
     * @var array
     */
    private $metadata = [];

    function getId()
    {
        return $this->id;
    }

    function getData()
    {
        return $this->data;
    }

    function getDataAsString()
    {
        $data = stream_get_contents($this->getData());
        rewind($this->getData());
        return $data;
    }

    function getMetadata()
    {
        return $this->metadata;
    }

    function setId($id)
    {
        $this->id = $id;
    }

    function setData($data)
    {
        $this->data = $data;
    }

    function setMetadata($metadata)
    {
        $this->metadata = $metadata;
    }


}