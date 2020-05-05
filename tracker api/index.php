<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 15-Jan-19
 * Time: 12:31 PM
 */
include_once 'magic_quotes.inc.php';
include_once 'login_state.php';
$response = array('login' => false, 'userInfo'=>'', 'result' => '', 'actionType' =>'');
//include 'db.inc.php';
if (!userIsLoggedIn()) {
    $response['login'] = false;
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
} else {
    $response['login'] = true;
    retrieveUser();
    if (isset($_POST['action']) && $_POST['action'] == "SendMessage") {
        $theMessage = $_POST['message'];
        $theClient = $_POST['recipient'];
        sendMessage($theMessage, $theClient);
    }

    if (isset($_POST['action']) && $_POST['action'] == "SaveLocation") {
        //$response['login'] = true;
        $longitude = $_POST['longitude'];
        $latitude = $_POST['latitude'];
        if (isset($_POST['longitude']) && isset($_POST['latitude']) && $_POST['longitude'] != "" && $_POST['latitude'] != "") {
            saveLocation($longitude, $latitude);
        }

    }

    if(isset($_POST['action']) && $_POST['action'] == "RetrieveAllUsers"){
        retreiveAllUsers();
    }

    if (isset($_POST['action']) && $_POST['action'] == "RetrieveLocation") {

        //$response['login'] = true;
        $c_email = $_POST['client_email'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];

        if ((isset($_POST['client_email']) && isset($_POST['start_date']) && isset($_POST['end_date'])) or ($_POST['client_email'] != "" && $_POST['start_date'] != "" && $_POST['end_date'] != "")) {
            retrieveLocation($c_email, $start_date, $end_date);
        }
    }

    if (isset($_POST['action']) && $_POST['action'] == "UpdateStatus") {
        //$response['login'] = true;
        updateStatus();
    }

    if (isset($_POST['action']) && $_POST['action'] == "RetrieveUserMe") {
        //$response['login'] = true;
        retrieveUser();
    }
    header('Content-Type: application/json');
    echo json_encode($response);
}

function sendMessage($message, $client)
{
    global $response;
    $sql = "";
    if ($_SESSION['usertype'] == "admin") {
        //$sql = "INSERT INTO adminmessage SET message = :message, a_email =  $_SESSION[email], c_email = $client, message_date = curdate()";


        $sql = "INSERT INTO adminmessage( message, a_email, c_email, message_date) VALUES(:message, '$_SESSION[email]', '$client',curdate())";
    }
    if ($_SESSION['usertype'] == "client") {
        $sql = "INSERT INTO clientmessage (message, c_email, message_date) VALUES (:message, '$_SESSION[email]', CURRENT_DATE )";
    }
    try {
        include 'db.inc.php';
        //global $pdo;
        $quarry = $pdo->prepare($sql);
        $quarry->bindValue(":message", $message);
        $result = $quarry->execute();

       // header('Content-Type: application/json');
        $response['actionType'] = 'SendMessage';
        $response['result'] = $result;
        //echo json_encode($response);


    } catch (PDOException $e) {
        $response['result'] = $e;
       // header('Content-Type: application/json');
        //echo json_encode($response);
    }

}

function saveLocation($longitude, $latitude)
{
    global $response;

    if ($_SESSION['usertype'] == "client") {
        include 'db.inc.php';
        $sql = "INSERT INTO clientlocation (c_email, longitude, latitude, logdate) VALUE ('$_SESSION[email]', :longitude, :latitude, curdate() )";
        $querry = $pdo->prepare($sql);
        $querry->bindValue(":longitude", $longitude);
        $querry->bindValue(":latitude", $latitude);
        $result = $querry->execute();

        //header('Content-Type: application/json');
        $response['actionType'] = 'SaveLocation';

        $response['result'] = $result;

        //echo json_encode($response);
    }

}

function retrieveLocation($c_email, $start_date, $end_date)
{
    global $response;

    include 'db.inc.php';
    $sql = "SELECT longitude, latitude FROM clientlocation WHERE c_email = '$c_email' AND (logdate BETWEEN  cast('$start_date' as datetime)  AND cast('$end_date' as datetime))";

    $querry = $pdo->query($sql);
    $resp = array();

    $s = $querry->fetchAll(PDO::FETCH_ASSOC);

    $response['actionType'] = 'RetrieveLocation';

    $response['result'] = $s;

    //header('Content-Type: application/json');
    //echo json_encode($response);
    /*foreach ($querry as $row) {
        $resp[] = $row;
    }
    echo json_encode($resp, false);
    return json_encode($resp);*/
}

function updateStatus($c_email, $state)
{
    global $response;

    $sql = "UPDATE client SET status = :state WHERE c_email = :email";
    include 'db.inc.php';
    $querry = $pdo->prepare($sql);
    $querry->bindValue(":c_email", $c_email);
    $querry->bindValue(":state", $state);
    $result = $querry->execute();

    //Check Quarry Status

    $response['actionType'] = 'UpdateStatus';
    $response['result'] = $result;

    //header('Content-Type: application/json');
    //echo json_encode($response);
}

function retreiveAllUsers(){
    global $response;

    if($_SESSION['usertype'] == "admin"){
        $sql = "SELECT email, phone_no, firstname, lastname, username FROM client WHERE a_email = '$_SESSION[email]'";
        include 'db.inc.php';

        $querry = $pdo->query($sql);

        $s = $querry->fetchAll(PDO::FETCH_ASSOC);

        $response['actionType'] = 'RetrieveAllUsers';

        $response['result'] = $s;
    }
}

function addUser($c_email, $password, $firstname, $lastname, $phoneNo)
{
    global $response;

    $sql = "INSERT INTO client SET c_email=:c_email, c_password =:password, firstname = :firstname, lastname =:lastname, phone_no =:phone_no, a_email='$_SESSION[email]'";

    include 'db.inc.php';
    $querry = $pdo->prepare($sql);
    $querry->bindValue(":c_email", $c_email);
    $querry->bindValue(":password", $password);
    $querry->bindValue(":firstname", $firstname);
    $querry->bindValue(":lastname", $lastname);
    $querry->bindValue(":phone_no", $phoneNo);
    $result = $querry->execute();

    $response['actionType'] = 'addUser';
    $response['result'] = $result;
    //header('Content-Type: application/json');
    //echo json_encode($response);
}

function retrieveUser()
{
    global $response;
    if ($_SESSION['usertype'] == 'client') {
        $sql = "SELECT email, firstname, lastname, phone_no, a_email, username, a_id FROM client WHERE email=:a_email";
        //header('Content-Type: application/json');
        //echo json_encode($response);
    }else{
        $sql = "SELECT email, firstname, lastname, username FROM admin WHERE email=:a_email";
    }
    include 'db.inc.php';
    $querry = $pdo->prepare($sql);
    $querry->bindValue(":a_email", $_SESSION['email']);
    $querry->execute();
    $result = $querry->fetchAll(PDO::FETCH_ASSOC);

    $response['actionType'] = 'RetrieveUserMe';
    $response['userInfo'] = $result;
}

?>