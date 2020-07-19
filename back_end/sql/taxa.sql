DROP DATABASE IF EXISTS `gbif`;
CREATE DATABASE `gbif`;
USE `gbif`;

CREATE TABLE taxa (
    `taxonID` INT(10) PRIMARY KEY,
    `datasetID` CHAR(40),
    `parentNameUsageID` INT(10),
    `acceptedNameUsageID` INT(10),
    `originalNameUsageID` INT(10),
    `scientificName` VARCHAR(256),
    `scientificNameAuthorship` VARCHAR(256),
    `canonicalName` VARCHAR(256),
    `genericName` VARCHAR(256),
    `specificEpithet` VARCHAR(256),
    `infraspecificEpithet` VARCHAR(256),
    `taxonRank` VARCHAR(256),
    `nameAccordingTo` VARCHAR(256),
    `namePublishedIn` VARCHAR(256),
    `taxonomicStatus` VARCHAR(256),
    `nomenclaturalStatus` VARCHAR(256),
    `taxonRemarks` VARCHAR(256),
    `kingdom` VARCHAR(256),
    `phylum` VARCHAR(256),
    `class` VARCHAR(256),
    `order` VARCHAR(256),
    `family` VARCHAR(256),
    `genus` VARCHAR(256)
)