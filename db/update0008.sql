
alter table PAGES add column BACKLINK_COUNT TEXT;

create index if not exists BACKLINK_COUNT ON PAGES (BACKLINK_COUNT);






