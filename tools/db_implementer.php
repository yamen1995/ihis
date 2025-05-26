<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "IHIS";
$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$sql ="CREATE DATABASE IHIS";
$conn->query($sql);
$conn = new mysqli($servername, $username, $password, $dbname);
$sql ="CREATE TABLE `creds` ( 

  `CR_ID` int(11) NOT NULL AUTO_INCREMENT, 

  `PC_Type` varchar(3) NOT NULL, 

  `FName` varchar(20) DEFAULT NULL, 

  `LName` varchar(20) DEFAULT NULL, 

  `Gender` varchar(2) NOT NULL DEFAULT 'm', 

  `DOB` date DEFAULT NULL, 

  `DOA` date DEFAULT NULL, 

  `Phone` int(10) NOT NULL, 

  `Address` varchar(20) DEFAULT NULL, 

  `Sec_Code` varchar(20) NOT NULL, 

  `created_at` timestamp NOT NULL DEFAULT current_timestamp(), 

  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(), 

  PRIMARY KEY (`CR_ID`,`PC_Type`), 

  UNIQUE KEY `Phone` (`Phone`), 

  UNIQUE KEY `Sec_Code` (`Sec_Code`) 

 ); 

CREATE TABLE `staffs` ( 

  `ST_ID` int(11) NOT NULL AUTO_INCREMENT, 

  `Title` varchar(10) DEFAULT NULL, 

  `CR_ID` int(11) DEFAULT NULL, 

  PRIMARY KEY (`ST_ID`), 

  KEY `CR_ID` (`CR_ID`), 

  CONSTRAINT `staffs_ibfk_1` FOREIGN KEY (`CR_ID`) REFERENCES `creds` (`CR_ID`) 

 ); 

CREATE TABLE `doctors` ( 

  `DR_ID` int(11) NOT NULL AUTO_INCREMENT, 

  `Speciality` varchar(10) DEFAULT NULL, 

  `CR_ID` int(11) DEFAULT NULL, 

  PRIMARY KEY (`DR_ID`), 

  KEY `CR_ID` (`CR_ID`), 

  CONSTRAINT `doctors_ibfk_1` FOREIGN KEY (`CR_ID`) REFERENCES `creds` (`CR_ID`) 

 ); 

CREATE TABLE `rooms` ( 

  `RO_ID` int(11) NOT NULL AUTO_INCREMENT, 

  `Ro_Num` int(11) DEFAULT NULL, 

  `Bed_num` int(11) DEFAULT NULL, 

  `Is_Occupied` tinyint(1) DEFAULT NULL, 

  PRIMARY KEY (`RO_ID`) 

 ); 

CREATE TABLE `patients` ( 

  `PA_ID` int(11) NOT NULL AUTO_INCREMENT, 

  `Med_History` varchar(100) DEFAULT NULL, 

  `IS_Active` tinyint(1) DEFAULT 1, 

  `ST_ID` int(11) DEFAULT NULL, 

  `RO_ID` int(11) DEFAULT NULL, 

  `CR_ID` int(11) DEFAULT NULL, 

  `created_at` timestamp NOT NULL DEFAULT current_timestamp(), 

  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(), 

  PRIMARY KEY (`PA_ID`), 

  KEY `ST_ID` (`ST_ID`), 

  KEY `RO_ID` (`RO_ID`), 

  KEY `CR_ID` (`CR_ID`), 

  CONSTRAINT `patients_ibfk_1` FOREIGN KEY (`ST_ID`) REFERENCES `staffs` (`ST_ID`), 

  CONSTRAINT `patients_ibfk_2` FOREIGN KEY (`RO_ID`) REFERENCES `rooms` (`RO_ID`), 

  CONSTRAINT `patients_ibfk_3` FOREIGN KEY (`CR_ID`) REFERENCES `creds` (`CR_ID`) 

 ); 

CREATE TABLE `encounters` ( 

  `EN_ID` int(11) NOT NULL AUTO_INCREMENT, 

  `PA_ID` int(11) DEFAULT NULL, 

  `DR_ID` int(11) DEFAULT NULL, 

  `Date` date DEFAULT NULL, 

  `Time` time DEFAULT NULL, 

  PRIMARY KEY (`EN_ID`), 

  KEY `PA_ID` (`PA_ID`), 

  KEY `DR_ID` (`DR_ID`), 

  CONSTRAINT `encounters_ibfk_1` FOREIGN KEY (`PA_ID`) REFERENCES `patients` (`PA_ID`), 

  CONSTRAINT `encounters_ibfk_2` FOREIGN KEY (`DR_ID`) REFERENCES `doctors` (`DR_ID`) 

 ); 

CREATE TABLE `md_records` ( 

  `MDR_ID` int(11) NOT NULL AUTO_INCREMENT, 

  `EN_ID` int(11) DEFAULT NULL, 

  `Summery` varchar(100) DEFAULT NULL, 

  `Diagnosis` varchar(100) DEFAULT NULL, 

  PRIMARY KEY (`MDR_ID`), 

  KEY `EN_ID` (`EN_ID`), 

  CONSTRAINT `md_records_ibfk_1` FOREIGN KEY (`EN_ID`) REFERENCES `encounters` (`EN_ID`) 

 ); 

CREATE TABLE `prescriptions` ( 

  `PR_ID` int(11) NOT NULL AUTO_INCREMENT, 

  `MDR_ID` int(11) DEFAULT NULL, 

  `Drug` varchar(40) DEFAULT NULL, 

  `Dosage` varchar(40) DEFAULT NULL, 

  PRIMARY KEY (`PR_ID`), 

  KEY `MDR_ID` (`MDR_ID`), 

  CONSTRAINT `prescriptions_ibfk_1` FOREIGN KEY (`MDR_ID`) REFERENCES `md_records` (`MDR_ID`) 

 ); ";
$conn->multi_query($sql);
$conn->close();
?>
