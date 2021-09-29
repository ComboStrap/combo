
alter table PAGES add column UUID TEXT;

create unique index if not exists PAGES_UUID_INDEX ON PAGES (UUID);






