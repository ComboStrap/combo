-- deleting the not null on canonical
-- 7. Making Other Kinds Of Table Schema Changes, see https://www.sqlite.org/lang_altertable.html
PRAGMA foreign_keys= OFF;
-- SQlite starts its own transaction, no BEGIN TRANSACTION;
create table PAGES_NEW
(
    ID               TEXT PRIMARY KEY,
    PATH             TEXT,
    PAGE_ID          TEXT UNIQUE,
    PAGE_ID_ABBR     TEXT,
    CANONICAL        TEXT,
    NAME             TEXT,
    TITLE            TEXT,
    H1               TEXT,
    DESCRIPTION      TEXT,
    DATE_MODIFIED    TEXT,
    DATE_CREATED     TEXT,
    DATE_PUBLISHED   TEXT,
    TYPE             TEXT,
    LANG             TEXT,
    WORD_COUNT       INTEGER,
    IS_LOW_QUALITY   INTEGER,
    DATE_START       TEXT,
    DATE_END         TEXT,
    COUNTRY          TEXT,
    BACKLINK_COUNT   INTEGER,
    IS_HOME          TEXT,
    DATE_REPLICATION TEXT,
    REGION           TEXT,
    LEVEL            INTEGER,
    IS_INDEX         TEXT,
    ANALYTICS        TEXT
);


INSERT INTO pages_new (ID, PATH, PAGE_ID, PAGE_ID_ABBR, CANONICAL, NAME, TITLE, H1, DESCRIPTION, DATE_MODIFIED,
                       DATE_CREATED, DATE_PUBLISHED, TYPE, LANG, WORD_COUNT, IS_LOW_QUALITY, DATE_START, DATE_END,
                       COUNTRY, BACKLINK_COUNT, IS_HOME, DATE_REPLICATION, REGION, LEVEL, IS_INDEX, ANALYTICS)
SELECT ID,
       PATH,
       PAGE_ID,
       PAGE_ID_ABBR,
       CANONICAL,
       NAME,
       TITLE,
       H1,
       DESCRIPTION,
       DATE_MODIFIED,
       DATE_CREATED,
       DATE_PUBLISHED,
       TYPE,
       LANG,
       WORD_COUNT,
       IS_LOW_QUALITY,
       DATE_START,
       DATE_END,
       COUNTRY,
       BACKLINK_COUNT,
       IS_HOME,
       DATE_REPLICATION,
       REGION,
       LEVEL,
       IS_INDEX,
       ANALYTICS
FROM pages;

-- drop view
drop view PAGE_REFERENCES_VW;
drop view PAGE_ALIASES_VW;

DROP TABLE pages;
ALTER TABLE pages_new
    RENAME TO pages;

-- Index
create index PAGES_BACKLINK_COUNT_IDX on PAGES (BACKLINK_COUNT);
create index PAGES_DATE_END_IDX on PAGES (DATE_END desc);
create index PAGES_DATE_START_IDX on PAGES (DATE_START desc);
create index PAGES_IS_HOME_IDX on PAGES (IS_HOME);
create index PAGES_IS_LOW_QUALITY_IDX on PAGES (IS_LOW_QUALITY);
create index PAGES_LANG_IDX on PAGES (LANG);
create index PAGES_DATE_CREATED_IDX on PAGES (DATE_CREATED desc);
create index PAGES_DATE_MODIFED_IDX on PAGES (DATE_MODIFIED desc);
create index PAGES_DATE_PUBLISHED_IDX on PAGES (DATE_CREATED desc);
create index PAGES_DATE_REPLICATION_IDX on PAGES (DATE_REPLICATION);
create index PAGES_PAGES_NAME on PAGES (NAME);
create index PAGES_PATH_IDX on PAGES (PATH);
create index PAGES_TYPE_IDX ON PAGES (TYPE);
create index PAGES_WORD_COUNT_IDX on PAGES (WORD_COUNT);

-- recreate views
create view IF NOT EXISTS PAGE_REFERENCES_VW as
select
    p.path as referent_path,
    pr.reference as reference_path
from
    page_references pr
        inner join pages p on pr.page_id = p.page_id;
create view IF NOT EXISTS PAGE_ALIASES_VW as
select
    p.path as page_path,
    pa.path as alias_path,
    pa.type as alias_type
from
    page_aliases pa
        inner join pages p on pa.page_id = p.page_id;

PRAGMA foreign_keys=ON;
