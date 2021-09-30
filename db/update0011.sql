
alter table PAGES add column DATE_REPLICATION TEXT;

create unique index if not exists PAGES_DATE_REPLICATION ON PAGES (DATE_REPLICATION);






