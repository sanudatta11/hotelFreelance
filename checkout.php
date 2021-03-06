<?php
/**
 * Created by PhpStorm.
 * User: sanu
 * Date: 24/3/18
 * Time: 9:26 AM
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
            if (isset($_POST['dateExit']) && isset($_POST['roomId']) && isset($_POST['customerName']) && isset($_POST['bookingId']) && isset($_POST['checkOutDate'])) {
                try {
                    $sql_query_string = "SELECT * FROM " . $dbname . ".bookings WHERE id = :bid";
                    $find_query = $mysql_conn->prepare($sql_query_string);
                    $find_query->bindParam(':bid', $_POST['bookingId']);
                    $find_query->execute();


                    if ($find_query->rowCount() > 0) {
                        $find_query->setFetchMode(PDO::FETCH_ASSOC);
                        $bookingData = $find_query->fetch();

                        //Find room cost for this roomId
                        $sql_query_string = "SELECT * FROM " . $dbname . ".rooms A," . $dbname . ".roomsCategories B WHERE A.id = :rid AND A.categoryId = B.id";
                        $find_query = $mysql_conn->prepare($sql_query_string);
                        $find_query->bindParam(':rid', $_POST['roomId'], PDO::PARAM_INT);
                        $find_query->execute();

                        $find_query->setFetchMode(PDO::FETCH_ASSOC);
                        if ($find_query->rowCount() <= 0) {
                            $data['message'] = "Room Find Error";
                            http_response_code(404);
                            header('Content-Type: application/json');
                            echo json_encode($data);
                            die();
                        }
                        $roomPriceData = $find_query->fetch();
                        //Check if the time has elapsed for booking if yes, calculate cost. Or else give the basic cost.

                        $data['bookingDetail'] = $bookingData;
                        $data['roomData'] = $roomPriceData;
                        $dateDiff = (int)(strtotime($bookingData['dateExit']) - strtotime($bookingData['dateEntry']));
                        $data['price']  = (int)($dateDiff / (60 * 60 * 24)) * (int)$roomPriceData['price'];

                        if (strtotime($bookingData['dateExit']) < strtotime($_POST['checkOutDate'])) {
                            //Extra days fees must be included with base fees
                            $dateEntry = date_create($bookingData['dateExit']);
                            $dateExit = date_create($_POST['checkOutDate']);

                            $dateDiff = (int)(strtotime($_POST['checkOutDate']) - strtotime($bookingData['dateExit']));
                            $data['extraPrice'] = (int)$roomPriceData['price'] * (int)($dateDiff / (60 * 60 * 24));
                        }

                        //Add Details to Past Bookings
                        $sql_query_string = "INSERT INTO " . $dbname . ".pastBookings (roomId,customerName,dateEntry,dateExit,normalFee,extraFee) VALUES(:rid,:cName,:bindEntry,:bindExit,:nfee,:efee)";
                        $update_query = $mysql_conn->prepare($sql_query_string);
                        $update_query->bindParam(':rid', $_POST['roomId']);
                        $update_query->bindParam(':cName', $_POST['customerName']);
                        $update_query->bindParam(':bindEntry', $bookingData['dateEntry']);
                        $update_query->bindParam(':bindExit', $_POST['dateExit']);
                        $update_query->bindParam(':nfee', $data['price']);
                        $tempExtra = isset($data['extraPrice'])? $data['extraPrice'] : 0;
                        $update_query->bindParam(':efee', $tempExtra);
                        $update_query->execute();

                        //Remove the details from booking table and adding it to past booking
                        $sql_query_string = "DELETE FROM " . $dbname . ".bookings WHERE id = :bid";
                        $update_query = $mysql_conn->prepare($sql_query_string);
                        $update_query->bindParam(':bid', $bookingData['id']);
                        $update_query->execute();

                        $data['message'] = "Checkout Complete";
                        http_response_code(200);
                        header('Content-Type: application/json');
                        echo json_encode($data);
                        die();
                    } else {
                        $data['message'] = "Invalid Room Checkout";
                        http_response_code(405);
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