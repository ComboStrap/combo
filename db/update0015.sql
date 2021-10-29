alter table PAGES add column REGION TEXT;
update PAGES set REGION = COUNTRY;
update pages set COUNTRY = null;

ALTER TABLE PAGES_ALIAS RENAME TO DEPRECATED_PAGES_ALIAS;
DROP INDEX PAGES_ALIAS_UK;
CREATE INDEX DEPRECATED_PAGES_ALIAS_UK ON DEPRECATED_PAGES_ALIAS (ALIAS);


CREATE TABLE PAGE_ALIASES
(
    PAGE_ID TEXT,  -- The page id
    PATH TEXT   -- The path value
);

CREATE UNIQUE INDEX PAGE_ALIAS_UK ON PAGE_ALIASES (PAGE_ID,PATH);
