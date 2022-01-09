create view IF NOT EXISTS PAGE_REFERENCES_VW as
select
    p.path as referent_path,
    pr.reference as reference_path
from
    page_references pr
        inner join pages p on pr.page_id = p.page_id;
