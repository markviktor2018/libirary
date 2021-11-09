<?php

/////класс работы с фондами
class fonds {

	public $encryption;
	private $dbos;
	
	function __construct() {
		////создание класса шифрования
		$this->encryption = new encryption();
		///создание класса доступа к БД
		$db=new connect_db();
		 if($db->state=="connected") {
		   $this->dbos=$db;  
		 }
	}
	

   
	function __destruct() {
	
	}
	
	/////////////////////////////////////////////////////////////////
	//////////////////////работа с секциями//////////////////////////
	/////////////////////////////////////////////////////////////////
	function add_section($name,$description,$password="") {
		////генерация ключа секции
		$encryption_key=$this->encryption->generate_key(time());
		
		/////генерация хэша ключа секции для контроля и сравнения
		$password_control_str=$this->encryption->generate_key($password);
		
		////добавление этой секции в базу данных
		if($this->dbos->state=="connected") {
			
			$name=$this->dbos->dbo->quote($name);
			$encryption_key=$this->dbos->dbo->quote($encryption_key);
			$password_control_str=$this->dbos->dbo->quote($password_control_str);
			$description=$this->dbos->dbo->quote($description);
			
			$sql="INSERT INTO sections(naim,encryption_key,description,password) values($name,$encryption_key,$description,$password_control_str)";
			$this->dbos->dbo->exec($sql);
		}
	}
	
	function edit_section($section_id,$name,$description) {
		
		if($this->dbos->state=="connected") {
			$name=$this->dbos->dbo->quote($name);
			$description=$this->dbos->dbo->quote($description);
			$section_id=$this->dbos->dbo->quote($section_id);
			
			$sql="UPDATE sections set name=$name,description=$description=$description where id=$section_id";
			$this->dbos->dbo->exec($sql);
		}
	}
	
	function delete_section($section_id) {
		
		if($this->dbos->state=="connected") {
			$section_id=$this->dbos->dbo->quote($section_id);
			
			////удаление групп////////////////////////
							$groups=get_list_of_groups($section_id,$section_password,$private_password);
							foreach($groups as $group) {
								
								$this->delete_group($group->id,$section_password,$private_password);
							}
			
			
			$sql="delete from sections where id=$section_id";
			$this->dbos->dbo->exec($sql);
		}
	}
	
	function get_list_of_sections($password) {
		
		if($this->dbos->state=="connected") {
			$password_control_str=$this->encryption->generate_key($password);
			$password_control_str_empty=$this->encryption->generate_key("");
			
			$password_control_str=$this->dbos->dbo->quote($password_control_str);
			$password_control_str_empty=$this->dbos->dbo->quote($password_control_str_empty);
			
			///сначала все, что доступно без пароля
			$sql="SELECT id from sections where password=$password_control_str_empty";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$founded_sections[]=$row[0];
			}
			/////теперь если пароль задан
			if($password!="") {
				$sql="SELECT id from sections where password=$password_control_str";
					foreach ($this->dbos->dbo->query($sql) as $row){
						$founded_sections[]=$row[0];
				}
			}
			
			return $founded_sections;
		}
	}
	
	/////////////////////////////////////////////////////////////////
	//////////////////////работа с рубриками/////////////////////////
	/////////////////////////////////////////////////////////////////
	function add_group($section_id,$name,$description,$keywords,$section_password="",$private_password="") {
		////генерация ключа секции
		$encryption_key=$this->encryption->generate_key(time());
		
		////добавление этой секции в базу данных
		if($this->dbos->state=="connected") {
			
			/////подготовка класса шифрования
			$sql="SELECT encryption_key from sections where id=$section_id";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$encryption_key_section=$row[0];
			}
			
			echo "encryption_password=$private_password<br>";
			
			$this->encryption->encryption_key_section=$encryption_key_section;  /// надо получить ключ секции
			$this->encryption->encryption_key_section_password=$section_password;
			$this->encryption->encryption_key_group=""; ////это группа, не надо тут никаких ключей
			$this->encryption->encryption_key_private=$private_password;
			
			$this->encryption->prepare_encryption_key();
			
			/////шифруем метаданные, навзвание и описание
			
			
			$name=$this->dbos->dbo->quote($this->encryption->encrypt_string($name));
			$encryption_key=$this->dbos->dbo->quote($encryption_key);
			$section_id=$this->dbos->dbo->quote($section_id);
			$keywords=$this->dbos->dbo->quote($this->encryption->encrypt_string($keywords));
			$description=$this->dbos->dbo->quote($this->encryption->encrypt_string($description));
			$hash_password=$this->dbos->dbo->quote($this->encryption->encrypt_string("1111111"));
			
			$sql="INSERT INTO groups(section_id,naim,description,keywords,encryption_key,hash_password) values($section_id,$name,$description,$keywords,$encryption_key,$hash_password)";
			$this->dbos->dbo->exec($sql);
		}
	}
	
	function edit_group($group_id,$name,$description,$keywords,$section_password="",$private_password="") {
		
		if($this->dbos->state=="connected") {
			
			$group_id=$this->dbos->dbo->quote($group_id);
			/////подготовка класса шифрования
			$sql="SELECT encryption_key from sections where id in(select section_id from groups where id=$group_id)";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$encryption_key_section=$row[0];
			}
			
			$this->encryption->encryption_key_section=$encryption_key_section;  /// надо получить ключ секции
			$this->encryption->encryption_key_section_password=$section_password;
			$this->encryption->encryption_key_group=""; ////это группа, не надо тут никаких ключей
			$this->encryption->encryption_key_private=$private_password;
			
			$this->encryption->prepare_encryption_key();
			
			
			$name=$this->dbos->dbo->quote($this->encryption->encrypt_string($name));
			$keywords=$this->dbos->dbo->quote($this->encryption->encrypt_string($keywords));
			$description=$this->dbos->dbo->quote($this->encryption->encrypt_string($description));
			
			
			$sql="UPDATE groups set naim=$name,description=$description,keywords=$keywords where id=$group_id";
			$this->dbos->dbo->exec($sql);
		}
	}
	
	function delete_group($group_id,$section_password="",$private_password="") {
		
		if($this->dbos->state=="connected") {
			
			$sql="SELECT encryption_key from sections where id in(select section_id from groups where id=$group_id)";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$encryption_key_section=$row[0];
			}
			
			$this->encryption->encryption_key_section=$encryption_key_section;  /// надо получить ключ секции
			$this->encryption->encryption_key_section_password=$section_password;
			$this->encryption->encryption_key_group=""; ////это группа, не надо тут никаких ключей
			$this->encryption->encryption_key_private=$private_password;
			
			$this->encryption->prepare_encryption_key();
			
			
			$this->encryption->prepare_encryption_key();
			
			///сначала все, что доступно без пароля
			$sql="SELECT id,naim,description,keywords,hash_password from groups where section_id=$section_id";
				foreach ($this->dbos->dbo->query($sql) as $row){
				
					$hash_str=$this->encryption->decrypt_string($row[4])*1;
					
					if($hash_str==1111111) {
			
						if($row[0]==$group_id) {
							
							////удаление подгрупп////////////////////////
							$sub_groups=get_list_of_sub_groups($group_id,$section_password,$private_password);
							foreach($sub_groups as $sub_group) {
								
								$this->delete_sub_group($sub_group->id,$section_password,$private_password);
							}
							
							$sql="delete from groups where id=$group_id";
							$this->dbos->dbo->exec($sql);
						}
			
					}
			}
		}
	}
	
	
	//////////////список групп
	function get_list_of_groups($section_id,$section_password="",$private_password="") {
		
		if($this->dbos->state=="connected") {
			
			
			$sql="SELECT encryption_key from sections where id=$section_id";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$encryption_key_section=$row[0];
			}
			
			$this->encryption->encryption_key_section=$encryption_key_section;  /// надо получить ключ секции
			$this->encryption->encryption_key_section_password=$section_password;
			$this->encryption->encryption_key_group=""; ////это группа, не надо тут никаких ключей
			$this->encryption->encryption_key_sub_group=""; ////это группа, не надо тут никаких ключей
			$this->encryption->encryption_key_private=$private_password;
			
			
			$this->encryption->prepare_encryption_key();
			
			///сначала все, что доступно без пароля
			$sql="SELECT id,naim,description,keywords,hash_password from groups where section_id=$section_id";
				foreach ($this->dbos->dbo->query($sql) as $row){
				
					$hash_str=$this->encryption->decrypt_string($row[4])*1;
					
					if($hash_str==1111111) {
						unset($group_info);
						$group_info->id=$row[0];
						$group_info->name=$this->encryption->decrypt_string($row[1]);
						$group_info->description=$this->encryption->decrypt_string($row[2]);
						$group_info->keywords=$this->encryption->decrypt_string($row[3]);
						$group_info->section_id=$section_id;
						$founded_groups[]=$group_info;
					
					}
			}
			
			
			return $founded_groups;
		}
	}
	
	
	///////////////добавление подгруппы
	
	
	
	function add_sub_group($group_id,$name,$description,$keywords,$section_password="",$private_password="") {
		////генерация ключа секции
		$encryption_key=$this->encryption->generate_key(time());
		
		////добавление этой секции в базу данных
		if($this->dbos->state=="connected") {
			
			/////подготовка класса шифрования
			$sql="SELECT encryption_key from sections where id in (select section_id from groups where id=$group_id)";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$encryption_key_section=$row[0];
			}
			
			$sql="SELECT encryption_key from groups where id=$group_id";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$encryption_key_group=$row[0];
			}
			
			///echo "encryption_password=$private_password<br>";
			
			$this->encryption->encryption_key_section=$encryption_key_section;  /// надо получить ключ секции
			$this->encryption->encryption_key_section_password=$section_password;
			$this->encryption->encryption_key_group=$encryption_key_group;
			$this->encryption->encryption_key_private=$private_password;
			
			$this->encryption->prepare_encryption_key();
			
			/////шифруем метаданные, навзвание и описание
			
			
			$name=$this->dbos->dbo->quote($this->encryption->encrypt_string($name));
			$encryption_key=$this->dbos->dbo->quote($encryption_key);
			$section_id=$this->dbos->dbo->quote($section_id);
			$keywords=$this->dbos->dbo->quote($this->encryption->encrypt_string($keywords));
			$description=$this->dbos->dbo->quote($this->encryption->encrypt_string($description));
			$hash_password=$this->dbos->dbo->quote($this->encryption->encrypt_string("1111111"));
			
			$sql="INSERT INTO sub_groups(group_id,naim,description,keywords,encryption_key,hash_password) values($group_id,$name,$description,$keywords,$encryption_key,$hash_password)";
			$this->dbos->dbo->exec($sql);
		}
	}
	
	
	////hедактирование подгруппы
	
	function edit_sub_group($sub_group_id,$name,$description,$keywords,$section_password="",$private_password="") {
		////генерация ключа секции
		$encryption_key=$this->encryption->generate_key(time());
		
		////добавление этой секции в базу данных
		if($this->dbos->state=="connected") {
			
			////ищем к какой группе оно относится
			$sql="SELECT group_id from sub_groups  where id=$sub_group_id";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$group_id=$row[0];
			}
		
			
			/////подготовка класса шифрования
			$sql="SELECT encryption_key from sections where id in (select section_id from groups where id=$group_id)";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$encryption_key_section=$row[0];
			}
			
			$sql="SELECT encryption_key from groups where id=$group_id";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$encryption_key_group=$row[0];
			}
			
			///echo "encryption_password=$private_password<br>";
			
			$this->encryption->encryption_key_section=$encryption_key_section;  /// надо получить ключ секции
			$this->encryption->encryption_key_section_password=$section_password;
			$this->encryption->encryption_key_group=$encryption_key_group; ////это группа, не надо тут никаких ключей
			$this->encryption->encryption_key_private=$private_password;
			
			$this->encryption->prepare_encryption_key();
			
			/////шифруем метаданные, навзвание и описание
			
			
			$name=$this->dbos->dbo->quote($this->encryption->encrypt_string($name));
			$encryption_key=$this->dbos->dbo->quote($encryption_key);
			$section_id=$this->dbos->dbo->quote($section_id);
			$keywords=$this->dbos->dbo->quote($this->encryption->encrypt_string($keywords));
			$description=$this->dbos->dbo->quote($this->encryption->encrypt_string($description));
			$hash_password=$this->dbos->dbo->quote($this->encryption->encrypt_string("1111111"));
			
			$sql="UPDATE sub_groups set naim=$name,description=$description,keywords=$keywords where id=$sub_group_id";
			$this->dbos->dbo->exec($sql);
		}
	}
	
	
	////////////удаление подгруппы
	function delete_sub_group($sub_group_id,$section_password="",$private_password="") {
		////генерация ключа секции
		$encryption_key=$this->encryption->generate_key(time());
		
		////добавление этой секции в базу данных
		if($this->dbos->state=="connected") {
			
			////ищем к какой группе оно относится
			$sql="SELECT group_id from sub_groups  where id=$sub_group_id";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$group_id=$row[0];
			}
		
			
			/////подготовка класса шифрования
			$sql="SELECT encryption_key from sections where id in (select section_id from groups where id=$group_id)";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$encryption_key_section=$row[0];
			}
			
			$sql="SELECT encryption_key from groups where id=$group_id";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$encryption_key_group=$row[0];
			}
			
			///echo "encryption_password=$private_password<br>";
			
			$this->encryption->encryption_key_section=$encryption_key_section;  /// надо получить ключ секции
			$this->encryption->encryption_key_section_password=$section_password;
			$this->encryption->encryption_key_group=$encryption_key_group; ////это группа, не надо тут никаких ключей
			$this->encryption->encryption_key_private=$private_password;
			
			$this->encryption->prepare_encryption_key();
			
			/////шифруем метаданные, навзвание и описание
			
			
			$sql="SELECT id,naim,description,keywords,hash_password from sub_groups where group_id=$group_id";
				foreach ($this->dbos->dbo->query($sql) as $row){
				
					$hash_str=$this->encryption->decrypt_string($row[4])*1;
					
					if($hash_str==1111111) {
						
						if($row[0]==$sub_group_id) {
							
							////удаление файлов
							
							$documents=get_list_of_documents($sub_group_id,$section_password,$private_password);
							foreach($documents as $doc) {
								$this->delete_document($doc->id,$section_password,$private_password);
							}
							
						
							
							$sql="delete from sub_groups where id=$sub_group_id";
							$this->dbos->dbo->exec($sql);
						}
					
					}
			}
		}
	}
	
	////////////список подгрупп////////////////////////
	function get_list_of_sub_groups($group_id,$section_password="",$private_password="") {
		
		if($this->dbos->state=="connected") {
			
			
			$sql="SELECT encryption_key from sections where id in (select section_id from groups where id=$group_id)";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$encryption_key_section=$row[0];
			}
			
			$sql="SELECT encryption_key from groups where id=$group_id";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$encryption_key_group=$row[0];
			}
			
			$this->encryption->encryption_key_section=$encryption_key_section;  /// надо получить ключ секции
			$this->encryption->encryption_key_section_password=$section_password;
			$this->encryption->encryption_key_group=$encryption_key_group; ////это группа, не надо тут никаких ключей
			$this->encryption->encryption_key_private=$private_password;
			$this->encryption->encryption_key_sub_group="";
			
			$this->encryption->prepare_encryption_key();
			
			///сначала все, что доступно без пароля
			$sql="SELECT id,naim,description,keywords,hash_password from sub_groups where group_id=$group_id";
			
				foreach ($this->dbos->dbo->query($sql) as $row){
				
					$hash_str=$this->encryption->decrypt_string($row[4])*1;
					if($hash_str==1111111) {
						unset($group_info);
						$group_info->id=$row[0];
						$group_info->name=$this->encryption->decrypt_string($row[1]);
						$group_info->description=$this->encryption->decrypt_string($row[2]);
						$group_info->keywords=$this->encryption->decrypt_string($row[3]);
						$group_info->group_id=$group_id;
						$founded_sub_groups[]=$group_info;
						
					}
			}
			
			
			return $founded_sub_groups;
		}
	}
	
	
	////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////самое главное//////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////////////////
	
	////////////////работа с документами/////////////////////////
	////////////////создание документа///////////////////////////
	function get_document_types() {
		if($this->dbos->state=="connected") {
			
			$sql="SELECT id,naim from document_types";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$id=$row[0];
					$naim=$row[1];
					$document_types[$id]=$naim;
			}
			
			return $document_types;
		}
	}
	
	function get_list_of_documents($sub_group,$section_password="",$private_password="") {
		
		if($this->dbos->state=="connected") {
				$sql="SELECT group_id,encryption_key from sub_groups where id=$sub_group";
					foreach ($this->dbos->dbo->query($sql) as $row){
						$group_id=$row[0];
						$encryption_key_sub_group=$row[1];
				}
			
				$sql="SELECT encryption_key from sections where id in (select section_id from groups where id=$group_id)";
					foreach ($this->dbos->dbo->query($sql) as $row){
						$encryption_key_section=$row[0];
				}
				
				$sql="SELECT encryption_key from groups where id=$group_id";
					foreach ($this->dbos->dbo->query($sql) as $row){
						$encryption_key_group=$row[0];
				}
				
				
				$this->encryption->encryption_key_section=$encryption_key_section; 
				$this->encryption->encryption_key_section_password=$section_password;
				$this->encryption->encryption_key_group=$encryption_key_group; 
				$this->encryption->encryption_key_sub_group=$encryption_key_sub_group;
				$this->encryption->encryption_key_private=$private_password;
				
				$this->encryption->prepare_encryption_key();
			
				unset($list_of_documents);
			
				////получаем список доступных документов
				$sql="SELECT id,naim,type,description,logo,author,rate,data_add,last_modified,last_access from documents where sub_group=$sub_group";
					foreach ($this->dbos->dbo->query($sql) as $row){
						$id=$row[0];
						$naim=$row[1];
						$type=$row[2];
						$description=$row[3];
						$logo=$row[4];
						$author=$row[5];
						$rate=$row[6];
						$data_add=$row[7];
						$last_modified=$row[8];
						$last_access=$row[9];
				
				
				$document_type=$this->encryption->decrypt_string($type)*1;
				if($document_type>0) {
					
					///временная дешифровка логотипа
					if($logo!="") {
						$destination_logo="temp/".time()."_".basename($logo);
						$this->encryption->decrypt_file($logo,$destination_logo,$id);
					}
					
					$document_info=json_decode("{}");
					$document_info->id=$id;
					$document_info->name=$this->encryption->decrypt_string($naim);
					$document_info->type=$this->encryption->decrypt_string($type);
					$document_info->description=$this->encryption->decrypt_string($description);
					$document_info->logo=$destination_logo;
					$document_info->author=$this->encryption->decrypt_string($author);
					$document_info->rate=$this->encryption->decrypt_string($rate);
					$document_info->data_add=$data_add;
					$document_info->last_modified=$last_modified;
					$document_info->last_access=$last_access;
					
					$list_of_documents[]=$document_info;
				}
			}
			
				return $list_of_documents;
		
		}
	}
	
	function create_document($sub_group,$name,$type,$description="",$logo="",$author="",$section_password="",$private_password="") {
		
		if($this->dbos->state=="connected") {
			$sql="SELECT group_id,encryption_key from sub_groups where id=$sub_group";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$group_id=$row[0];
					$encryption_key_sub_group=$row[1];
			}
		
		
			$sql="SELECT encryption_key from sections where id in (select section_id from groups where id=$group_id)";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$encryption_key_section=$row[0];
			}
			
			$sql="SELECT encryption_key from groups where id=$group_id";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$encryption_key_group=$row[0];
			}
			
			$this->encryption->encryption_key_section=$encryption_key_section; 
			$this->encryption->encryption_key_section_password=$section_password;
			$this->encryption->encryption_key_group=$encryption_key_group; 
			$this->encryption->encryption_key_sub_group=$encryption_key_sub_group;
			$this->encryption->encryption_key_private=$private_password;
			
			$this->encryption->prepare_encryption_key();
		
			$name=$this->dbos->dbo->quote($this->encryption->encrypt_string($name));
			$type=$this->dbos->dbo->quote($this->encryption->encrypt_string($type));
			$description=$this->dbos->dbo->quote($this->encryption->encrypt_string($description));
			$author=$this->dbos->dbo->quote($this->encryption->encrypt_string($author));
			
			$sql="INSERT INTO documents(naim,type,description,author,logo,rate,data_add,last_modified,last_access,sub_group) values($name,$type,$description,$author,'','','".time()."','','','$sub_group')";
			$this->dbos->dbo->exec($sql);
			
			///получаем ид
			$inserted_id=$this->dbos->dbo->lastInsertId();
			
			////создаем подпапку
			$document_dir="files/document_".$inserted_id;
			mkdir($document_dir);
			
			if($logo!="") {
				////указали лого, щифруем файл
				$destination_logo=$document_dir."/".russain_clear(basename($logo));
				$this->encryption->encrypt_file($logo,$destination_logo);
				$destination_logo=$this->dbos->dbo->quote($destination_logo);
				
				///обновление обложки
				$sql="UPDATE documents set logo=$destination_logo where id='$inserted_id'";
				$this->dbos->dbo->exec($sql);
			}
			
			return $inserted_id;
		}
	}
	
	function view_document($document_id,$section_password="",$private_password="") {
		
		if($this->dbos->state=="connected") {
			
			$sql="SELECT sub_group from documents where id=$document_id";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$sub_group=$row[0];
			}
			
			if($sub_group>0) {
			
			$sql="SELECT group_id,encryption_key from sub_groups where id=$sub_group";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$group_id=$row[0];
					$encryption_key_sub_group=$row[1];
			}
		
		
			$sql="SELECT encryption_key from sections where id in (select section_id from groups where id=$group_id)";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$encryption_key_section=$row[0];
			}
			
			$sql="SELECT encryption_key from groups where id=$group_id";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$encryption_key_group=$row[0];
			}
			
			$this->encryption->encryption_key_section=$encryption_key_section; 
			$this->encryption->encryption_key_section_password=$section_password;
			$this->encryption->encryption_key_group=$encryption_key_group; 
			$this->encryption->encryption_key_sub_group=$encryption_key_sub_group;
			$this->encryption->encryption_key_private=$private_password;
			
			$this->encryption->prepare_encryption_key();
			
			$sql="SELECT id,naim,type,description,logo,author,rate,data_add,last_modified,last_access from documents where id=$document_id";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$id=$row[0];
					$naim=$row[1];
					$type=$row[2];
					$description=$row[3];
					$logo=$row[4];
					$author=$row[5];
					$rate=$row[6];
					$data_add=$row[7];
					$last_modified=$row[8];
					$last_access=$row[9];
			}
			
			$document_type=$this->encryption->decrypt_string($type)*1;
			if($document_type>0) {
				
				///временная дешифровка логотипа
				if($logo!="") {
					$destination_logo="temp/".time()."_".basename($logo);
					$this->encryption->decrypt_file($logo,$destination_logo,$id);
				}
				
				$document_info=json_decode("{}");
				$document_info->id=$id;
				$document_info->name=$this->encryption->decrypt_string($naim);
				$document_info->type=$this->encryption->decrypt_string($type);
				$document_info->description=$this->encryption->decrypt_string($description);
				$document_info->logo=$destination_logo;
				$document_info->author=$this->encryption->decrypt_string($author);
				$document_info->rate=$this->encryption->decrypt_string($rate);
				$document_info->data_add=$data_add;
				$document_info->last_modified=$last_modified;
				$document_info->last_access=$last_access;
				
				////получаем список файлов-приложений
				$document_info->attachments=$this->get_list_of_attachments($id);
				
				return $document_info;
			}
			
			} else return "not_found";
		}
	}
	
	
	function edit_document($document_id,$sub_group,$name,$type,$description="",$logo="",$author="",$section_password="",$private_password="") {
		
		
		if($this->dbos->state=="connected") {
			$sql="SELECT group_id,encryption_key from sub_groups where id=$sub_group";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$group_id=$row[0];
					$encryption_key_sub_group=$row[1];
			}
		
		
			$sql="SELECT encryption_key from sections where id in (select section_id from groups where id=$group_id)";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$encryption_key_section=$row[0];
			}
			
			$sql="SELECT encryption_key from groups where id=$group_id";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$encryption_key_group=$row[0];
			}
			
			$this->encryption->encryption_key_section=$encryption_key_section; 
			$this->encryption->encryption_key_section_password=$section_password;
			$this->encryption->encryption_key_group=$encryption_key_group; 
			$this->encryption->encryption_key_sub_group=$encryption_key_sub_group;
			$this->encryption->encryption_key_private=$private_password;
			
			$this->encryption->prepare_encryption_key();
		
			$name=$this->dbos->dbo->quote($this->encryption->encrypt_string($name));
			$type=$this->dbos->dbo->quote($this->encryption->encrypt_string($type));
			$description=$this->dbos->dbo->quote($this->encryption->encrypt_string($description));
			$author=$this->dbos->dbo->quote($this->encryption->encrypt_string($author));
			
			//$sql="INSERT INTO documents(naim,type,description,author,logo,rate,data_add,last_modified,last_access,sub_group) values($name,$type,$description,$author,'','','".time()."','','','$sub_group')";
			
			$sql="UPDATE documents set naim=$name,type=$type,description=$description,author=$author,sub_group='$sub_group' where id='$document_id'";
			
			///echo $sql;
			
			$this->dbos->dbo->exec($sql);
			
			////создаем подпапку
			$document_dir="files/document_".$document_id;
			mkdir($document_dir);
			
			if($logo!="") {
				////указали лого, щифруем файл
				$destination_logo=$document_dir."/".russain_clear(basename($logo));
				$this->encryption->encrypt_file($logo,$destination_logo);
				$destination_logo=$this->dbos->dbo->quote($destination_logo);
				
				///обновление обложки
				$sql="UPDATE documents set logo=$destination_logo where id='$document_id'";
				$this->dbos->dbo->exec($sql);
			}
			
			return $inserted_id;
		}
	}
	
	
	function delete_document($document_id,$section_password="",$private_password="") {
		
		
		if($this->dbos->state=="connected") {
			
			$sql="SELECT sub_group from documents where id=$document_id";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$sub_group=$row[0];
			}
			
			if($sub_group>0) {
			$sql="SELECT group_id,encryption_key from sub_groups where id=$sub_group";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$group_id=$row[0];
					$encryption_key_sub_group=$row[1];
			}
		
		
			$sql="SELECT encryption_key from sections where id in (select section_id from groups where id=$group_id)";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$encryption_key_section=$row[0];
			}
			
			$sql="SELECT encryption_key from groups where id=$group_id";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$encryption_key_group=$row[0];
			}
			
			$this->encryption->encryption_key_section=$encryption_key_section; 
			$this->encryption->encryption_key_section_password=$section_password;
			$this->encryption->encryption_key_group=$encryption_key_group; 
			$this->encryption->encryption_key_sub_group=$encryption_key_sub_group;
			$this->encryption->encryption_key_private=$private_password;
			
			$this->encryption->prepare_encryption_key();
			
			$sql="SELECT id,naim,type,description,logo,author,rate,data_add,last_modified,last_access from documents where id=$document_id";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$id=$row[0];
					$naim=$row[1];
					$type=$row[2];
					$description=$row[3];
					$logo=$row[4];
					$author=$row[5];
					$rate=$row[6];
					$data_add=$row[7];
					$last_modified=$row[8];
					$last_access=$row[9];
			}
			
			echo "document_type $document_type";
		
			
			$document_type=$this->encryption->decrypt_string($type)*1;
			if($document_type>0) {
				
				////можно удалять
				$attachments=$this->get_list_of_attachments($id,$section_password,$private_password);
				foreach($attachments as $attachment) {
					$this->delete_attachment($attachment->id,$section_password,$private_password);
					
				}
				
				///удаляем собственно документ
				$sql="DELETE from documents  where id='$document_id'";
				$this->dbos->dbo->exec($sql);
			}
			
		}
		} else return "not_found";
	}
	
	
	function add_attachment($document_id,$name,$content,$comment="",$section_password="",$private_password="") {
		
		if($this->dbos->state=="connected") {
			
			$sql="SELECT sub_group,type from documents where id=$document_id";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$sub_group=$row[0];
					$type=$row[1];
			}
			
			
			$sql="SELECT group_id,encryption_key from sub_groups where id=$sub_group";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$group_id=$row[0];
					$encryption_key_sub_group=$row[1];
			}
		
		
			$sql="SELECT encryption_key from sections where id in (select section_id from groups where id=$group_id)";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$encryption_key_section=$row[0];
			}
			
			$sql="SELECT encryption_key from groups where id=$group_id";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$encryption_key_group=$row[0];
			}
			
			$this->encryption->encryption_key_section=$encryption_key_section; 
			$this->encryption->encryption_key_section_password=$section_password;
			$this->encryption->encryption_key_group=$encryption_key_group; 
			$this->encryption->encryption_key_sub_group=$encryption_key_sub_group;
			$this->encryption->encryption_key_private=$private_password;
			
			$this->encryption->prepare_encryption_key();
			
			$name=$this->dbos->dbo->quote($this->encryption->encrypt_string($name));
			$comment=$this->dbos->dbo->quote($this->encryption->encrypt_string($comment));
			
			$type=$this->encryption->decrypt_string($type)*1;
			
			if($type=="1" or $type=="2" or $type=="5" or $type=="6" or $type=="7" or $type=="8") {
				////это файлы
				////если это лог - создаем файл сами, дописываем туда
				
				
				if($type=="8") {
					
					$document_dir="files/document_".$document_id;
					$destination=$document_dir."/".russain_clear("log_01.log");
					$destination_temp="temp/log_001.log";
					
					////поиск уже существующего лога (первого)
					$attachment_id=0;
					$sql="SELECT id from document_attachments where document_id=$document_id";
							foreach ($this->dbos->dbo->query($sql) as $row){
								$attachment_id=$row[0];
						}
						
					if($attachment_id>0) {
						
						//файл уже существует, расшифруем его
						$this->encryption->decrypt_file($destination,$destination_temp);
						file_put_contents($destination_temp,$content,FILE_APPEND);
						///echo "content_of_file=".file_get_contents($destination_temp);
						$this->encryption->encrypt_file($destination_temp,$destination);
						
						$sql="UPDATE document_attachments set last_add='".time()."' where id=$attachment_id";
						$this->dbos->dbo->exec($sql);
						
					} else {
						
						///такого документа не существует, надо создать
						
						
						///добавляем данные в файл
						file_put_contents($destination_temp,$content."\r\n",FILE_APPEND);
						
						$this->encryption->encrypt_file($destination_temp,$destination);
						$destination=$this->dbos->dbo->quote($this->encryption->encrypt_string($destination));
					
					
						$sql="INSERT INTO document_attachments(document_id,naim,content,comment,last_add) values($document_id,$name,$destination,$comment,'".time()."')";
						$this->dbos->dbo->exec($sql);
						
					}
					
					
				} else {
					
					/////это обычный файл
				
					
					$document_dir="files/document_".$document_id;
					$destination=$document_dir."/".russain_clear(basename($content));
					$this->encryption->encrypt_file($content,$destination);
					
					
					$destination=$this->dbos->dbo->quote($this->encryption->encrypt_string($destination));
					
					
					$sql="INSERT INTO document_attachments(document_id,naim,content,comment,last_add) values($document_id,$name,$destination,$comment,'".time()."')";
					$this->dbos->dbo->exec($sql);
					
				}
				
			} else {
				////это простая текстовая информация
				$content=$this->dbos->dbo->quote($this->encryption->encrypt_string($content));
			
				$sql="INSERT INTO document_attachments(document_id,naim,content,comment,last_add) values($document_id,$name,$content,$comment,'".time()."')";
				$this->dbos->dbo->exec($sql);
				
			}
			
		}
		
	}
	
	
	function get_list_of_attachments($document_id,$section_password="",$private_password="") {
		
		if($this->dbos->state=="connected") {
			
			$sql="SELECT sub_group from documents where id=$document_id";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$sub_group=$row[0];
			}
			
			$sql="SELECT group_id,encryption_key from sub_groups where id=$sub_group";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$group_id=$row[0];
					$encryption_key_sub_group=$row[1];
			}
		
		
			$sql="SELECT encryption_key from sections where id in (select section_id from groups where id=$group_id)";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$encryption_key_section=$row[0];
			}
			
			$sql="SELECT encryption_key from groups where id=$group_id";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$encryption_key_group=$row[0];
			}
			
			$this->encryption->encryption_key_section=$encryption_key_section; 
			$this->encryption->encryption_key_section_password=$section_password;
			$this->encryption->encryption_key_group=$encryption_key_group; 
			$this->encryption->encryption_key_sub_group=$encryption_key_sub_group;
			$this->encryption->encryption_key_private=$private_password;
			
			$this->encryption->prepare_encryption_key();
			
			unset($attachments);
			
			$sql="SELECT id,naim,content,comment,last_add from document_attachments where document_id=$document_id";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$id=$row[0];
					$naim=$row[1];
					$content=$row[2];
					$comment=$row[3];
					$last_add=$row[4];
					
				$document_attachment_info=json_decode("{}");
				$document_attachment_info->id=$id;
				$document_attachment_info->name=$this->encryption->decrypt_string($naim);
				$document_attachment_info->content=$this->encryption->decrypt_string($content);
				$document_attachment_info->comment=$this->encryption->decrypt_string($comment);
				$document_attachment_info->last_add=$last_add;
				
				$attachments[]=$document_attachment_info;
			}
			
			return $attachments;
			
		}
		
		
	}
	
	function view_attachment($attachment_id,$section_password="",$private_password="") {
		
		if($this->dbos->state=="connected") {
			
			$sql="SELECT sub_group,type from documents where id in (SELECT document_id from document_attachments where id=$attachment_id)";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$sub_group=$row[0];
					$type=$row[1];
			}
			
			$sql="SELECT group_id,encryption_key from sub_groups where id=$sub_group";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$group_id=$row[0];
					$encryption_key_sub_group=$row[1];
			}
		
		
			$sql="SELECT encryption_key from sections where id in (select section_id from groups where id=$group_id)";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$encryption_key_section=$row[0];
			}
			
			$sql="SELECT encryption_key from groups where id=$group_id";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$encryption_key_group=$row[0];
			}
			
			$this->encryption->encryption_key_section=$encryption_key_section; 
			$this->encryption->encryption_key_section_password=$section_password;
			$this->encryption->encryption_key_group=$encryption_key_group; 
			$this->encryption->encryption_key_sub_group=$encryption_key_sub_group;
			$this->encryption->encryption_key_private=$private_password;
			
			$this->encryption->prepare_encryption_key();
			
			$type=$this->encryption->decrypt_string($type)*1;
		
		
			////открываем этот файл
			
			$sql="SELECT id,naim,content,comment,last_add from document_attachments where id=$attachment_id";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$id=$row[0];
					$naim=$row[1];
					$content=$row[2];
					$comment=$row[3];
					$last_add=$row[4];
					
				$document_attachment_info=json_decode("{}");
				$document_attachment_info->id=$id;
				$document_attachment_info->name=$this->encryption->decrypt_string($naim);
				$document_attachment_info->comment=$this->encryption->decrypt_string($comment);
				
				$document_attachment_info->last_add=$last_add;
				
				
				////если это какой то файл
				if($type=="1" or $type=="2" or $type=="5" or $type=="6" or $type=="7" or $type=="8") {
					
					$content=$this->encryption->decrypt_string($content);
					
					
					if($content!="") {
						$content=trim($content);
						$destination_content="temp/".time()."_".basename($content);
						
						//if(file_exists($_SERVER["DOCUMENT_ROOT"]."/".$content)) {
						//	echo "file_exists!";
						//} else echo "file not exists!";
						
						////echo "content of file=".file_get_contents($_SERVER["DOCUMENT_ROOT"]."/".$content);
						
						$this->encryption->decrypt_file($_SERVER["DOCUMENT_ROOT"]."/".$content,$_SERVER["DOCUMENT_ROOT"]."/".$destination_content);
						
						$document_attachment_info->content=$destination_content;
					}
					
				} else {
				
					$document_attachment_info->content=$this->encryption->decrypt_string($content);
				
				}
				
				
			}
		
			return $document_attachment_info;
		
		}
	}
	
	
	function delete_attachment($attachment_id,$section_password="",$private_password="") {
		if($this->dbos->state=="connected") {
			
			////придется сначала найти его файл
			$sql="SELECT sub_group,type from documents where id in (SELECT document_id from document_attachments where id=$attachment_id)";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$sub_group=$row[0];
					$type=$row[1];
			}
			
			$sql="SELECT group_id,encryption_key from sub_groups where id=$sub_group";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$group_id=$row[0];
					$encryption_key_sub_group=$row[1];
			}
		
		
			$sql="SELECT encryption_key from sections where id in (select section_id from groups where id=$group_id)";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$encryption_key_section=$row[0];
			}
			
			$sql="SELECT encryption_key from groups where id=$group_id";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$encryption_key_group=$row[0];
			}
			
			$this->encryption->encryption_key_section=$encryption_key_section; 
			$this->encryption->encryption_key_section_password=$section_password;
			$this->encryption->encryption_key_group=$encryption_key_group; 
			$this->encryption->encryption_key_sub_group=$encryption_key_sub_group;
			$this->encryption->encryption_key_private=$private_password;
			
			$this->encryption->prepare_encryption_key();
			
			$sql="SELECT id,naim,content,comment,last_add from document_attachments where id=$attachment_id";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$id=$row[0];
					$naim=$row[1];
					$content=$row[2];
					$comment=$row[3];
					$last_add=$row[4];
				}
			
			$content=$this->encryption->decrypt_string($content);
			
			$shred = new Shred\Shred(3);
			
			if(file_exists($_SERVER["DOCUMENT_ROOT"]."/".$content)) {
				
				////удаление файла
				$shred->shred($_SERVER["DOCUMENT_ROOT"]."/".$content);
				
			}
			
			
			$sql="DELETE from document_attachments where id=$attachment_id";
			$this->dbos->dbo->exec($sql);
		}
	}
	
	function get_attachment_size($attachment_id,$section_password="",$private_password="") {
		
		if($this->dbos->state=="connected") {
			
			$sql="SELECT sub_group,type from documents where id in (SELECT document_id from document_attachments where id=$attachment_id)";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$sub_group=$row[0];
					$type=$row[1];
			}
			
			$sql="SELECT group_id,encryption_key from sub_groups where id=$sub_group";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$group_id=$row[0];
					$encryption_key_sub_group=$row[1];
			}
		
		
			$sql="SELECT encryption_key from sections where id in (select section_id from groups where id=$group_id)";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$encryption_key_section=$row[0];
			}
			
			$sql="SELECT encryption_key from groups where id=$group_id";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$encryption_key_group=$row[0];
			}
			
			$this->encryption->encryption_key_section=$encryption_key_section; 
			$this->encryption->encryption_key_section_password=$section_password;
			$this->encryption->encryption_key_group=$encryption_key_group; 
			$this->encryption->encryption_key_sub_group=$encryption_key_sub_group;
			$this->encryption->encryption_key_private=$private_password;
			
			$this->encryption->prepare_encryption_key();
			
			$sql="SELECT id,naim,content,comment,last_add from document_attachments where id=$attachment_id";
				foreach ($this->dbos->dbo->query($sql) as $row){
					$id=$row[0];
					$naim=$row[1];
					$content=$row[2];
					$comment=$row[3];
					$last_add=$row[4];
				}
			
			$content=$this->encryption->decrypt_string($content);
			
			if(file_exists($_SERVER["DOCUMENT_ROOT"]."/".$content)) {
				
				return filesize($_SERVER["DOCUMENT_ROOT"]."/".$content);
				
			}
				
		}
	}
	
	function get_document_size($document_id,$section_password="",$private_password="") {
		
		$total_size=0;
		$attachments=$this->get_list_of_attachments($document_id,$section_password,$private_password);
		foreach($attachments as $attachment) {
			
			$size=$this->get_attachment_size($attachment->id)*1;
			$total_size=$total_size+$size;
			
		}
		
		return $total_size;
		
	}
	
	function get_sub_group_size($sub_group,$section_password="",$private_password="") {
		
		$total_size=0;
		$documents=$this->get_list_of_documents($sub_group,$section_password,$private_password);
		
		foreach($documents as $document) {
			
			$size=$this->get_document_size($document->id)*1;
			$total_size=$total_size+$size;
		}
		
		return $total_size;
	}
	
	function get_group_size($group_id,$section_password="",$private_password="") {
		
		$total_size=0;
		$sub_groups=$this->get_list_of_sub_groups($group_id,$section_password,$private_password);
		///var_dump($sub_groups);
		
		foreach($sub_groups as $sub_group) {
			
			$size=$this->get_sub_group_size($sub_group->id)*1;
			$total_size=$total_size+$size;
		}
		
		return $total_size;
	}
	
	function get_section_size($section_id,$section_password="",$private_password="") {
		
		$total_size=0;
		$groups=$this->get_list_of_groups($section_id,$section_password,$private_password);
		
		foreach($groups as $group) {
			
			$size=$this->get_group_size($group->id)*1;
			$total_size=$total_size+$size;
		}
		
		return $total_size;
	}
	
	function search($keyword,$section_id,$group_id="",$sub_group_id="",$section_password="",$private_password="") {
		/////поиск по подгруппе
		
		////$groups
		
		
	}
	
}
?>