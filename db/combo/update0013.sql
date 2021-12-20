drop table ANALYTICS_TO_REFRESH;
create table PAGES_TO_REPLICATE
(
    ID        TEXT      NOT NULL PRIMARY KEY, -- The page id
    TIMESTAMP TIMESTAMP NOT NULL,             -- the timestamp
    REASON    TEXT      NOT NULL              -- the reason
);
