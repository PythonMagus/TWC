<?
require "inc.php";
$loggedIn = array_key_exists('userId', $_SESSION) ? $_SESSION['userId'] : '';
if (!$loggedIn) {
    header('Location: /');
    $_SESSION['thence'] = $_SERVER['REQUEST_URI'];
    exit;
}
$id = array_key_exists('id', $_GET) ? $_GET['id'] : '';
$started = date('Y-m-d');
if ($id) {
    $stmt = $session->db->prepare('SELECT b.name, typeid, gt.name, started, ended, state FROM battles b LEFT JOIN gametypes gt ON b.typeid = gt.id WHERE b.id = ?');
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) dLog("fail - SELECT b.name, typeid, gt.name, started, ended, state FROM battles b LEFT JOIN gametypes gt ON b.typeid = gt.id WHERE b.id =  $id");
    $stmt->store_result();
    $stmt->bind_result($name, $typeid, $type, $started, $ended, $state);
    if (!$type) $type = "";
    if (!$stmt->fetch())
    {
        dLog("Failed to find battle $id");
        header('Location: /');
        exit;
    }
    if ($_POST && $state != 2) {
        $name = $_POST['name'];
        $state = $_POST['state'];
        $started = $_POST['started'];
        $typeid = $_POST['typeid'];
        $stmt = $session->db->prepare('UPDATE battles SET name = ?, state = ?, started = ?, typeid = ? WHERE id = ?');
        $stmt->bind_param('sisii', $name, $state, $started, $typeid, $id);
        if (!$stmt->execute()) dLog("fail - UPDATE battles SET name = '$name', state = $state WHERE id = $id");
        dLog("Battle #$id ($name) is updated as $state and started $started");
    }
} else {
    $name = 'New Battle';
    $state = 0;
    $typeid = $type = "";
    if ($_POST && $state != 2) {
        $name = $_POST['name'];
        $typeid = $_POST['typeid'];
        $state = $_POST['state'];
        $stmt = $session->db->prepare('INSERT INTO battles (name, started, state, typeid) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('ssii', $name, $started, $state, $typeid);
        if (!$stmt->execute()) dLog("fail - INSERT INTO battles (name, started, state, typeid) VALUES ('$name', '$started', $state, $typeid)");
        $id = $stmt->insert_id;
        dLog("Battle #$id ($name) is created as $state and started $started");
    }
}
if ($_POST && $typeid > 0)
{
    $stmt = $session->db->prepare('SELECT name FROM gametypes WHERE id = ?');
    $stmt->bind_param('i', $typeid);
    if (!$stmt->execute()) dLog("fail - SELECT name FROM gametypes WHERE id = $typeid");
    $stmt->store_result();
    $stmt->bind_result($type);
    $stmt->fetch();
}
$users = getAllUsers();

$players = array();
if ($id) {
    $stmt = $session->db->prepare('SELECT userId FROM userbattles WHERE battleId = ?');
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) dlog("fail - SELECT u.id FROM userbattles WHERE battleId = $id");
    $stmt->store_result();
    $stmt->bind_result($userId);
    while ($stmt->fetch()) 
        array_push($players, $userId);
}
if ($_POST && $state != 2)
{
    $updatedPlayers = json_decode($_POST['players']);
    dLog("Battle $name ($id) [state=$state] players [" . join(',', $players) . "] => [" . join(',',$updatedPlayers) . "]");
    $oldPlayers = array();
    $newPlayers = array();
    foreach ($players as $playerId)
        if (!in_array($playerId, $updatedPlayers))
            array_push($oldPlayers, $playerId);
    foreach ($updatedPlayers as $playerId)
        if (!in_array($playerId, $players))
            array_push($newPlayers, $playerId);
    $players = $updatedPlayers;
    if (sizeof($oldPlayers)) {
        $stmt = $session->db->prepare('DELETE FROM userbattles WHERE battleId = ? AND userId IN (' . join(',', $oldPlayers) . ')');
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) dlog("fail - DELETE FROM userbattles WHERE battleId = $id AND userId IN (" . join(',', $oldPlayers) . ")");
    }
    foreach ($newPlayers as $playerId) {
        $stmt = $session->db->prepare('INSERT INTO userbattles (battleId, userId, result, points) VALUES (?, ?, 0, 0)');
        $stmt->bind_param('ii', $id, $playerId);
        if (!$stmt->execute()) dlog("fail - INSERT INTO userbattles (battleId, userId, result, points) VALUES ($id, $playerId, 0 0)");   
    }
}
htmlHeader($name == 'New Battle' ? $name : "Edit Battle - $type $name");
?>
    <script>
        var players = <?= json_encode($players) ?>;
        var users = <?= json_encode($users); ?>;
        var readOnly = <?= $state == 2 ? 'true' : 'false'; ?>;
        loadBattleEditor();
    </script>
    <form method="post" action="registerBattle.php?id=<?= $id ?>">
        <input type="hidden" name="players">
        <div class="BattleEditor">
            <h2>Battle Player Editor</h2>
            <table>
                <tr><th>Type:</th><td><select name="typeid"><option value="0" <?= $typeid == 0 ? "selected" : "" ?>>General</option>
<?
    $stmt = $session->db->prepare('SELECT id, type, name FROM gametypes ORDER BY name');
    if (!$stmt->execute()) dLog("fail - SELECT id, type, name FROM gametypes ORDER BY name");
    $stmt->store_result();
    $stmt->bind_result($gtid, $gttype, $gtname);
    while($stmt->fetch()) {
?>
                                <option value="<?= $gtid ?>" <?= $gtid == $typeid ? "selected" : "" ?>><?= $gtname ?></option>
<?
    }
?>
                    </select></td></tr>
                <tr><th>Name and number:</th><td><input name="name" placeholder="Name and number for this battle" value="<?= $name ?>"></td></tr>
                <tr><th>Starts:</th><td><input name="started" value="<?= $started ?>"></td></tr>
                <tr><th>State:</th><td><select name="state" <?= $_SESSION['admin'] ? '' : 'disabled' ?>>
<?
    foreach ($battleStates as $key => $value) {
?>
                                <option value="<?= $key ?>" <?= $key == $state ? "selected" : "" ?>><?= $value ?></option>
<?
    }
?>

                </td></tr>
                <tr class="Players"><th>Players:</th><td><ul class="Players"></ul></td></tr>
                <tr>
                    <td>
                        <input class="Filter" placeholder="Filter">
                        <select id="addPlayer" <?= $state == 2 ? "disabled" : ""?>><option>Select player to add</option></select>
                    </td>
                    <td><select id="removePlayer" <?= $state == 2 ? "disabled" : ""?>><option>Select player to remove</option></select></td>
                </tr>
            </table>
            <div class="ButtonBar">
                <input type="submit" value="Save" disabled>
                <button class="Cancel" onclick="document.location='<?= $id ? "battle.php?id=$id" : "index.php" ?>';return false;"><?= $_POST ? 'Close' : 'Cancel' ?></a>
            </div>
        </div>
    </form>
<?  htmlFooter() ?>
