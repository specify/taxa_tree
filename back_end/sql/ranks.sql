USE `ITIS`;

SELECT   `kingdom_id` AS 'kingdom_id',
         `rank_id` AS 'rank_id',
         `rank_name` AS 'rank_name',
         `dir_parent_rank_id` AS 'parent_id'
FROM     `taxon_unit_types`
ORDER BY `kingdom_id`, `rank_id`;