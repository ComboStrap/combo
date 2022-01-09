-- Event Queue table
-- The id is the sqlite rowid
CREATE TABLE EVENTS_QUEUE (
  NAME                TEXT, -- Name
  DATA                TEXT, -- JSON
  TIMESTAMP  TIMESTAMP -- Timestamp creation
);

-- Log of the redirections
CREATE TABLE REDIRECTIONS_LOG (
  TIMESTAMP    TIMESTAMP,
  SOURCE       TEXT,
  TARGET       TEXT,
  TYPE         TEXT, -- which algorithm or manual entry
  REFERRER     TEXT,
  METHOD       TEXT
);

create index if not exists REDIRECTIONS_LOG_TIMESTAMP ON REDIRECTIONS_LOG (TIMESTAMP DESC);

