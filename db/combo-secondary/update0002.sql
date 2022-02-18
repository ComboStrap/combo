-- Log of cache deletion
CREATE TABLE CACHE_LOG (
  TIMESTAMP    TIMESTAMP,
  EVENT        TEXT,
  PATH         TEXT,
  MIME         TEXT,
  MESSAGE      TEXT
);

create index if not exists CACHE_LOG_TIMESTAMP ON CACHE_LOG (TIMESTAMP DESC);

