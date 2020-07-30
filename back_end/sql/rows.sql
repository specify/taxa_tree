USE `gbif_col`;

SELECT `taxonID`                  AS 'tsn',
       IF(
           `infraspecificEpithet`!='',
           `infraspecificEpithet`,
           IF(
               `specificEpithet`!='',
               `specificEpithet`,
               IF(
                   `scientificName`!='',
                   `scientificName`,
                   `genericName`
               )
           )
       )                          AS 'name',
       `genericName`              AS 'common_name',
       `parentNameUsageID`        AS 'parent_tsn',
       `taxonRank`                AS 'rank',
       `kingdom`                  AS 'kingdom',
       `scientificNameAuthorship` AS 'author',
       `references`               AS 'source',
       `isExtinct`                AS 'is_extinct'
FROM   `taxa`
WHERE  `taxonomicStatus` IN ('','accepted name','provisionally accepted name')
   AND `acceptedNameUsageID`=0;