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
require('inc.php');
$loggedIn = '';

htmlHeader('Forgotten password', 'newLogin');
$message = 'Enter your email address and click "Reset". You password will be random- ized and emailed to you. Please remember to change it and keep it safe.';
$email = '';
if ($_POST) {
    $email = trim($_POST['email']);
    $stmt = $session->db->prepare('SELECT id FROM users WHERE email = ? AND suspended = 0');
    $stmt->bind_param('s', $email);
    if (!$stmt->execute()) dLog("fail - SELECT id FROM users WHERE email = $email AND suspended = 0");
    $stmt->store_result();
    $stmt->bind_result($id);
    if (!$stmt->fetch())
        $message = "Email \"$email\" is not in this database. Please double check your entry.";
    else
    {
        $message = "A randomised password has been sent to \"$email\". Please check your email to log in.";
        $letters = str_split('abcdefghijklmnopqrstuvwxyz');
        $password = '';
        foreach (array_rand($letters, 6) as $k) $password .= $letters[$k] . rand(0,9);
            $stmt = $session->db->prepare('UPDATE users SET `password` = PASSWORD(?) WHERE id = ?');
            $stmt->bind_param('si', $password, $id);
            if (!$stmt->execute()) dLog("fail - UPDATE users SET `password` WHERE users.id = $id");
        sendMail(array($email), 'Your TWC Battle password has been reset', "Your password has been reset on the TWC Battle site.\n\nEmail: $email\nPassword: $password\nSite: $site\n\n".
            "Feel free to change it.\n\nPlease report this email at once if you did not request a new password.");
    }
}
?>
        <img class="Logo" src="/images/logo.png">
        <div class="title-block">
            <h1><img src="/images/the-wargaming-club.png" alt=""></h1>
            <h2><img src="/images/office-of-reord.png" alt=""></h2>
        </div>
        <div class="form-block ForgotPassword">
            <div class="form-title">
                <div class="TopMargin"></div>
                <h3>Reset Password</h1>
            </div>
            <div class="form-text">
                <span class="Instructions"><?= $message?></span>
            </div>
            <form method="post">
                <div class="box">
                    <p><img src="/images/email.png" alt=""></p>
                    <input type="text" class="form-control" name="email"  onkeyup="document.querySelector('input[type=\'submit\']').disabled = !this.value;">
                </div>
                <div class="btns">
                    <input type="submit" value="Reset" class="sbt" disabled> 
                    <input type="button" value="Close" class="sbt" onclick="document.location='/';">
                </div>
                
            </form>
            <div class="form-footer">
            </div>
        </div>
<?  htmlFooter() ?>
