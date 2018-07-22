<? 
/*
 * 
 *  This file is part of TWCOoR.
 * 
 *  TWCOoR is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License 
 *  as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 *  TWCOoR is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty 
 *  of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * 
 *  You should have received a copy of the GNU General Public License along with TWCOoR. 
 *  If not, see http://www.gnu.org/licenses/.
 * 
 */
require "inc.php"; 
$loggedIn = array_key_exists('userId', $_SESSION) ? $_SESSION['userId'] : '';

$errorMsg = "";
if ($_POST)
{
    $loggedIn = $_SESSION['userId'] = doLogin($_POST['email'],  $_POST['password']);
    if (!$loggedIn)
        $errorMsg = 'Invalid email or password';
    else if (array_key_exists('thence', $_SESSION))
    {
        header('Location: ' . $_SESSION['thence']);
        unset($_SESSION['thence']);
        exit;
    }
}
$user = array();
if ($loggedIn)
    $user = getUserDetails($loggedIn);

htmlHeader($loggedIn ? "Status for " . $_SESSION['alias'] : "Login page");
?>

<? if (!$loggedIn) {?>
        <div class="Instructions">
            Register your battles here, and view results and ribbons.
        </div>
        <form method="post">
            <div class="Login">
                <table>
                    <tr><th>Email:</th><td><input name="email"></td></tr>
                    <tr><th>Password:</th><td><input name="password" type="password"></td></tr>
<? if ($errorMsg) {?>
                    <tr><td colspan="2" class="ErrorMsg"><?=$errorMsg?></td></tr>
<? } ?>
                </table>
                <div class="ButtonBar">
                    <input type="submit" value="Login">
                </div>
                <div class="ForgotWrapper">
                    <a href="forgotPassword.php">Click to reset your password</a>
                </div>
            </div>
        </form>
<? } else { /* logged in */ ?>
        <div class="ButtonBar">
            <button onclick="document.location='logout.php';">Log out</button>
            <button onclick="document.location='editUser.php?id=<?= $_SESSION['userId'] ?>';">Change password</button>
            <button onclick="document.location='challenges.php';">My Challenges</button>
            <button onclick="document.location='battles.php';">All battles</button>
            <button onclick="document.location='users.php';">All generals</button>
            <button onclick="document.location='tournaments.php';">All tournaments</button>
            <button onclick="document.location='registerBattle.php';">Register battle</button>
        </div>
<?
    outputUserDetails($loggedIn);
?>

<? } 
htmlFooter() ?>


