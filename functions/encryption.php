<?php

/////класс шифрования

class encryption {

	public $encryption_key_section="";
	public $encryption_key_section_password="";
	
	public $encryption_key_group="";
	public $encryption_key_sub_group="";
	public $encryption_key_private="";
	
	private $encryption_key="";
	private $file_encryption_blocks=10000;
	private $time_to_delete=60;
	
	private $dbos;
	
	function __construct() {
		////создание класса доступа к БД
		$db=new connect_db();
		 if($db->state=="connected") {
		   $this->dbos=$db;  
		   
		 }
		 
		 $this->remove_temp_files();
		$this->remove_all_temp_files();
	}
	
	function remove_temp_files() {
		////поиск временных файлов и их удаление
		if($this->dbos->state=="connected") {
			
			$shred = new Shred\Shred(3);
			$sql="SELECT id,filename from decrypted_files where ".time()."-last_opened>".$this->time_to_delete;
				foreach ($this->dbos->dbo->query($sql) as $row){
					$id=$row[0];
					$filename=$row[1];
					
					if(file_exists($filename)) {
						$shred->shred($_SERVER["DOCUMENT_ROOT"]."/$filename");
					}
					
					$sql="DELETE from decrypted_files where id=$id";
					$this->dbos->dbo->exec($sql);
			}
			
		}
	}
	
	function add_temp_file($filename,$document_id) {
		$dest_to_db=$this->dbos->dbo->quote($filename);
		$attachment_id=$this->dbos->dbo->quote($attachment_id);
		$document_id=$this->dbos->dbo->quote($document_id);
		$sql="INSERT INTO decrypted_files(document_id,attachment_id,filename,last_opened) values($document_id,$attachment_id,$dest_to_db,'".time()."')";
		$this->dbos->dbo->exec($sql);
	}
	
	function remove_all_temp_files() {
		
		$dir=$_SERVER["DOCUMENT_ROOT"]."/temp/";
		$b = scandir($dir,1);
		$shred = new Shred\Shred(3);
		foreach($b as $bb) {
			if($bb!="." and $bb!="..") { 
			////по дате создания
				if(time()-filemtime($dir.$bb)>=$this->time_to_delete) {
					$shred->shred($dir.$bb);
				}
			}
		}
	}
	
	function prepare_encryption_key() {
		
		///первоначальный ключ
		$encryption_key=$this->encryption_key_section.$this->encryption_key_section_password.$this->encryption_key_group.$this->encryption_key_sub_group.$this->encryption_key_private;
		
		///растяжение ключа
		
		for($i=0;$i<=55201;$i++) {
			$encryption_key=hash("sha256",$encryption_key);
		}
		
		///ключ готов
		$this->encryption_key=$encryption_key;
		
	}
	
	function generate_key($string) {
		
		/////генерация ключа
		$generated_encryption_key=$string;
		
		for($i=0;$i<=55205;$i++) {
			$generated_encryption_key=hash("sha256",$generated_encryption_key);
		}
		
		return $generated_encryption_key;
	}
	
	
	/////////cобственнно шифрование и дешифрование
	function encrypt_string($data) {
		
		$key=$this->encryption_key;
		$l = strlen($key);
		
        if ($l < 16)
            $key = str_repeat($key, ceil(16/$l));

        if ($m = strlen($data)%8)
            $data .= str_repeat("\x00",  8 - $m);
        if (function_exists('mcrypt_encrypt'))
            $val = mcrypt_encrypt(MCRYPT_BLOWFISH, $key, $data, MCRYPT_MODE_ECB);
        else
            $val = openssl_encrypt($data, 'BF-ECB', $key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING);
		
		
        return base64_encode($val);
		
		
	}
	
	function decrypt_string($data) {
		
		$data=base64_decode($data);
				
		$key=$this->encryption_key;
		
        $l = strlen($key);
        if ($l < 16)
            $key = str_repeat($key, ceil(16/$l));

        if (function_exists('mcrypt_encrypt'))
            $val = mcrypt_decrypt(MCRYPT_BLOWFISH, $key, $data, MCRYPT_MODE_ECB);
        else
            $val = openssl_decrypt($data, 'BF-ECB', $key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING);
	
	
		$val=trim($val);
        return $val;
	}
	
	function encrypt_file($source, $dest) {
		
		$key=$this->encryption_key;
		
		$key = substr(sha1($key, true), 0, 16);
		$iv = openssl_random_pseudo_bytes(16);

		$error = false;
		if ($fpOut = fopen($dest, 'w')) {
			
			// Put the initialzation vector to the beginning of the file
			fwrite($fpOut, $iv);
			if ($fpIn = fopen($source, 'rb')) {
				while (!feof($fpIn)) {
					$plaintext = fread($fpIn, 16 * $this->file_encryption_blocks);
					$ciphertext = openssl_encrypt($plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
					// Use the first 16 bytes of the ciphertext as the next initialization vector
					$iv = substr($ciphertext, 0, 16);
					fwrite($fpOut, $ciphertext);
				}
				fclose($fpIn);
				
			}
			else {
				$error = true;
				
			}
			fclose($fpOut);
		}
		else {
			$error = true;
			
		}
		
		return $error ? null : $dest;
	}
	
	function decrypt_file($source, $dest,$document_id="",$attachment_id="") {
	
		$source=trim($source);
		$dest=trim($dest);
		
		$key=$this->encryption_key;
		
		$key = substr(sha1($key, true), 0, 16);
		
		$error = false;
		if ($fpOut = fopen($dest, 'w')) {
			if ($fpIn = fopen($source, 'rb')) {
				// Get the initialzation vector from the beginning of the file
				$iv = fread($fpIn, 16);
				while (!feof($fpIn)) {
					$ciphertext = fread($fpIn, 16 * ($this->file_encryption_blocks + 1)); // we have to read one block more for decrypting than for encrypting
					$plaintext = openssl_decrypt($ciphertext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
					// Use the first 16 bytes of the ciphertext as the next initialization vector
					$iv = substr($ciphertext, 0, 16);
					fwrite($fpOut, $plaintext);
				}
				fclose($fpIn);
			}
			else {
				$error = true;
				echo "source error";
			}
			fclose($fpOut);
		}
		else {
			$error = true;
			echo "destination error";
		}
		
		
		///заносим данные о расшифрованном файле
		$dest_to_db=$this->dbos->dbo->quote($dest);
		$document_id=$this->dbos->dbo->quote($document_id);
		$attachment_id=$this->dbos->dbo->quote($attachment_id);
		$sql="INSERT INTO decrypted_files(document_id,attachment_id,filename,last_opened) values($document_id,$attachment_id,$dest_to_db,'".time()."')";
		$this->dbos->dbo->exec($sql);

		return $error ? null : $dest;
	}
   
	function __destruct() {
	
	}

}
?>