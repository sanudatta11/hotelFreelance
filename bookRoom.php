<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 16/3/18
 * Time: 11:57 AM
 */
session_start();
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
            if (isset($_POST['dateEntry']) && isset($_POST['dateExit']) && isset($_POST['roomId']) && isset($_POST['customerName'])) {
                try {
                    $sql_query_string = "SELECT * FROM " . $dbname . ".bookings WHERE roomId = :rid AND ((dateEntry BETWEEN :bindEntry1 AND :bindExit1) OR (dateExit BETWEEN :bindEntry2 AND :bindExit2))";
                    $find_query = $mysql_conn->prepare($sql_query_string);
                    $find_query->bindParam(':rid', $_POST['roomId']);
                    $find_query->bindParam(':bindEntry1', $_POST['dateEntry']);
                    $find_query->bindParam(':bindExit1', $_POST['dateExit']);
                    $find_query->bindParam(':bindEntry2', $_POST['dateEntry']);
                    $find_query->bindParam(':bindExit2', $_POST['dateExit']);
                    $find_query->execute();

                    if ($find_query->rowCount() > 0) {
                        $data['message'] = "Invalid Room Selected";
                        http_response_code(409);
                        header('Content-Type: application/json');
                        echo json_encode($data);
                        die();
                    } else {
                        //Room is clear
                        $sql_query_string = "INSERT INTO " . $dbname . ".bookings (roomId,customerName,dateEntry,dateExit) VALUES(:rid,:cName,:bindEntry,:bindExit)";
                        $update_query = $mysql_conn->prepare($sql_query_string);
                        $update_query->bindParam(':rid', $_POST['roomId']);
                        $update_query->bindParam(':cName', $_POST['customerName']);
                        $update_query->bindParam(':bindEntry', $_POST['dateEntry']);
                        $update_query->bindParam(':bindExit', $_POST['dateExit']);
                        $update_query->execute();

                        $sql_query_string = "SELECT * FROM " . $dbname . ".rooms WHERE id = :rid LIMIT 1";
                        $find_query = $mysql_conn->prepare($sql_query_string);
                        $find_query->bindParam(':rid', $_POST['roomId']);
                        $find_query->execute();
                        $find_query->setFetchMode(PDO::FETCH_ASSOC);

                        $data['message'] = "Room Booked";
                        $data['roomDetails'] = $find_query->fetch();
                        http_response_code(200);
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