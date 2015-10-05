CREATE TABLE blob_metadata (
  uuid VARCHAR(100) NOT NULL,
  meta_key VARCHAR(1000) NOT NULL,
  meta_val VARCHAR(4000) NOT NULL,
  PRIMARY KEY (uuid, meta_key)
);
CREATE INDEX idx_blobmetaval ON blob_metadata(meta_val);