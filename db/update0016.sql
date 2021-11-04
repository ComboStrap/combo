alter table PAGE_ALIASES add column TYPE text; -- the type ie redirection or synonym
CREATE UNIQUE INDEX PAGE_ALIAS_TYPE ON PAGE_ALIASES (TYPE);

create view IF NOT EXISTS PAGE_ALIASES_VW as
select
    p.path as page_path,
    pa.path as alias_path,
    pa.type as alias_type
from
    page_aliases pa
        inner join pages p on pa.page_id = p.page_id;
