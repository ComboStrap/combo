
alter table PAGES add column PAGE_ID TEXT;

create unique index if not exists PAGES_UUID_INDEX ON PAGES (PAGE_ID);






