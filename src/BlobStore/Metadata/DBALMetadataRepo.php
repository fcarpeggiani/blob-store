<?php

namespace BlobStore\Metadata;

use Doctrine\DBAL\Connection;
use BlobStore\Metadata\MetadataRepoInterface;

/**
 * Uses a simple key/val table to store metadata
 *
 * @author davide
 */
class DBALMetadataRepo implements MetadataRepoInterface
{
    /**
     *
     * @var Connection
     */
    private $connection;

    /**
     *
     * @var string
     */
    private $tableName;

    public function __construct(Connection $connection, $tableName = "BLOB_METADATA")
    {
        $this->connection = $connection;
        $this->tableName = $tableName;
    }

    public function saveMetadata($uuid, $metadata)
    {
        $this->connection->transactional(function() use ($uuid, $metadata) {
            foreach ($metadata as $key => $val) {
                assert(is_scalar($val), "Metadata values must be scalars, $key is of type " . gettype($val));
                $this->connection->insert($this->tableName, [
                    'UUID' => $uuid,
                    'META_KEY' => $key,
                    'META_VAL' => $val
                ]);
            }
        });
    }

    public function findBy($criteria)
    {
        $q = $this->connection->createQueryBuilder();
        $q->select('m.uuid', 'm.meta_key', 'm.meta_val')
          ->from($this->tableName, 'm')
          ->orderBy('m.uuid');
        if (isset($criteria['uuid'])) {
            $q->andWhere('m.uuid = :uuid');
            $q->setParameter('uuid', $criteria['uuid']);
            unset($criteria['uuid']);
        }
        foreach ($criteria as $key => $val) {
            $q1 = $this->connection->createQueryBuilder();
            $q1->select(1)->from($this->tableName, 'm1')
                ->where('m1.uuid = m.uuid')
                ->andWhere('m1.meta_key = :key')
                ->andWhere('m1.meta_val = :val');

            $q->andWhere('EXISTS (' . $q1->getSQL() . ')');
            $q->setParameter('key', $key)
              ->setParameter('val', $val);
        }
        $ret = [];
        foreach ($q->execute() as $row) {
            foreach ($row as $col => $val) {
                // always fetch by uppered column name
                $row[strtoupper($col)] = $val;
            }
            $ret[$row['UUID']][$row['META_KEY']] = $row['META_VAL'];
        }
        return $ret;
    }


}