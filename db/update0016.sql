alter table PAGE_ALIASES add column type text; -- the type ie redirection or synonym
CREATE UNIQUE INDEX PAGE_ALIAS_TYPE ON PAGE_ALIASES (TYPE);
