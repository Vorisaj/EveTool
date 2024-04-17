<?php
session_start();
$isSigned = isset($_SESSION["user_id"]) && $_SESSION["user_id"] >= 0;

//Redirect to dashboard if user is signed
if ($isSigned) {
    header("location: dashboard.php");
    die();
}

?>

<html>
    <head>
        <title>EveTool</title>
        <link rel="stylesheet" href="css/style.css">
    </head>

    <body>
        <header onclick="location.href='index.php'" class="layout-header" style="line-height: 1; color: white; text-align: center; padding: 0px;">
            <div style="padding: 12px; cursor: pointer;">
                <img style="margin-left: -165px;top: 8px;position: absolute;width: 48px;"  alt="Black Rose logo" src="img/logo.webp">
                <img style="margin-left: 120px;top: 8px;position: absolute;width: 48px;"  alt="Black Rose logo" src="img/logo.webp">
                <div style="font-size: 1.3rem; line-height: 1; margin-bottom: 5px;">Login</div>
                <div style="font-size: 0.7rem;">Login or Register</div>
            </div>
        </header>

    <div style='position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);'>
  <?php
    if (!isset($_GET["register"])) {
  ?>
        <h3>Please sign in</h3>
        <?php
            if (isset($_GET['loginerror'])) {
                echo '<span style="color:red">Invalid username or password!</span>';
            }
            if (isset($_GET['regok'])) {
                echo '<span style="color:green">Account created!</span>';
            }
        ?>
        <form method="POST" action="login_api.php">
        <table>
            <tr><td>Username:</td><td><input type="text" name="username" /></td></tr>
            <tr><td>Password:</td><td><input type="password" name="password" /></td></tr>
            <tr><td></td><td><input class="button" type="submit" name="login" value="login"/></td></tr>
            </table>
        </form>
        Does not have account? -> <a href="index.php?register">Register</a>

    <?php
        } else {
    ?>
        <h3>Please register</h3>
        <?php
            if (isset($_GET['regerror'])) {
                echo '<span style="color:red">Error during registration!</span>';
            }
        ?>
        <form method="POST" action="login_api.php">
        <table>
            <tr><td>Username:</td><td><input type="text" name="username" /></td></tr>
            <tr><td>Password:</td><td><input type="password" name="password" /></td></tr>
            <tr><td>Email:</td><td><input type="text" name="email" /></td></tr>
            <tr><td></td><td><input class="button"  type="submit" name="register" value="register"/></td></tr>
            </table>
        </form>
        Back to <a href="index.php">Sign in</a>
    <?php
        }
    ?>
    </div>
</body>
</html>