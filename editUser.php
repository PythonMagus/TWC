<?
require "inc.php";
$loggedIn = array_key_exists('userId', $_SESSION) ? $_SESSION['userId'] : '';
$id = array_key_exists('id', $_GET) ? $_GET['id'] :'';
if (!$loggedIn || !($_SESSION['admin'] || $id == $loggedIn))    // may edit self
{
    header('Location: /');
    $_SESSION['thence'] = $_SERVER['REQUEST_URI'];
    exit;
}
$realName = $alias = $email = $created = '';
$rankType = 'military';
$points = $victories = $battles = $suspended = 0;
$user = array('points' => 0, 'battles' => 0, 'victories' => 0, 'setVictories' => 0, 'setBattles' => 0, 'setPoints' => 0);
$message = $_SESSION['admin'] ? "Enter the details for the general. You may give them points/battles/victories from past matches" :
    "Edit your details or change your password. (Please do not use an important password e.g. your bank. Just your pet's name will do.)";
if ($_POST) {
    $realName = $_POST['realName'];
    $alias = $_POST['alias'];
    $email = $_POST['email'];
    $created = $_POST['created'];
    $rankType = $_POST['rankType'];
    $report = "alias=$alias,email=$email,created=$created,rankType=$rankType,id=$id";
    if ($id) {
        $report = 'User details updated: ' . $report;
        $user = getUserDetails($id);
        $stmt = $session->db->prepare('UPDATE users SET email = ?, alias = ?, created = ?, rankType = ?, realName = ? WHERE id = ?');
        $stmt->bind_param('sssssi', $email, $alias, $created, $rankType, $realName, $id);
        if (!$stmt->execute()) dLog("fail - UPDATE users WHERE users.id = $id");
        if ($password = $_POST['password'])
        {
            $stmt = $session->db->prepare('UPDATE users SET `password` = PASSWORD(?) WHERE id = ?');
            $stmt->bind_param('si', $password, $id);
            if (!$stmt->execute()) dLog("fail - UPDATE users SET `password` WHERE users.id = $id");
        }
        $message = "General $alias's details updated";
    } else {
        $report = 'User created: ' . $report;
        $stmt = $session->db->prepare('INSERT INTO users (email, alias, created, rankType, password, realName) VALUE (?,?,?,?, PASSWORD(?), ?)');
        $stmt->bind_param('ssssss', $email, $alias, $created, $rankType, $password, $realName);
        if (!$stmt->execute()) dLog("fail - INSERT INTO users ");
        $id = $stmt->insert_id;
        $message = "General $alias created";
    }
    if ($_SESSION['admin'])
    {
        $suspended = array_key_exists('suspended', $_POST) ? 1 : 0;
        $stmt = $session->db->prepare('UPDATE users SET points = ?, battles = ?, victories = ?, suspended = ?  WHERE id = ?');
        $points = $_POST['points'];
        $victories = $_POST['victories'];
        $battles = $_POST['battles'];
        $setPoints =  $points - $user['points'] + $user['setPoints'];
        $setBattles = $battles - $user['battles'] + $user['setBattles'];
        $setVictories = $victories - $user['victories'] + $user['setVictories'];
        $report .= ",points=$points,battles=$battles,victories=$victories,suspended=$suspended";
        $stmt->bind_param('iiiii', $setPoints, $setBattles, $setVictories, $suspended, $id); 
        if (!$stmt->execute()) dLog("fail - UPDATE users SET points = $setPoints, battles = $setBattles, victories = $setVictories, suspended = $suspended  WHERE id = $id");
    }
    dLog($report);
} elseif ($id) {
    $stmt = $session->db->prepare('SELECT email, alias, created, realName, suspended FROM users WHERE users.id = ?');
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) dLog("fail - SELECT email, alias, created, realName, suspended FROM users WHERE users.id = $id");
    $stmt->store_result();
    if (!$stmt->num_rows) {
        dLog("Failed to find user $id for editing");
        $id = '';
    } else {
        $stmt->bind_result($email,$alias, $created, $realName, $suspended);
        $stmt->fetch();
        $user = getUserDetails($id);
        $points = $user['points'];
        $victories = $user['victories'];
        $battles = $user['battles'];
        $created = substr($created, 0, 10);
    }
}

htmlHeader($id ? 'Creating new general' : "Editing general $alias");
?>
    <script>
        var newUser = <?= $id ?'false' : 'true' ?>;
        loadPlayerEditor();
    </script>
    <form method="post" action="editUser.php?id=<?= $id ?>">
        <span class="Instructions"><?= $message ?></span>
        <div class="UserEditor">
            <h2>General Editor</h2>
            <table>
                <tr><th>Name:</th><td><input name="realName" placeholder="OPTIONAL - Real name of this general" value="<?= $realName ?>"></td></tr>
                <tr><th>Alias:</th><td><input name="alias" placeholder="Alias for this general" value="<?= $alias ?>"></td></tr>
                <tr><th>Email:</th><td><input name="email" placeholder="Email address" value="<?= $email ?>"></td></tr>
                <tr><th>Joined:</th><td><input name="created" value="<?= $created ?>"></td></tr>
                <tr><th>Password:</th><td><input type="password" name="password"></td></tr>
                <tr><th>Confirm:</th><td><input type="password" name="confirm"></td></tr>
                <tr><th>Rank:</th><td>
                    <select name="rankType">
                        <option value="military">Military</option>
                        <option value="civil" <?= $rankType == 'civil' ? 'selected': '' ?>>Civil</option>
                    </select>
                </td></tr>
<? if ($_SESSION['admin']) { ?>
                <tr><th>Suspended:</th><td><input name="suspended" type="checkbox" title="General may no longer log in" value="1" <?= $suspended ? " checked" : "" ?>></td></tr>
                <tr><th>Points:</th><td><input name="points" placeholder="Current score" value="<?= $points ?>"></td></tr>
                <tr><th>Battles:</th><td><input name="battles" placeholder="Current score" value="<?= $battles ?>"></td></tr>
                <tr><th>Victories:</th><td><input name="victories" placeholder="Current score" value="<?= $victories ?>"></td></tr>
                <tr><th>Ribbons:</th>
                    <td>
<?
    $stmt = $session->db->prepare('SELECT r.id, r.name, r.image, ur.userId FROM ribbons r LEFT JOIN userribbons ur ON r.id = ur.ribbonId AND ur.userId = ? WHERE family = \'club\'');
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) dLog("fail - SELECT r.id, r.name, r.image, ur.userId FROM ribbons r LEFT JOIN userribbons ur ON r.id = ur.ribbonId AND ur.userId = $id WHERE family = \'club\'");
    $stmt->store_result();
    $stmt->bind_result($ribbonId, $name, $image, $ticked);
    while ($stmt->fetch()) {
        $key = 'ribbon_' . $ribbonId;
        if ($_POST) {
            $keyValue = array_key_exists($key, $_POST) ? $_POST[$key] : '';
            if (boolval($keyValue) != boolval($ticked)) {
                if ($keyValue)
                    $stmt1 = $session->db->prepare('INSERT INTO userribbons (userId, ribbonId) VALUES (?,?)');
                else
                    $stmt1 = $session->db->prepare('DELETE FROM userribbons WHERE userId = ? AND ribbonId = ?');
                $stmt1->bind_param('ii', $id, $ribbonId);
                $stmt1->execute();
                $ticked = boolval($keyValue);
            }
        }
?>
                        <div class="RibbonWrapper">
                            <img src="/images/<?= $image ?>.png" title="<?= $name ?>" class="Ribbon"><br>
                            <input type="checkbox" name="<?= $key ?>" id="<?= $key ?>" <?= $ticked ? ' checked' : '' ?>>
                            <label for="<?= $key ?>"><?= $name ?></label>
                        </div>
<? } ?>
                    </td>
                </tr>
<? } ?>
            </table>
            <div class="ButtonBar">
                <input type="submit" value="Save" disabled>
                <button class="Cancel" onclick="document.location='<?= $id ? "user.php?id=$id" : "index.php" ?>';return false;"><?= $_POST ? 'Close' : 'Cancel' ?></a>
            </div>
        </div>
    </form>
<?  htmlFooter() ?>
