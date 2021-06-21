USE `gbif`;

SELECT `taxonID`                  AS 'tsn',
       IF(
           `infraspecificEpithet`!='',
           `infraspecificEpithet`,
           IF(
               `specificEpithet`!='',
               `specificEpithet`,
               IF(
                   `canonicalName`!='',
                   `canonicalName`,
                   `genericName`
               )
           )
       )                          AS 'name',
       `genericName`              AS 'common_name',
       `parentNameUsageID`        AS 'parent_tsn',
       `taxonRank`                AS 'rank',
       `kingdom`                  AS 'kingdom',
       `scientificNameAuthorship` AS 'author',
       IF(
           LENGTH(`namePublishedIn`)>64,
           '',
           `namePublishedIn`
       )                          AS 'source'
FROM   `taxa`
WHERE  `taxonomicStatus`='accepted'
   AND `acceptedNameUsageID`=0
   AND `kingdom`!='incertae sedis'
   AND `taxonRank`!='unranked'
   AND (
        `taxonRank` IN ('Kingdom', 'Phylum')
        OR
        `parentNameUsageID`>8
       );
