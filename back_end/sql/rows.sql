USE `worms`;

SELECT `child`.`taxonID`                     AS 'tsn',
      IF(
           LENGTH(`infraspecificEpithet`)>0,
           `infraspecificEpithet`,
           IF(
               LENGTH(`specificEpithet`)>0,
               `specificEpithet`,
               IF(
                   LENGTH(`acceptedNameUsage`)>0,
                   `acceptedNameUsage`,
                   IF(
                       LENGTH(`scientificName`)>0,
                       `scientificName`,
                       IF(
                           LENGTH(`genus`)>0,
                           `genus`,
                           IF(
                               LENGTH(`family`)>0,
                               `family`,
                               IF(
                                   LENGTH(`order`)>0,
                                   `order`,
                                   IF(
                                       LENGTH(`class`)>0,
                                       `class`,
                                       `phylum`
                                   )
                               )
                           )
                       )
                   )
               )
           )
       )                                     AS 'name',
       `parentNameUsage`                     AS 'common_name',
       `parentNameUsageID`                   AS 'parent_tsn',
       `taxonRank`                           AS 'rank',
       IFNULL(`scientificNameAuthorship`,'') AS 'author',
       IFNULL(`references`,'')     AS 'source'
FROM   `taxon` `child`
WHERE `taxonomicStatus` = 'accepted'
  AND (
      `taxonRank` = 'Phylum'
      OR (
        SELECT COUNT(*)
        FROM taxon `parent`
        WHERE `parent`.taxonID = `child`.parentNameUsageID
          AND taxonomicStatus = 'accepted'
      ) = 1
  )
