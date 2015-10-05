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
     * @var StreamInterface
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

    /**
     *
     * @return resource
     */
    function getData()
    {
        return \GuzzleHttp\Psr7\StreamWrapper::getResource($this->data);
    }

    function getDataAsString()
    {        
        return (string)$this->data;
    }

    /**
     *
     * @return StreamInterface
     */
    function getDataAsPsr7Stream()
    {
        return $this->data;
    }

    function getMetadata()
    {
        return $this->metadata;
    }

    function setId($id)
    {
        $this->id = $id;
    }

    function setDataAsPsr7Stream(\Psr\Http\Message\StreamInterface $data)
    {
        $this->data = $data;
    }

    function setMetadata($metadata)
    {
        $this->metadata = $metadata;
    }


}