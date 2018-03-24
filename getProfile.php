<?php
/**
 * Created by PhpStorm.
 * User: sanu
 * Date: 24/3/18
 * Time: 10:51 AM
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/confidential/connector.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/confidential/mysql_login.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/confidential/config.php';

$_POST = json_decode(file_get_contents('php://input'), true);

require_once('vendor/autoload.php');
use \Firebase\JWT\JWT;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['token'])) {
        $jwt = $_POST['token'];
        try {
            $decoded = JWT::decode($jwt, $jwtKey, array('HS256'));
            if ($decoded) {
                $decoded_array = (array) $decoded;
                $loginId = $decoded_array['userId'];
                  //Get Details of profile
                $sql_query_string = "SELECT * FROM " . $dbname . ".profile A," . $dbname . ".login B WHERE B.id = :lid AND A.email = B.email LIMIT 1";
                $find_query = $mysql_conn->prepare($sql_query_string);
                $find_query->bindParam(':lid', $loginId);
                $find_query->execute();
                if ($find_query->rowCount() > 0) {
                    $find_query->setFetchMode(PDO::FETCH_ASSOC);
                    $data['profile'] = $find_query->fetch();
                    $data['message'] = "Profile Data generated";
                    http_response_code(200);
                    header('Content-Type: application/json');
                    echo json_encode($data);
                    die();
                } else {
                    //No Fucking Data
                    $data['message'] = "Invalid Data returned from Query";
                    http_response_code(306);
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
        } catch (exception $e) {
            $data['message'] = "JWT Verification Error";
            http_response_code(301);
            header('Content-Type: application/json');
            echo json_encode($data);
            die();
        }
    } else {
        $data['message'] = "Invalid Token";
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