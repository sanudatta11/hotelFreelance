<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 15/3/18
 * Time: 9:38 PM
 */

session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/confidential/connector.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/confidential/mysql_login.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/confidential/config.php';

require_once('vendor/autoload.php');
use \Firebase\JWT\JWT;

$_POST = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // The request is using the POST method
    if (isset($_POST['email']) && isset($_POST['password'])) {
            $email = $_POST['email'];
            $password = $_POST['password'];

            $sql_query_string = "SELECT * FROM ".$dbname.".login WHERE email = :bindemail LIMIT 1";
            $find_query = $mysql_conn->prepare($sql_query_string);
            $find_query->bindParam(':bindemail', $email, PDO::PARAM_INT);
            $find_query->execute();
            $find_query->setFetchMode(PDO::FETCH_ASSOC);
            while ($row = $find_query->fetch()) {
                if ($row['password'] == $password) { //Add SHA1 Verification if needed

                    $token = array(
                        "iat" => 1356999524,
                        "nbf" => 1357000000,
                        "userId" => $row['id']
                    );

                    $jwtToken = JWT::encode($token, $jwtKey);

                    $data['message'] = "Login Successfull";
                    $data['token'] = $jwtToken;
                    http_response_code(200);
                    header('Content-Type: application/json');
                    echo json_encode($data);
                    die();

                } else {
                    $data['message'] = "Invalid Password Provided!";
                    http_response_code(401);
                    header('Content-Type: application/json');
                    echo json_encode($data);
                    die();
                }
            }
            $data['message'] = "Invalid Email Provided!";
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode($data);
            die();
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