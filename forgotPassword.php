<?
require('inc.php');

htmlHeader('Forgotten password');
$message = 'Enter your email address and click "Reset". You password will be randomized and emailed to you. Please remember to change it and keep it safe.';
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
    <form method="post">
        <div class="ForgotPassword">
            <h2>Reset password</h2>
            <span class="Instructions"> <?= $message ?> </span>
            <div>
            <label for="email">Email:</label>
            <input id="email" name="email" value="<?= $email ?>" onkeyup="document.querySelector('input[type=\'submit\']').disabled = !this.value;">
            </div>
            <div class="ButtonBar">
                <input type="submit" value="Reset" disabled>
                <input type="button" value="Close" onclick="document.location='/';">
            </div>
        </div>
    </form>
<?  htmlFooter() ?>
