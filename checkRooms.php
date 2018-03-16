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

require_once('vendor/autoload.php');
use \Firebase\JWT\JWT;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // The request is using the POST method
    if (isset($_POST['token'])) {
        $jwt = $_POST['token'];

        try {
            $decoded = JWT::decode($jwt, $jwtKey, array('HS256'));

            if (isset($_POST['dateEntry']) && isset($_POST['dateExit'])) {
                try {
                    $sql_query_string = "SELECT * FROM ".$dbname.".bookings WHERE ((dateEntry BETWEEN :bindEntry1 AND :bindExit1) OR (dateExit BETWEEN :bindEntry2 AND :bindExit2))";
                    $find_query = $mysql_conn->prepare($sql_query_string);
                    $find_query->bindParam(':bindEntry1', $_POST['dateEntry']);
                    $find_query->bindParam(':bindExit1', $_POST['dateExit']);
                    $find_query->bindParam(':bindEntry2', $_POST['dateEntry']);
                    $find_query->bindParam(':bindExit2', $_POST['dateExit']);
                    $find_query->execute();
                    $find_query->setFetchMode(PDO::FETCH_ASSOC);

                    $takenRooms = array();
                    array_push($takenRooms,0);
                    if($find_query->rowCount() > 0)
                    {
                        $rows = $find_query->fetchAll();
                        foreach ($rows as $row) {
                            array_push($takenRooms, $row['roomId']);
                        }
                    }
                    $data['takenRooms'] = $takenRooms;

                    $qIds = str_repeat('?,', count($takenRooms) - 1) . '?';

                    $sql_query_string = "SELECT * FROM " . $dbname . ".rooms WHERE id NOT IN ($qIds)";
                    $find_query = $mysql_conn->prepare($sql_query_string);
                    $find_query->setFetchMode(PDO::FETCH_ASSOC);
                    $find_query->execute($takenRooms);

                    if($find_query->rowCount() > 0)
                    {
                        //Get Category Data
                        $sql_query_string = "SELECT * FROM " . $dbname . ".roomsCategories";
                        $cat_query = $mysql_conn->prepare($sql_query_string);
                        $cat_query->setFetchMode(PDO::FETCH_ASSOC);
                        $cat_query->execute();
                        if($cat_query->rowCount() > 0)
                        {
                            $categoryMap = array();
                            $categories = $cat_query->fetchAll();
                            foreach ($categories as $category) {
                                $categoryMap[(int)$category['id']] = $category['price'];
                            }

                            $roomsData = $find_query->fetchAll();
                            $finalAvailableRoomsData = array();
                            foreach ($roomsData as $roomData){
                                $tempObject = unserialize(serialize($roomData));
                                $tempObject['price'] = $categoryMap[(int)$roomData['categoryId']];
                                array_push($finalAvailableRoomsData,$tempObject);
                            }
                            $data['message'] = "Rooms Detail Found";
                            $data['data'] = $finalAvailableRoomsData;
                            http_response_code(200);
                            header('Content-Type: application/json');
                            echo json_encode($data);
                            die();
                        }
                        else
                        {
                            $data['message'] = "Category Find Error!";
                            http_response_code(502);
                            header('Content-Type: application/json');
                            echo json_encode($data);
                            die();
                        }
                    }
                    else{
                        $data['message'] = "No Rooms Available";
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
            }
            else {
                $data['message'] = "Dates Incomplete";
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