
alter table PAGES add column IS_HOME TEXT;

create index if not exists IS_HOME ON PAGES (IS_HOME);






