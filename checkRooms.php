<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 15/3/18
 * Time: 10:26 PM
 */


session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/confidential/connector.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/confidential/mysql_login.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/confidential/config.php';

$_POST = json_decode(file_get_contents('php://input'), true);

use \Firebase\JWT\JWT;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // The request is using the POST method
    if (isset($_POST['token']) || isset($_POST['jwt'])) {
        $jwt = $_POST['token'] || $_POST['jwt'];
        try{
            $decoded = JWT::decode($jwt, $jwtKey, array('HS256'));
            $data['message'] = "JWT Verified";
            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode($data);
            die();
        }catch (exception $e){
            $data['message'] = "JWT Verification Error";
            http_response_code(300);
            header('Content-Type: application/json');
            echo json_encode($data);
            die();
        }

    } else {
        $data['message'] = "Incomplete Data";
        http_response_code(406);
        header('Content-Type: application/json');
        echo json_encode($data);
        die();
    }
} else {
    $data['message'] = "Invalid HTTP Method";
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode($data);
    die();
}