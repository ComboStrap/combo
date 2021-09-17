
alter table PAGES add column TYPE TEXT;
alter table PAGES add column LANG TEXT;
alter table PAGES add column WORD_COUNT INTEGER;
alter table PAGES add column IS_LOW_QUALITY INTEGER;
alter table PAGES add column DATE_START TEXT;
alter table PAGES add column DATE_END TEXT;
alter table PAGES add column COUNTRY TEXT;

create index if not exists WORD_COUNT ON PAGES (WORD_COUNT);
create index if not exists LANG ON PAGES (LANG);
create index if not exists IS_LOW_QUALITY ON PAGES (IS_LOW_QUALITY);
create index if not exists TYPE ON PAGES (TYPE);
create index if not exists DATE_START ON PAGES (DATE_START DESC);
create index if not exists DATE_END ON PAGES (DATE_END DESC);






