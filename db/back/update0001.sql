-- Event Queue table
-- The id is the sqlite rowid
CREATE TABLE EVENTS_QUEUE (
  NAME                TEXT, -- Name
  DATA                TEXT, -- JSON
  CREATION_TIMESTAMP  TIMESTAMP -- Timestamp creation
);

