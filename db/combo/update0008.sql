
alter table PAGES add column BACKLINK_COUNT INTEGER;

create index if not exists BACKLINK_COUNT ON PAGES (BACKLINK_COUNT);






