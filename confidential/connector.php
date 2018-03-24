<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 15/3/18
 * Time: 9:31 PM
 */

require_once $_SERVER['DOCUMENT_ROOT'].'/confidential/mysql_login.php';
try{
    $mysql_conn=new PDO("mysql:host=$host;port=3306;sdbname=$dbname",$username_db,$password_db);
    $mysql_conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $mysql_conn->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
    //echo "Connection established";
}
catch(PDOException $e){
    echo 'Connect Object Error:'.$e->getmessage();
}
?>