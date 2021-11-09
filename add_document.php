<?php
require("functions/classes.php");
require("functions/functions.php");
ini_set("display_errors","On");

$naim=@$_POST["naim"];
$document_type=@$_POST["document_type"];
$description=@$_POST["description"];
$author=@$_POST["author"];
$sub_group=@$_POST["sub_group"];

$password="ier56f17";
$password=hash("sha512",$password);

$user_password="+K2rraz1k402";
$user_password=hash("sha512",$user_password);

//echo "hashed_password=";
///var_dump($user_password);


$fonds=new fonds();
//////////////////////создание документа//////////////////////////

////если добавляется новый документ

if($naim!="") {
	
	var_dump($_FILES);
	
	if($_FILES['logo']['tmp_name']!="") {
						$uploaddir =  $_SERVER["DOCUMENT_ROOT"]."/temp/";
						$tms=time();
						$uploadfile1 = $uploaddir.russain_clear($tms."_".basename($_FILES['logo']['name']));
						
						if ((move_uploaded_file($_FILES['logo']['tmp_name'], $uploadfile1))) {
							$new_file="temp/".russain_clear($tms."_".basename($_FILES['logo']['name']));
							
							var_dump($new_file);
						}
						
					}
	///добавление документа
	//$fonds->create_document($sub_group,$naim,$document_type,$description,$new_file,$author);
	$fonds->edit_document(6,$sub_group,$naim,$document_type,$description,$new_file,$author);
}

?>

<form action="<?=$_SERVER["PHP_SELF"];?>" method='POST' id='add_new'  enctype='multipart/form-data'>
		
		<table>
		
			<tr><td>Подгруппа документа</td><td>
			
				<select name="sub_group">
				<?php
					
					$sub_groups=$fonds->get_list_of_sub_groups("12");
					foreach($sub_groups as $sub_group) {
						?>
						<option value="<?=$sub_group->id?>"><?=$sub_group->name?></option>
						<?
					}
				
				?>
				</select>
			
			</td>
			</tr>
			<tr><td>Название документа</td><td><input type="text" name="naim"></td></tr>
			<tr><td>Тип документа </td><td>
			
				<select name="document_type">
				<?php
					
					$types=$fonds->get_document_types();
					foreach($types as $type_id=>$name) {
						?>
						<option value="<?=$type_id?>"><?=$name?></option>
						<?
					}
				
				?>
				</select>
			
			</td></tr>
			<tr><td>Описание документа</td><td><textarea name="description"></textarea></td></tr>
			<tr><td>Автор документа</td><td><input type="text" name="author"></td></tr>
			<tr><td>Обложка документа</td><td><input type="file" name="logo"></td></tr>
		</table>
		<input type="submit" value="Отправить">
		
		</form>