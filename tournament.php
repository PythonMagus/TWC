<?
require "inc.php";
$loggedIn = array_key_exists('userId', $_SESSION) ? $_SESSION['userId'] : '';
if (!$loggedIn)
{
    header('Location: /');
    $_SESSION['thence'] = $_SERVER['REQUEST_URI'];
    exit;
}
$id = array_key_exists('id', $_GET) ? $_GET['id'] : '';
$awards = array();
for ($i = 1; $i <= 3; $i++) $awards[$i] =  array('type' => $results[$i], 'url' => null, 'player' => null);
$awards[90] = array('type' => 'Participation', 'url' => null, 'player' => null);
if (!$id) {
    $state = $type = $gametypeid = 0;
    $name = 'New tournament';
    $started = date('Y-m-d');
    $ended = '';
} else {
    $stmt = $session->db->prepare('SELECT name, state, type, gametypeid, started, ended FROM tournaments WHERE id = ?');
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) dLog("fail - SELECT name, state, type, gametypeid, started, ended FROM tournaments WHERE id = $id");
    $stmt->store_result();
    $stmt->bind_result($name, $state, $type, $gametypeid, $started, $ended);
    $stmt->fetch();
    $stmt = $session->db->prepare('SELECT level, url, userid FROM tournamentawards ta LEFT JOIN tournamentusers tu ON ta.id = tu.awardid WHERE ta.tournamentid = ?');
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) dLog("fail - SELECT level, url, userif FROM tournamentawards ta LEFT JOIN tournamentusers tu ON ta.id = tu.awardid WHERE tournamentid = $id");
    $stmt->store_result();
    $stmt->bind_result($level, $url, $userid);
    while ($stmt->fetch()) {
        $awards[$level]['url'] = $url;
        $awards[$level]['player'] = $userid;

    }
    if ($type == 1 && $state > 0) {// A Pyramid game in progress
        $levels = array();
        $battles = array();
        $stmt = $session->db->prepare('SELECT tu.userId, ub.battleId, ub.result FROM tournamentusers tu LEFT JOIN tournamentbattles tb ON tu.tournamentid = tb.tournamentid ' .
                    'LEFT JOIN userbattles ub on tb.battleid = ub.battleid AND tu.userId = ub.userId WHERE tu.tournamentid = ? ORDER BY tb.battleId');
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) dLog("fail - SELECT tu.userId, ub.battleId, ub.result FROM tournamentusers tu LEFT JOIN tournamentbattles tb ON tu.tournamentid = tb.tournamentid ".
                    "LEFT JOIN userbattles ub on tb.battleid = ub.battleid AND tu.userId = ub.userId WHERE tu.tournamentid = ? ORDER BY tb.battleId");
        $stmt->store_result();
        $stmt->bind_result($userId, $battleId, $result);
        while ($stmt->fetch()) {
            if (!array_key_exists($userId, $levels)) $levels[$userId] = 1;
            if (!$battleId) continue;
            if ($result == 1)
                $levels[$userId]++;
            elseif ($result !== NULL && $result != 0) {
                if ($levels[$userId] > 1) $levels[$userId]--;
            } else {
                if (!array_key_exists($battleId, $battles)) 
                    $battles[$battleId] = array($userId);
                else
                    array_push($battles[$battleId], $userId);
            }
        }
    }
}
$users = getAllUsers();

$players = array();
if ($id) {
    $stmt = $session->db->prepare('SELECT userId FROM tournamentusers WHERE tournamentId = ?');
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) dlog("fail - SELECT userId FROM tournamentusers WHERE tournamentId = $id");
    $stmt->store_result();
    $stmt->bind_result($userId);
    while ($stmt->fetch()) 
        array_push($players, $userId);
}
$imIn = array_search($_SESSION['userId'], $players) !== false;
$depth = sizeof($players) ? ceil(log(sizeof($players), 2)) : 1;
$prevState = $state;
if ($_POST && $_SESSION['admin']) {
    $name = $_POST['name'];
    $state = $_POST['state'];
    $type = $_POST['type'];
    $gametypeid = $_POST['gametypeid'];
    $gametype = 'General';
    $stmt = $session->db->prepare('SELECT type FROM gametypes WHERE id = ?');
    $stmt->bind_param('i', $gametypeid);
    if (!$stmt->execute()) dlog("fail - SELECT type FROM gametypes WHERE id = $gametypeid");
    $stmt->store_result();
    $stmt->bind_result($gametype);
    $stmt->fetch();
    $started = $_POST['started'];
    if ($id) {
        $stmt = $session->db->prepare('UPDATE tournaments SET name = ?, state = ?, started = ?, type = ?, gametypeid = ?, numrounds = ? WHERE id = ?');
        $stmt->bind_param('sisiiii', $name, $state, $started, $type, $gametypeid, $depth, $id);
        if (!$stmt->execute()) dLog("fail - UPDATE tournaments SET name = '$name', state = $state, started='$started', type = $type, gametypeid = $gametypeid, numrounds = $depth WHERE id = $id");
        dLog("Tournament #$id ($gametype $name, type = $type) is updated as $state and started $started");
    } else {
        $stmt = $session->db->prepare('INSERT INTO tournaments (name, started, state, type, gametypeid, numrounds) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('ssiiii', $name, $started, $state, $type, $gametypeid, $depth);
        if (!$stmt->execute()) dLog("fail - INSERT INTO tournaments (name, started, state, type, gametypeid, numrounds) VALUES ('$name', '$started', $state, $type, $gametypeid, $depth)");
        $id = $stmt->insert_id;
        dLog("Tournament #$id ($gametype $name, type = $type) is created as $state and started $started");
    }
    if ($state == 1 && $prevState == 0 && $type == 0) {
        preg_match_all("/(\d+)-(\d+):(.):(\d+)-(BYE|\d+)/", $_POST['draw'], $draw, PREG_SET_ORDER);
        $stmt = $session->db->prepare('DELETE FROM tournamentbattles WHERE tournamentid = ?');
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) dLog("fail - DELETE FROM tournamentbattles WHERE tournamentid = $id");
        foreach ($draw as $pair) {
            $round = $pair[1];
            $playOff = $pair[2];
            $playOffStr = sprintf("%d%s", $round, chr(ord('A') + $playOff));
            $victor = $pair[3];
            $playerA = $pair[4];
            $playerB = $pair[5];
            if ($playerB == 'BYE') {
                $battleid = 0;
            } else {
                $battlename = "$name tournament round $playOffStr";
                $stmt = $session->db->prepare("INSERT INTO battles (typeid, name, started, state) VALUES (?, ?, NOW(), 1)");
                $stmt->bind_param('is', $gametypeid, $battlename);
                if (!$stmt->execute()) dLog("fail - INSERT INTO battles (typeid, name, started, state) VALUES ($gametypeid, $battlename, NOW(), 1)");
                $battleid = $stmt->insert_id;
                $stmt = $session->db->prepare("INSERT INTO userbattles (userid, battleid) VALUES (?, ?), (?, ?)");
                $stmt->bind_param('iiii', $playerA, $battleid, $playerB, $battleid);
                if (!$stmt->execute()) dLog("fail - INSERT INTO userbattles (userid, battleid) VALUES ($playerA, $battleid), ($playerB, $battleid)");
                $recipients = array();
                $aliases = array();
                foreach($users as $user)
                    if ($user['id'] == $playerA || $user['id'] == $playerB) {
                        array_push($recipients, $user['email']);
                        array_push($aliases, $user['alias']);
                    }
                dLog("Set up $battlename (#$battleid) between {$aliases[0]} and {$aliases[1]}");
                sendMail($recipients, "$gametype $battleid has started", 
                    "{$aliases[0]} has been paired with {$aliases[1]} in the first round\n\nCheck $site/tournament.php?id=$id for details.");
            }
            $stmt = $session->db->prepare("INSERT INTO tournamentbattles (battleid, tournamentid, round, playoff, byePlays) VALUES (?, ?, ?, ?, ?)");
            $byePlays = $playerB == 'BYE' ? $playerA : 0;
            $stmt->bind_param('iiiii', $battleid, $id, $round, $playOff, $byePlays);
            if (!$stmt->execute()) dLog("fail - INSERT INTO tournamentbattles (battleid, tournamentid, round, playoff, byePlays) VALUES ($battleid, $id, $round, $playOff, $byePlays)");
        }
    }
    $targetDir = 'uploads/';
    foreach ($awards as $key => $value) {
        if (!array_key_exists("award$key", $_FILES) || !$_FILES["award$key"]["tmp_name"]) continue;
        $file = $_FILES["award$key"];
        $target_file = $targetDir . basename($file["name"]);
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
        $check = getimagesize($file["tmp_name"]);
        if($check !== false) {
            $uploadOk = 1;
        } else {
            dLog("File is not an image.");
            $uploadOk = 0;
        }
        // Allow certain file formats
        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
            dLog("Sorry, only JPG, JPEG, PNG & GIF files are allowed.");
            $uploadOk = 0;
        } 
        if ($uploadOk) {
            if (file_exists($targetFile)) unlink($targetFile);
            move_uploaded_file($file["tmp_name"], $target_file);
            $target_file = '/' . $target_file;
            if ($value['url']) {
                $stmt = $session->db->prepare('UPDATE tournamentawards SET url = ? WHERE tournamentid = ? AND level = ?');
                $stmt->bind_param('sii', $target_file, $id, $key);
                if (!$stmt->execute()) dLog("fail -UPDATE tournamentawards SET url = '$target_file' WHERE tournamentid = $id AND level = $key");
            } else {
                $stmt = $session->db->prepare('INSERT INTO tournamentawards (url, tournamentid, level) VALUES (?,?,?)');                
                $stmt->bind_param('sii', $target_file, $id, $key);
                if (!$stmt->execute()) dLog("fail -INSERT INTO tournamentawards (url, tournamentid, level) VALUES ('$target_file', $tournamentid, $key)");
            }
            dLog('Set award level ' . ($key == 0 ? 'Participation' : $results[$key]) . ' as ' . $target_file);
            $awards[$key]['url'] = $target_file;
        }
    }
    dLog('award = ' . json_encode($awards));
}
if ($_POST && ($state == 0 || $state == 1 && $type == 1)) {
    if ($_SESSION['admin']) {
        $updatedPlayers = json_decode($_POST['players']);
        dLog("Tournament $name ($id) [state=$state] players [" . join(',', $players) . "] => [" . join(',',$updatedPlayers) . "]");
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
            $stmt = $session->db->prepare('DELETE FROM tournamentusers WHERE tournamentId = ? AND userId IN (' . join(',', $oldPlayers) . ')');
            $stmt->bind_param('i', $id);
            if (!$stmt->execute()) dlog("fail - DELETE FROM tournamentusers WHERE tournamentId = $id AND userId IN (" . join(',', $oldPlayers) . ")");
        }
        foreach ($newPlayers as $playerId) {
            $stmt = $session->db->prepare('INSERT INTO tournamentusers (tournamentId, userId) VALUES (?, ?)');
            $stmt->bind_param('ii', $id, $playerId);
            if (!$stmt->execute()) dlog("fail - INSERT INTO tournamentusers (tournamentId, userId) VALUES ($id, $playerId)");   
        }
    } else if ($imIn != ($_POST['myaction'] == 'join')) {
        $stmt = $session->db->prepare('DELETE FROM tournamentusers WHERE tournamentId = ? AND userId= ?');
        $userId = $_SESSION['userId'];
        $stmt->bind_param('ii', $id, $userId);
        if (!$stmt->execute()) dlog("fail - DELETE FROM tournamentusers WHERE tournamentId = $id AND userId = $userId");
        $imIn = $_POST['myaction'] == 'join';
        if ($imIn) {
            $stmt = $session->db->prepare('INSERT INTO tournamentusers (tournamentId, userId) VALUES (?, ?)');
            $stmt->bind_param('ii', $id, $userId);
            if (!$stmt->execute()) dlog("fail - INSERT INTO tournamentusers (tournamentId, userId) VALUES ($id, $userId)");
        }
        dLog("Tournament $name ($id): I " . $_POST['myaction']);
    }
}
$draw = "";
if ($state != 0 && $type == 0) {
    $stmt = $session->db->prepare('SELECT round, playoff, tu.battleId, byePlays, userId, result FROM tournamentbattles tu ' .
        'LEFT JOIN userbattles ub ON tu.battleId = ub.battleId WHERE tournamentId = ? ORDER BY round, playoff');
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) dlog("fail - SELECT round, playoff, tu.battleId, userId, result FROM tournamentbattles tu " .
        "LEFT JOIN userbattles ub ON tu.battleId = ub.battleId WHERE tournamentId = $id ORDER BY round, playoff");
    $stmt->store_result();
    $stmt->bind_result($round, $playoff, $battleId, $byePlays, $userId, $result);
    $lastPlayoff = -1;
    while ($stmt->fetch()) {
        if ($byePlays != 0) {
            if ($draw) $draw .= ",";
            $draw .= "$round-$playoff:1:$byePlays-BYE";
        } elseif ($lastPlayoff == $playoff) {
            if ($draw) $draw .= ",";
            $draw .= "$round-$playoff:$victor:$playerA-$userId:$battleId";
        } else {
            $playerA = $userId;
            $victor = $result < 2 ? $result : 2;
        }
        $lastPlayoff = $playoff;
    }
}
$typeName = $tournamentTypes[$type];
htmlHeader($name == 'New Tournament' ? $name : "Tournament - $typeName $name");
?>
    <script>
        var tournamentId = <?= $id ? $id : '0'  ?>;
        var players = <?= json_encode($players) ?>;
        var users = <?= json_encode($users); ?>;
        var type = '<?= $typeName ?>';
        var state = '<?= $tournamentStates[$state] ?>';
<? if ($type == 1 && $state > 0) echo("var me = {$_SESSION['userId']}, levels = " . json_encode($levels) . ", battles = " . json_encode($battles) . ";\n") ?>
        loadTournament();
    </script>
    <form method="post" action="tournament.php?id=<?= $id ?>" enctype="multipart/form-data">
        <div class="ButtonBar">
            <input type="submit" value="Save" disabled>
            <button class="Cancel" onclick="document.location='tournaments.php';return false;"><?= ($_POST || $state > 0) ? 'Close' : 'Cancel' ?></a>
        </div>
        <input type="hidden" name="players">
        <div class="Tournament">
            <h2>Tournament Details</h2>
            <table>
                <tr><th>Type:</th><td><select name="type" <?= $_SESSION['admin'] ? '' : 'disabled' ?>>
<?
    foreach ($tournamentTypes as $key => $value)
        echo("<option value=\"$key\" " . ($key == $type ? "selected" : "") . ">$value</option>");
?>
                   </select></td></tr>
                <tr><th>Game type:</th><td><select name="gametypeid"><option value="0" <?= $gametypeid == 0 ? "selected" : "" ?>>General</option>
<?
    $stmt = $session->db->prepare('SELECT id, type, name FROM gametypes ORDER BY name');
    if (!$stmt->execute()) dLog("fail - SELECT id, type, name FROM gametypes ORDER BY name");
    $stmt->store_result();
    $stmt->bind_result($gtid, $gttype, $gtname);
    while($stmt->fetch()) {
?>
                                <option value="<?= $gtid ?>" <?= $gtid == $gametypeid ? "selected" : "" ?>><?= $gtname ?></option>
<?
    }
?>
                    </select></td></tr>
                <tr><th>Name and number:</th><td><input name="name" placeholder="Name for this tournament" value="<?= $name ?>" <?= $_SESSION['admin'] ? '' : 'disabled' ?>></td></tr>
                <tr><th>Started:</th><td><input name="started" value="<?= $started ?>"></td></tr>
                <tr><th>Ended:</th><td><?= $ended ? date('Y-m-d', strtotime($ended)) : '' ?></td></tr>
                <tr><th>State:</th><td><select name="state" <?= $_SESSION['admin'] ? '' : 'disabled' ?>>
<?
    foreach ($tournamentStates as $key => $value) {
?>
                                <option value="<?= $key ?>" <?= $key == $state ? "selected" : "" ?>><?= $value ?></option>
<?
    }
?>

                </td></tr>
                <tr class="Players"><th>Players:</th><td><ul class="Players"></ul></td></tr>
<? if ($_SESSION['admin']) { ?>
                <tr class="PlayerManager">
                    <td>
                        <input class="Filter" placeholder="Filter">
                        <select id="addPlayer" <?= $state == 2 ? "disabled" : ""?>><option>Select player to add</option></select>
                    </td>
                    <td><select id="removePlayer" <?= $state == 2 ? "disabled" : ""?>><option>Select player to remove</option></select></td>
                </tr>
<? } else { ?>
                <tr class="PersonalManager"><th>Me:</th><td>
<?
    $myState = array('join', 'abstain');
    foreach ($myState as $value) echo("<label for=\"action_$value\">". ucwords($value) . "</label><input type=\"radio\" name=\"myaction\" id=\"action_$value\" " . ($imIn == ($value == "join") ? "checked" : "") . ">");
} ?>
                </td><tr>
            </table>
            <div class="DrawPanel" style="display: none;">
                <h2>Draw</h2>
                <input type="hidden" name="draw" value="<?= $draw ?>">
                <div class="TableWrapper">
                    <table class="EliminationCanvas" border="0" cellspacing="0"></table>
                    <table class="ConsolationCanvas"  border="0" cellspacing="0"></table>
                </div>
                <div style="display: none;">
                    <input type="checkbox" id="drawAccepted">
                    <label for="drawAccepted">I accept this draw. By clicking save, the players will be informed.</label>
                </div>
            </div>
            <div class="PyramidPanel" style="display: none;"></div>
            <div class="Awards">
                <h2>Awards</h2>
                <input type="hidden" value="<?= htmlspecialchars(json_encode($awards)) ?>" id="awards">
            </div>
        </div>
    </form>
<?  htmlFooter() ?>
