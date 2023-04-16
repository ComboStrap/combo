alter table PAGES add column IS_INDEX TEXT;

drop index IS_HOME;

create index if not exists IS_INDEX ON PAGES (IS_INDEX);
