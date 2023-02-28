drop TABLE EVENTS_QUEUE;

-- Event Queue table
CREATE TABLE EVENTS_QUEUE
(
    NAME      TEXT,     -- Name
    DATA      TEXT,     -- JSON
    DATA_HASH TEXT,     -- JSON Hash
    TIMESTAMP TIMESTAMP, -- Timestamp creation,
    constraint pk_events_queue primary key (NAME, DATA_HASH)
);
