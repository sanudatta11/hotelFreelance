<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 16/3/18
 * Time: 12:51 PM
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
            if (isset($_POST['dateEntry']) && isset($_POST['dateExit'])) {
                try {
                    $sql_query_string = "SELECT * FROM " . $dbname . ".bookings WHERE ((dateEntry BETWEEN :bindEntry1 AND :bindExit1) OR (dateExit BETWEEN :bindEntry2 AND :bindExit2))";
                    $find_query = $mysql_conn->prepare($sql_query_string);
                    $find_query->bindParam(':bindEntry1', $_POST['dateEntry']);
                    $find_query->bindParam(':bindExit1', $_POST['dateExit']);
                    $find_query->bindParam(':bindEntry2', $_POST['dateEntry']);
                    $find_query->bindParam(':bindExit2', $_POST['dateExit']);
                    $find_query->execute();

                    $find_query->setFetchMode(PDO::FETCH_ASSOC);

                    if($find_query->rowCount()>0)
                    {
                        $data['message'] = "Details Populated";
                        $data['data'] = $find_query->fetchAll();
                        http_response_code(200);
                        header('Content-Type: application/json');
                        echo json_encode($data);
                        die();
                    }else{
                        $data['message'] = "No Rooms Booked";
                        http_response_code(404);
                        header('Content-Type: application/json');
                        echo json_encode($data);
                        die();
                    }
                } catch (exception $e) {
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