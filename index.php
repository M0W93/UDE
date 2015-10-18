<?php
header('Access-Control-Allow-Origin: *');

require 'vendor/autoload.php';

$app = new \Slim\Slim();

require_once 'db.php';

//Funktion zum einfügen eines neuen Datensatzes
$app->get('/insert/:long/:lat/:city/:value/:typ', function($long, $lat, $city, $value, $typ){
	$db = getDB();
	$timestamp = date("Y-m-d");
	$number = 1;
	$sth = $db->prepare("
		INSERT INTO sensoren
		VALUES (:timestamp, :long, :lat, :city, :value, :typ, :number)
	");

	$sth->bindParam(":timestamp", $timestamp, PDO::PARAM_INT);
	$sth->bindParam(":long", $long, PDO::PARAM_STR);
	$sth->bindParam(":lat", $lat, PDO::PARAM_STR);
	$sth->bindParam(":city", $city, PDO::PARAM_STR);
	$sth->bindParam(":value", $value, PDO::PARAM_INT);
	$sth->bindParam(":typ", $typ, PDO::PARAM_INT);
	$sth->bindParam(":number", $number, PDO::PARAM_INT);

	$sth->execute();
});
 
//Funktion um die Datensätze zu filtern
$app->get('/get/:typ/:year(/:month(/:day))', function($typ, $year, $month = '', $day = '') {
	$db = getDB();

		if(!$day){
			if(!$month){
				$date = $year;
			}else{
				$date = $year . "-" . $month;
			}
		}else{
			$date = $year . "-" . $month . "-" . $day;
		}

	$sth = $db->prepare("
		SELECT * FROM sensoren 
		WHERE (typ = :typ) 
		AND (timestamp = :date 
		OR substr(timestamp, 1, 7) = :date 
		OR substr(timestamp, 1, 4) = :date)
	");

	$sth->bindParam(":typ", $typ, PDO::PARAM_INT);
	$sth->bindParam(":date", $date, PDO::PARAM_STR);
	$sth->bindParam(":date", $date, PDO::PARAM_STR);
	$sth->bindParam(":date", $date, PDO::PARAM_STR);


	$sth->execute();


	$result = $sth->fetchAll(PDO::FETCH_ASSOC);
	echo json_encode($result);

});

//Parset die CSV Datei und schreibt sie in die Datenbank
$app->get('/parse', function () {
	$app = \Slim\Slim::getInstance();
	$timestamp = date("Y-m-d");
	$long = getEuCode($data[0])[1];
	$typ = 1;
		$db = getDB();

		if (($handle = fopen("stationdata-17-10-2015.csv.json", "r")) !== FALSE) {
		  while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
		  	
		    $sth = $db->prepare("
		    	INSERT INTO sensoren 
		    	VALUES (:timestamp, :long, :lat, :city, :value, :typ, :number)
		   	");

		    $sth->bindParam(":timestamp", $timestamp, PDO::PARAM_STR);
		   	$sth->bindParam(":long", $long, PDO::PARAM_STR);
		   	$sth->bindParam(":lat", getEuCode($data[0])[2], PDO::PARAM_STR);
		   	$sth->bindParam(":city", getEuCode($data[0])[0], PDO::PARAM_STR);
		   	$sth->bindParam(":value", $data[2], PDO::PARAM_INT);
		   	$sth->bindParam(":typ", $typ, PDO::PARAM_INT);
		   	$sth->bindParam(":number", $data[0], PDO::PARAM_STR);

		   	$sth->execute();
			
		  }
		  fclose($handle);
		}
});

//Wandelt den EUCode in Koordinaten und den Stadtnamen um
function getEuCode($code){

	if (($handle = fopen("Bericht_EU_Meta_Stationen.csv", "r")) !== FALSE) {
	  while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
	  	if (count($data) > 1) {
	  		if ($data[1] == $code) {
		    	fclose($handle);
		    	return [$data[3], $data[6], $data[8]];
		    }
	  	}
	    
	  }
	  fclose($handle);
	}
}

$app->run();