DROP DATABASE IF EXISTS `worms`;
CREATE DATABASE `worms`;
USE `worms`;

DROP TABLE IF EXISTS taxon;
CREATE TABLE taxon (
    `taxonID` VARCHAR(45) PRIMARY KEY NOT NULL,
    `scientificNameID` VARCHAR(45),
    `acceptedNameUsageID` VARCHAR(45),
    `parentNameUsageID` VARCHAR(45),
    `namePublishedInID` VARCHAR(71),
    `scientificName` TEXT,
    `acceptedNameUsage` TEXT,
    `parentNameUsage` TEXT,
    `namePublishedIn` TEXT,
    `namePublishedInYear` TEXT,
    `kingdom` TEXT,
    `phylum` TEXT,
    `class` TEXT,
    `order` TEXT,
    `family` TEXT,
    `genus` TEXT,
    `subgenus` TEXT,
    `specificEpithet` TEXT,
    `infraspecificEpithet` TEXT,
    `taxonRank` TEXT,
    `scientificNameAuthorship` TEXT,
    `nomenclaturalCode` TEXT,
    `taxonomicStatus` TEXT,
    `nomenclaturalStatus` TEXT,
    `modified` TEXT,
    `bibliographicCitation` TEXT,
    `references` TEXT,
    `license` TEXT,
    `rightsHolder` TEXT,
    `datasetName` TEXT,
    `institutionCode` TEXT,
    `datasetID` TEXT
);