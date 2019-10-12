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
$user = null;
if ($loggedIn) {
    $user = getUserDetails($loggedIn);
    htmlHeader("Status for " . $_SESSION['alias'], 'new');
    setLeftBlock($navigationTabs);
    outputUserDetails($loggedIn);
} else { /* logged in */
    htmlHeader("Login page", 'newLogin');
?>
        <img class="Logo" src="/images/logo.png">
        <div class="title-block">
            <h1><img src="/images/the-wargaming-club.png" alt=""></h1>
            <h2><img src="/images/office-of-reord.png" alt=""></h2>
        </div>
        <div class="form-block">
            <div class="form-text">
                <p><img src="/images/register-battle.png" alt=""></p>
            </div>
            <div class="form-title">
                <h3><img src="/images/sign-in.png" alt=""></h3>
            </div>
            <form method="post">
                <div class="box">
                    <p><img src="/images/email.png" alt=""></p>
                    <input type="text" class="form-control" name="email">
                </div>
                <div class="box">
                    <p><img src="/images/password.png" alt=""></p>
                    <input type="password" class="form-control" name="password">
                </div>
                <div class="box">
    <? if ($errorMsg) {?>
                    <span class="ErrorMsg"><?=$errorMsg?></span>
    <? } ?>
                </div>
                <div class="btns">
                    <input type="submit" value="login" class="sbt">
                </div>
                
            </form>
            <div class="form-footer">
                <a href="forgotPassword.php"><img src="images/password-reset.png" alt=""></a>
            </div>
        </div>

<? } 
htmlFooter() ?>


