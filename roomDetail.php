<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 16/3/18
 * Time: 12:58 PM
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
            if (isset($_POST['roomId'])) {
                try {
                    $sql_query_string = "SELECT * FROM " . $dbname . ".rooms WHERE id = :rid LIMIT 1";
                    $find_query = $mysql_conn->prepare($sql_query_string);
                    $find_query->bindParam(':rid', $_POST['roomId'],PDO::PARAM_INT);
                    $find_query->execute();

                    $find_query->setFetchMode(PDO::FETCH_ASSOC);

                    if($find_query->rowCount()>0)
                    {
                        $data['message'] = "Details Populated";
                        $data['data'] = $find_query->fetch();
                        $sql_query_string = "SELECT * FROM " . $dbname . ".roomsCategories WHERE id = :cid LIMIT 1";
                        $find_query = $mysql_conn->prepare($sql_query_string);
                        $find_query->bindParam(':cid', $data['data']['categoryId'],PDO::PARAM_INT);
                        $find_query->execute();
                        $data['data']['price'] = $find_query->fetch()['price'];
                        $find_query->setFetchMode(PDO::FETCH_ASSOC);
                        http_response_code(200);
                        header('Content-Type: application/json');
                        echo json_encode($data);
                        die();
                    }else{
                        $data['message'] = "Room Find Error";
                        http_response_code(404);
                        header('Content-Type: application/json');
                        echo json_encode($data);
                        die();
                    }
                }
                catch (exception $e) {
                    $data['message'] = "PDO::ERROR";
                    $data['error'] = $e;
                    http_response_code(400);
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