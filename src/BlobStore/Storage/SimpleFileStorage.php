<?php

namespace BlobStore\Storage;

use Exception;
use GuzzleHttp\Psr7\LazyOpenStream;
use Psr\Http\Message\StreamInterface;

/**
 * Stores data in a common directory
 *
 * @author davide
 */
class SimpleFileStorage implements StorageInterface
{
    /**
     *
     * @var string directory prefix to store all data
     */
    private $prefix;

    /**
     *
     * @var int create a deep directory structure for balancing
     */
    private $nestingLevel;

    public function __construct($prefix, $nestingLevel = 0)
    {
        assert($prefix != '' && is_writable($prefix), "$prefix is not writable by current user");
        $this->prefix = $prefix;
        $this->nestingLevel = $nestingLevel;
    }

    public function saveData($id, StreamInterface $data)
    {
        // FIXME: with high load there are concurrency issues I think
        $tries = 0;
        do {
          $filename = $this->createFilename($this->nestingLevel);
          $dest = $this->prefix . DIRECTORY_SEPARATOR . $filename;
          $ok = ! file_exists($dest);
          $tries++;
        } while(!$ok && $tries < 5);
        if(! $ok) {
            throw new Exception("Cannot write $filename in " . $this->prefix);
        }
        $filedir = dirname($filename);
        if($filedir != '.') {
            @mkdir($this->prefix . DIRECTORY_SEPARATOR . $filedir, 0777, TRUE);
        }
        // if another process created the same file in the meanwhile, we don't want
        // to overwrite it at least!
        $f = @fopen($dest, 'x+');
        if ($f === FALSE) {
            throw new Exception("Cannot write $dest. Maybe another process created it concurrently.");
        }
        $f = \GuzzleHttp\Psr7\stream_for($f);
        \GuzzleHttp\Psr7\copy_to_stream($data, $f);
        $f->close();
        $data->rewind();

        return $filename;
    }

    /**
     * Creates a random filename, with a directory nesting
     * for balancing
     */
    private function createFilename($nestingLevel) {
        $n = '';
        for($i = 0; $i < 40; $i++) {
            $n .= mt_rand(0, 9);
            if ($nestingLevel > $i) {
                $n .= DIRECTORY_SEPARATOR;
            }
        }

        return $n;
    }

    public function getData($storageKey)
    {
        $filename = $this->prefix . DIRECTORY_SEPARATOR . $storageKey;
        assert(is_readable($filename), "$filename does not exists!");
        // don't open the file now, file descriptors are not infinite!
        return new LazyOpenStream($filename, 'r');
    }
}