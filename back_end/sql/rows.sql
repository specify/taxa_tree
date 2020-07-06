USE `ITIS`;

SELECT    `unit`.`tsn`                AS `taxon`,
          `unit`.`complete_name`      AS `name`,
          `unit`.`unit_name1`         AS 'common_name',
          `unit`.`parent_tsn`         AS 'parent_taxon',
          `unit`.`rank_id`            AS 'rank_id',
          `unit`.`kingdom_id`         AS 'kingdom_id',
          `author`.`shortauthor`      AS 'author',
          `source`.`publication_name` AS 'source'
FROM      `taxonomic_units` `unit`
LEFT JOIN `strippedauthor` `author`
       ON `unit`.`taxon_author_id` = `author`.`taxon_author_id`
LEFT JOIN `reference_links` `source_reference`
       ON `unit`.`tsn` = `source_reference`.`tsn`
      AND `original_desc_ind` = 'Y'
      AND `doc_id_prefix` = 'PUB'
LEFT JOIN `publications` `source`
       ON `source_reference`.`documentation_id` = `source`.`publication_id`
      AND LENGTH(`source`.`publication_name`)<64
WHERE     `unit`.`name_usage` = 'valid';