-- The id that needs a refresh
create table ANALYTICS_TO_REFRESH (
     ID          TEXT NOT NULL PRIMARY KEY, -- The page id
     TIMESTAMP   TIMESTAMP NOT NULL -- the timestamp
);

create table ANALYTICS_REFRESHED (
     ID          TEXT NOT NULL, -- The page id
     TIMESTAMP   TIMESTAMP NOT NULL -- the timestamp
)
