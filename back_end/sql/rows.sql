USE `col`;

SELECT `NameUsage`.`ID`            AS 'tsn',
      IF(
           LENGTH(`infraspecificEpithet`)>0,
           `infraspecificEpithet`,
           IF(
               LENGTH(`infragenericEpithet`)>0,
               `infragenericEpithet`,
               IF(
                   LENGTH(`scientificEpithet`)>0,
                   `scientificEpithet`,
                   IF(
                       LENGTH(`scientificName`)>0,
                       `scientificName`,
                       IF(
                           LENGTH(`uninomial`)>0,
                           `uninomial`,
                           `genericName`
                       )
                   )
               )
           )
       )                          AS 'name',
       `genericName`              AS 'common_name',
       `parentID`                 AS 'parent_tsn',
       `rank`                     AS 'rank',
       IFNULL(`authorship`,'')               AS 'author',
       IFNULL(`Reference`.`citation`,'')     AS 'source'
FROM   `NameUsage`
LEFT JOIN `Reference` ON `Reference`.`ID` = `nameReferenceID`
WHERE  `status` IN ('', 'accepted')
  AND  `rank` IN ('kingdom', 'subkingdom', 'phylum', 'subphylum', 'superclass', 'class', 'subclass', 'infraclass', 'superorder', 'order', 'suborder', 'infraorder', 'superfamily', 'family', 'subfamily', 'tribe', 'subtribe', 'genus', 'subgenus', 'section', 'subsection', 'species', 'subspecies', 'variety', 'subvariety')
