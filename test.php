<?php
require("functions/classes.php");
require("functions/functions.php");
ini_set("display_errors","On");

////тестируем функцию шифрования файла
/*
$section_key="fbace8ecc7b19f24f17cb5ee900e335306b376cb7b98eb48f820ddff536d6b01";
$group_key="ac6b6a23272a105bcf95f2ea2fc29d2e5c244310add8ac79922b5c38ccb499ba";
$private_key="35f53cd12fb35b08322ecc1c2d34747e4c1ace9f68fae752392fa8479ddfc8b9";

$encryption=new encryption();

$encryption->encryption_key_section=$section_key;
$encryption->encryption_key_group=$group_key;
$encryption->encryption_key_private=$private_key;
$encryption->prepare_encryption_key();
*/
/*
$text_to_encrypt="Система шифрования работает!!!";

$encrypted_message=$encryption->encrypt_string($text_to_encrypt);
echo $encrypted_message;
echo "<br><br>";

$decrypted_message=$encryption->decrypt_string($encrypted_message);
echo $decrypted_message;
//echo $encrypadd_grouption->generate_key(time());
*/
/*
$fileName = __DIR__ . '/testfile.txt';
file_put_contents($fileName, 'File would be encrypted...');

$result = $encryption->encrypt_file($fileName, $fileName . '.enc');
if ($result) {
    echo "FILE ENCRYPTED TO " . $result;

    $result = $encryption->decrypt_file($result,$fileName . '.dec');
    if ($result) {
        echo "<BR>FILE DECRYPTED TO " . $result;
    }
}
*/

////шифруем среднюю книгу для теста
/*
$fileName="xakep.pdf";
$result = $encryption->encrypt_file($fileName, $fileName . '.enc');
if ($result) {
    echo "FILE ENCRYPTED TO " . $result;

$encryption->decrypt_file($result,$fileName . '.dec');

}
*/
$password="ier56f17";
$password=hash("sha512",$password);

$user_password="+K2rraz1k402";
$user_password=hash("sha512",$user_password);

echo "hashed_password=";
var_dump($user_password);


$fonds=new fonds();
///var_dump($fonds->get_list_of_sections($password));


//$fonds->add_section("Обычная секция","Стандартная обычная секция для широкого круга нужд");
//$fonds->add_section("Запретная секция","Особая секция, далеко не для всех",$password);

/////////////////////////////////////////////////////////
///создание группы в обычной секции
echo "<pre>";
////$fonds->add_sub_group(12,"Книги моего детства из библиотеки","","электроника из библиотеки","","");
///var_dump($fonds->get_list_of_sub_groups("12"));


/////правим подгруппу
//$fonds->edit_sub_group(15,"Журналы по радиоэлектронике","","старая электроника","","");
//$fonds->delete_sub_group(16,"","");

/////////////////////////////////////////////////////////
///создание группы в запретной секции

///$fonds->add_group(2,"Электроника","Электроника - первые технические книги","электроника",$password,"");
///var_dump($fonds->get_list_of_groups("2",$password));

////редактирование группы

//$fonds->edit_group(2,"Электроника","Электроника - первые технические книги. первые детские книги","электроника");
//var_dump($fonds->get_list_of_groups("1"));

////создание личной книжной полки
//$fonds->add_group(1,"Очень личная книжная полка","Очень личная книжная полка","личное","",$user_password);
//echo "<pre>";
//var_dump($fonds->get_list_of_groups("1","",$user_password));



//////////////////////создание документа//////////////////////////
////var_dump($fonds->view_document(6));


///создаем несколько документов различных видов
///$fonds->create_document(13,"Тестовая книга 1","1","Это просто какая-то тествоая книга","","");
///$fonds->create_document(13,"Тестовая заметка","3","Это просто какая-то тестовая заметка","","");
////$fonds->create_document(13,"Тестовый лог с метеостанции","8","Это просто Тестовый лог с метеостанции","","");

///var_dump($fonds->get_list_of_documents(13));

////var_dump($fonds->view_document(7));
///var_dump($fonds->view_document(8));
var_dump($fonds->view_document(9));

////добавляем новую книгу (сам файл книги)
///$fonds->add_attachment(7,"Основной текст книги","temp/lie-behind-the-lie-detector.pdf","Тестовый текст книги");
//var_dump($fonds->view_attachment(9));

////добавляем новую заметку (сам текст заметки)
///$fonds->add_attachment(8,"Основной текст аметки","Много-много текста для заметки и прочего","Тестовый текст заметки");
///var_dump($fonds->view_attachment(12));


////добавляем новый лог (пример с метеостанции)
///$fonds->add_attachment(9,"Основной лог с метеостанции","{'some_jsondfdsfdsfd'}","Лог погодной метеостанции");


////получение размера файла
///var_dump($fonds->get_attachment_size(10));

////получение размера документа//////////////////////////
//var_dump($fonds->get_document_size(7));
//var_dump($fonds->get_sub_group_size(13));
//var_dump($fonds->get_group_size(12));
///var_dump($fonds->get_section_size(1));

$fonds->delete_document(7)

//////////поиск


?>

