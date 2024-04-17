<?php
session_start();
require "lib/functions.php";

if (isset($_POST["login"])) {
    if (!isset($_POST["username"]) || !isset($_POST["password"])) {
        header("location: index.php?loginerror");
        die();
    }
    $result = local_login($_POST["username"], $_POST["password"]);
    if ($result == -1) {
        header("location: index.php?loginerror");
        die();
    }
    $_SESSION["user_id"] = $result;
    header("location: index.php");
    die();
}

if (isset($_POST["register"])) {
    if (!isset($_POST["username"]) || !isset($_POST["password"])) {
        header("location: index.php?regerror=1&register");
        die();
    }
    $result = local_register($_POST["username"], $_POST["password"], $_POST["email"]);
    if ($result == -1) {
        header("location: index.php?regerror=1&register");
    } else {
        header("location: index.php?regok");
    }
    die();
}

function local_login($username, $password) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        return -1;
    }
    $ar = $result->fetch_assoc();
    $passwordhash = hash('sha256',$ar["salt"].hash('sha256',$password));
    if ($passwordhash == $ar["passwordhash"]) {
        $_SESSION["isAdmin"] = $ar["is_admin"];
        return $ar["id"];
    } else {
        return -1;
    }
}

function local_register($username, $password, $email) {
    global $conn;
    //Check if user already exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    if(!$stmt->execute()) return -1;
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        return -1;
    }
    $salt = substr(md5(uniqid(rand(), TRUE)), 0,5);
    $passwordhash = hash('sha256',$salt.hash('sha256',$password));
    $stmt = $conn->prepare("INSERT INTO users (username, passwordhash, email, salt) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $passwordhash, $email, $salt);
    $stmt->execute();
    return 1;
}
