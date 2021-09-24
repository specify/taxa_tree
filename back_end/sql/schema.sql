DROP DATABASE IF EXISTS `gbif_col`;
CREATE DATABASE `gbif_col`;
USE `gbif_col`;

CREATE TABLE NameUsage (
    `ID` VARCHAR(30) PRIMARY KEY NOT NULL,
    `sourceID` TEXT,
    `parentID` TEXT,
    `basionymID` TEXT,
    `status` TEXT,
    `scientificName` TEXT,
    `authorship` TEXT,
    `rank` TEXT,
    `uninomial` TEXT,
    `genericName` TEXT,
    `infragenericEpithet` TEXT,
    `scientificEpithet` TEXT,
    `infraspecificEpithet` TEXT,
    `cultivarEpithet` TEXT,
    `namePhrase` TEXT,
    `nameReferenceID` TEXT,
    `namePublishedInYear` INTEGER,
    `namePublishedInPage` TEXT,
    `namePublishedInPageLink` TEXT,
    `code` TEXT,
    `nameStatus` TEXT,
    `accordingToID` TEXT,
    `referenceID` TEXT,
    `scrutinizer` TEXT,
    `scrutinizerID` TEXT,
    `scrutinizerDate` DATe,
    `extinct` BOOLEAN,
    `temporalRangeStart` TEXT,
    `temporalRangeEnd` TEXT,
    `environment` TEXT,
    `species` TEXT,
    `section` TEXT,
    `subgenus` TEXT,
    `genus` TEXT,
    `subtribe` TEXT,
    `tribe` TEXT,
    `subfamily` TEXT,
    `family` TEXT,
    `superfamily` TEXT,
    `suborder` TEXT,
    `order` TEXT,
    `subclass` TEXT,
    `class` TEXT,
    `subphylum` TEXT,
    `phylum` TEXT,
    `kingdom` TEXT,
    `sequenceIndex` INT,
    `link` TEXT,
    `remarks` TEXT
);

CREATE TABLE Reference (
    `ID` VARCHAR(30) PRIMARY KEY NOT NULL,
    `sourceID` TEXT,
    `citation` TEXT,
    `author` TEXT,
    `title` TEXT,
    `year` TEXT,
    `source` TEXT,
    `details` TEXT,
    `doi` TEXT,
    `link` TEXT,
    `remarks` TEXT
);


CREATE TABLE NameRelation (
    `nameID` TEXT,
    `relatedNameID` TEXT,
    `sourceID` TEXT,
    `type` TEXT,
    `referenceID` TEXT,
    `remarks` TEXT
);