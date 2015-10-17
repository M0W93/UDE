<?php
require 'vendor/autoload.php';

$app = new \Slim\Slim();

function getDB()
{
    $dbhost = "localhost";
    $dbuser = "kibl";
    $dbpass = "hfgDfhg74++#";
    $dbname = "kibl_ude";
 
    $mysql_conn_string = "mysql:host=$dbhost;dbname=$dbname";
    $dbConnection = new PDO($mysql_conn_string, $dbuser, $dbpass); 
    $dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $dbConnection;
}

//Funktion zum einfügen eines neuen Datensatzes
$app->get('/insert/:long/:lat/:city/:value/:typ', function($long, $lat, $city, $value, $typ){
	$db = getDB();
	$timestamp = date("Ymd");
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
$app->get('/get/:typ/:year/:month(/:day)', function($typ, $year, $month, $day = '') {
	$db = getDB();

		if(!$day){
			$date = $year . "-" . $month;
		}else{
		$date = $year . "-" . $month . "-" . $day;
		}


	$sth = $db->prepare("
		SELECT * FROM sensoren WHERE typ = :typ AND (timestamp LIKE :date OR substr(timestamp, 1, 7) LIKE :date)
	");

	$sth->bindParam(":typ", $typ, PDO::PARAM_INT);
	$sth->bindParam(":date", $date, PDO::PARAM_STR);

	$sth->execute();
	$result = $sth->fetchAll();
	echo json_encode($result);

});

//Parset die CSV Datei und schreibt sie in die Datenbank
$app->get('/parse', function () {
		$app = \Slim\Slim::getInstance();
	$timestamp = date("Y-m-d");
	$typ = 1;
		$db = getDB();

		if (($handle = fopen("stationdata.csv.json", "r")) !== FALSE) {
		  while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
		  	echo getEuCode($data[0])[1] . "<br>";
		  	
		    $sth = $db->prepare("
		    	INSERT INTO seonsoren 
		    	VALUES (:timestamp, :long, :lat, :city, :value, :typ, :number)
		   	");

		    $sth->bindParam(":timestamp", $timestamp, PDO::PARAM_INT);
		   	$sth->bindParam(":long", getEuCode($data[0])[1], PDO::PARAM_STR);
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