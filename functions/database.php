<?php

/////класс работы с базой данных
class connect_db {
   public $state="";
   public $i="";
   public $dbo=null;
   
   function __construct() {
   try {
		require("./config_2.php");
		////подключение к базе
		$conn=DB_DRIVER.":host=".DB_HOSTNAME.";dbname=".DB_DATABASE;
		$db=new PDO($conn,DB_USERNAME,DB_PASSWORD);
		$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
		$this->dbo=$db;
		$db->exec("set character set ".DB_CHARACTER);
		$db->exec("set character_set_client=".DB_CHARACTER);
		$db->exec("set character_set_results=".DB_CHARACTER);
		$result=$db->exec("set collation_connection=".DB_COLLATION);
		$this->state="connected";
	} catch(PDOException $e) {
	////ошибка доступа к базе ланных
	$this->state="";
	
	}
   }
   
   function __destruct() {
	$this->sbo=null;
   }
}


?>