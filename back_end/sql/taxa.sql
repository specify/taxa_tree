DROP DATABASE IF EXISTS `gbif_col`;
CREATE DATABASE `gbif_col`;
USE `gbif_col`;

CREATE TABLE taxa (
    `taxonID` INT(10) PRIMARY KEY,
    `identifier` VARCHAR(256),
    `datasetID` CHAR(40),
    `datasetName` CHAR(40),
    `acceptedNameUsageID` INT(10),
    `parentNameUsageID` INT(10),
    `taxonomicStatus` VARCHAR(256),
    `taxonRank` VARCHAR(256),
    `verbatimTaxonRank` VARCHAR(256),
    `scientificName` VARCHAR(256),
    `kingdom` VARCHAR(256),
    `phylum` VARCHAR(256),
    `class` VARCHAR(256),
    `order` VARCHAR(256),
    `superfamily` VARCHAR(256),
    `family` VARCHAR(256),
    `genericName` VARCHAR(256),
    `genus` VARCHAR(256),
    `subgenus` VARCHAR(256),
    `specificEpithet` VARCHAR(256),
    `infraspecificEpithet` VARCHAR(256),
    `scientificNameAuthorship` VARCHAR(256),
    `source` VARCHAR(256),
    `namePublishedIn` VARCHAR(256),
    `nameAccordingTo` VARCHAR(256),
    `modified` VARCHAR(256),
    `description` VARCHAR(256),
    `taxonConceptID` VARCHAR(256),
    `scientificNameID` VARCHAR(256),
    `references` VARCHAR(256),
    `isExtinct` VARCHAR(5)
);