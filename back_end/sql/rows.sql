USE `ITIS`;

SELECT    `unit`.`tsn`                AS 'taxon',
          IF(
              `unit`.`unit_name4` IS NOT NULL,
              `unit`.`unit_name4`,
              IF(
                  `unit`.`unit_name3` IS NOT NULL,
                  `unit`.`unit_name3`,
                  IF(
                      `unit`.`unit_name2` IS NOT NULL,
                      `unit`.`unit_name2`,
                      `unit`.`unit_name1`
                      )
                  )
          )                           AS 'name',
          ''                          AS 'common_name',
          `unit`.`parent_tsn`         AS 'parent_taxon',
          `unit`.`rank_id`            AS 'rank_id',
          `unit`.`kingdom_id`         AS 'kingdom_id',
          `author`.`taxon_author`     AS 'author',
          `source`.`publication_name` AS 'source'
FROM      `taxonomic_units` `unit`
LEFT JOIN `taxon_authors_lkp` `author`
       ON `unit`.`taxon_author_id` = `author`.`taxon_author_id`
LEFT JOIN `reference_links` `source_reference`
       ON `unit`.`tsn` = `source_reference`.`tsn`
      AND `original_desc_ind` = 'Y'
      AND `doc_id_prefix` = 'PUB'
LEFT JOIN `publications` `source`
       ON `source_reference`.`documentation_id` = `source`.`publication_id`
      AND LENGTH(`source`.`publication_name`)<64
WHERE     `unit`.`name_usage` = 'valid'
       OR `unit`.`name_usage` = 'accepted';