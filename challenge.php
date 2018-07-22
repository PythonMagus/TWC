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
if (!$loggedIn)
{
    header('Location: /');
    $_SESSION['thence'] = $_SERVER['REQUEST_URI'];
    exit;
}
$id = array_key_exists('id', $_GET) ? $_GET['id'] : '';
$handle = '';
$tournamentName = '';
$tournamentId = 0;
$users = getAllUsers();
if ($id) {
    $stmt = $session->db->prepare('SELECT `from`, f.alias, f.email, `to`, t2.alias, t2.email, c.gametypeid, tournamentid, t.name, handle ' .
        'FROM challenges c JOIN users f ON c.`from` = f.id ' .
        'LEFT JOIN users t2 ON c.`to` = t2.id LEFT JOIN tournaments t on c.tournamentId = t.id WHERE c.id = ?');
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) dLog("fail - SELECT `from`, f.alias, f.email, `to`, t2.alias, t2.email, gametypeid, tournamentid, t.name, handle " .
        "FROM challenges c JOIN users f ON c.`from` = f.id LEFT JOIN users t2 ON c.`to` = t2.id LEFT JOIN tournaments t on c.tournamentId = t.id WHERE c.id = $id");
    $stmt->store_result();
    $stmt->bind_result($fromId, $fromAlias, $fromEmail, $toId, $toAlias, $toEmail, $gametypeId, $tournamentId, $tournamentName, $handle);
    $stmt->fetch();
} elseif (array_key_exists('handle', $_GET)) {
    $handle = $_GET('handle');
    $stmt = $session->db->prepare('SELECT `from`, f.alias, `to`, t.alias, c.gametypeid, tournamentid, t.name, c.id FROM challenges c JOIN users f ON c.`from` = f.id ' .
        'LEFT JOIN users t ON c.`to` = t.id LEFT JOIN tournaments t on c.tournamentId = t.id WHERE handle = ?');
    $stmt->bind_param('s', $handle);
    if (!$stmt->execute()) dLog("fail - SELECT `from`, f.alias, `to`, t.alias, gametypeid, tournamentid, t.name, c.id FROM challenges c JOIN users f ON c.`from` = f.id LEFT JOIN users t ON c.`to` = t.id LEFT JOIN tournaments t on c.tournamentId = t.id WHERE handle = $handle");
    $stmt->store_result();
    $stmt->bind_result($fromId, $fromAlias, $toId, $toAlias, $gametypeId, $tournamentId, $tournamentName, $id);
    if (!$stmt->fetch()) {
        htmlHeader('Challenge no longer exists');
?>
        <div class="ButtonBar">
            <button onclick="document.location='/';">Home</button>
            <button onclick="document.location='challenges.php';">My Challenges</button>
        </div>
        <span class="Error">Sorry. That challenge no longer exists</span>
<?
        htmlFooter();
        exit;
    }
} else if (array_key_exists('to', $_GET)) {
    $fromId = $_GET['from'];
    $toId =  $_GET['to'];
    if (array_key_exists('tournamentId', $_GET)) {
        $tournamentId =  $_GET['tournamentId'];
        $stmt = $session->db->prepare('SELECT name, gametypeid FROM tournaments WHERE id = ?');
        $stmt->bind_param('i', $tournamentId);
        if (!$stmt->execute()) dLog("fail - SELECT name, gametypeid FROM tournaments WHERE id = $id");
        $stmt->store_result();
        $stmt->bind_result($tournamentName, $gametypeId);
        $stmt->fetch();
    } else {
        $tournamentId = 0;
        $gametypeId = 0;
    }
    foreach($users as $user)
        if ($user['id'] == $fromId)
            $fromAlias = $user['alias'];
}
if (!$handle)
    while (true) {
        $handle = getToken(12);
        $stmt = $session->db->prepare('SELECT handle FROM challenges WHERE handle = ?');
        $stmt->bind_param('s', $handle);
        if (!$stmt->execute()) dLog("fail - SELECT handle FROM challenges WHERE handle = $handle");
        $stmt->store_result();
        if (!$stmt->fetch()) break;
    }

function deleteChallenge($id, $reason) {
    global $session;
    $stmt = $session->db->prepare('DELETE FROM challenges WHERE id = ?');
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) dLog("fail - DELETE FROM challenges WHERE id = $id");
    dLog("Challenge #$id deleted because $reason");
}
$gametypes = array(0 => array('type' => 'General', 'name' => 'General'));
$stmt = $session->db->prepare('SELECT id, type, name FROM gametypes ORDER BY name');
if (!$stmt->execute()) dLog("fail - SELECT id, type, name FROM gametypes ORDER BY name");
$stmt->store_result();
$stmt->bind_result($gtid, $gttype, $gtname);
while($stmt->fetch())
    $gametypes[$gtid] = array('type' => $gttype, 'name' => $gtname);

$mailed = false;
if ($_POST) {
    if ($id) {
        if ($_SESSION['userId'] == $fromId) {
            if ($_POST['action'] == 'Cancel') {
                deleteChallenge($id, 'cancelled by challenger #' . $_SESSION['userId'] . "\n{$_POST['message']}");
                if ($tournamentId)
                    header("Location:/tournament.php?id=$tournamentId");
                else
                    header("Location:/");
                exit;
            } elseif ($_POST['action'] == 'Reissue') {
                $mailed = "This is a reminded that a challenge by $fromAlias to $toAlias for a game of " . $gametypes[$gametypeId]['name'] . 
                        " has been issued.\n{$_POST['message']}\n\nTo accept or reject: $site/challenge.php?handle=$handle";
                sendMail(array($fromEmail,$toEmail), "Reminder: TWC " . $gametypes[$gametypeId]['type'] . " $tournamentName Challenge has been issued", $mailed);
                dLog("Challenge #$id ($handle) has been reissued between #$fromId and #$toId\n{$_POST['message']}");
            }
        } else if ($_SESSION['userId'] == $toId) {
            if ($_POST['action'] == 'Reject') {
                deleteChallenge($id, 'rejected by opponent #' . $_SESSION['userId']);
                sendMail(array($fromEmail,$toEmail) , "TWC " . $gametypes[$gametypeId]['type'] . " Challenge has been rejected", 
                    "The challenge by $fromAlias to $toAlias for a game of " . $gametypes[$gametypeId]['name'] . " has been rejected.\nReason: {$_POST['message']}");
                header('Location:/challenges.php');
                exit;
            } else {
                deleteChallenge($id, 'accepted by opponent #' . $_SESSION['userId']);
                $battlename = $tournamentId ? "Tournament $tournamentName challenge" : ($gametypes[$gametypeId]['type'] . ' challenge');
                $stmt = $session->db->prepare('INSERT INTO battles (name, started, state, typeid) VALUES (?, NOW(), 1, ?)');
                $stmt->bind_param('si', $battlename, $gametypeId);
                if (!$stmt->execute()) dLog("fail - INSERT INTO battles (name, started, state, typeid) VALUES ($battlename, NOW(), 1, $gametypeId)");
                $battleId = $stmt->insert_id;
                $stmt = $session->db->prepare('INSERT INTO userbattles (battleId, userId) VALUES (?, ?),(?,?)');
                $stmt->bind_param('iiii', $battleId, $fromId, $battleId, $toId);
                if (!$stmt->execute()) dlog("fail - INSERT INTO userbattles (battleId, userId) VALUES ($battleId, $fromId), ($battleId, $toId)");   
                if ($tournamentId) {
                    $stmt = $session->db->prepare("INSERT INTO tournamentbattles (battleid, tournamentid) VALUES (?, ?)");
                    $stmt->bind_param('ii', $battleId, $tournamentId);
                    if (!$stmt->execute()) dLog("fail - INSERT INTO tournamentbattles (battleid, tournamentid) VALUES ($battleid, $tournamentId)");
                }

                sendMail(array($fromEmail,$toEmail) , "TWC " . $gametypes[$gametypeId]['type'] . " Challenge has been accepted", 
                    "The challenge by $fromAlias to $toAlias for a game of " . $gametypes[$gametypeId]['name'] . 
                        " has been accepted.\n{$_POST['message']}\nFor more details: $site/battle.php?id=$battleId");
                header("Location:/battle.php?id=$battleId");
                if ($tournamentId) {
                    $stmt = $session->db->prepare('SELECT id, `to`, `from` FROM challenges WHERE tournamentId = ? AND (`to` IN (?,?) OR `from` IN (?, ?))');
                    $stmt->bind_param('iiiii', $tournamentId, $toId, $fromId, $toId, $fromId);
                    if (!$stmt->execute()) dLog("fail - SELECT id, `to`, `from` FROM challenges WHERE tournamentId = $tournamentId AND (`to` IN ($toId, $fromId) OR `from` IN ($toId, $fromId))");
                    $stmt->store_result();
                    $stmt->bind_result($challengeId, $playerA, $playerB);
                    while ($stmt->fetch())
                        deleteChallenge($challengeId, "between #$playerA and #$playerB in tournmament #$tournmament because #$toId and #$fromId have started a game in the same tournament");
                }
                exit;
            } 
        }
    } else {
        $fromId = $_SESSION['userId'];
        $toId = $_POST['to'];
        $gametypeId = $_POST['gametype'];
        $stmt = $session->db->prepare('INSERT INTO challenges (`from`, `to`, gametypeid, handle) VALUES(?,?,?,?)');
        $stmt->bind_param('iiis', $fromId, $toId, $gametypeId, $handle);
        if (!$stmt->execute()) dLog("fail - INSERT INTO challenges (`from`, `to`, gametypeid, handle) VALUES( $fromId, $toId, $gametypeId, '$handle')");
        $id = $stmt->insert_id;
        if (array_key_exists('tournamentId', $_POST)) {
            $tournamentId = $_POST['tournamentId'];
            $stmt = $session->db->prepare('UPDATE challenges SET tournamentId = ? WHERE id = ?');
            $stmt->bind_param('ii', $tournamentId, $id);
            if (!$stmt->execute()) dLog("fail - UPDATE challenges SET tournamentId = $tournamentId WHERE id = $id");
            $stmt = $session->db->prepare('SELECT name, gametypeid FROM tournaments WHERE id = ?');
            $stmt->bind_param('i', $tournamentId);
            if (!$stmt->execute()) dLog("fail - SELECT name, gametypeid FROM tournaments WHERE id = $id");
            $stmt->store_result();
            $stmt->bind_result($tournamentName, $gametypeId);
            $stmt->fetch();
        }
        $emails = array();
        foreach ($users as $value)
            if ($value['id'] == $toId) {
                $toAlias = $value['alias'];
                $toEmail = $value['email'];
            } elseif ($value['id'] == $fromId) { 
                $fromAlias = $value['alias'];
                $fromEmail = $value['email'];
            }
        $mailed = "The challenge by $fromAlias to $toAlias for a game of " . $gametypes[$gametypeId]['name'] . 
                " has been issued.\n{$_POST['message']}\n\nTo accept or reject: $site/challenge.php?handle=$handle";
        sendMail(array($fromEmail,$toEmail), "TWC " . $gametypes[$gametypeId]['type'] . " $tournamentName Challenge has been issued", $mailed);
        dLog("Challenge #$id ($handle) has been set up between #$fromId and #$toId\n{$_POST['message']}");
    }
}
htmlHeader($id ? $gametypes[$gametypeId]['type'] . " $tournamentName Challenge from $fromAlias to $toAlias" : 'New Challenge');
?>
    <form method="post" action="challenge.php?id=<?= $id ?>" enctype="multipart/form-data">
        <input type="hidden" name="players">
        <div class="Challenge">
            <h2>Challenge Details</h2>
            <table>
<? if ($tournamentId) { ?>
                <tr><th>Tournament:</th><td>
                    <a href="/tournament.php?id=<?= $tournamentId ?>"><?= $tournamentName ?></a>
                    <input type="hidden" name="tournamentId" value="<?= $tournamentId ?>">
                </td></tr>
<? } ?>
                <tr><th>Type:</th><td><select name="gametype" <?= !$tournamentId && $fromId == $_SESSION['userId'] ? '' : 'disabled' ?>>
<?
    foreach ($gametypes as $key => $value)
        echo("<option value=\"$key\" " . ($key == $gametypeId ? "selected" : "") . ">{$gametypes[$key]['name']}</option>");
?>
                   </select></td></tr>
                <tr><th>From:</th><td><a href="/user.php?id=<?= $fromId ?>"><?= $fromAlias ?></a></td></tr>
                <tr><th>To:</th><td><select name="to" <?= !$tournamentId && $fromId == $_SESSION['userId'] ? '' : 'disabled' ?>>
<?
    foreach ($users as $value)
        echo("<option value=\"{$value['id']}\" " . ($value['id'] == $toId ? "selected" : "") . ">{$value['alias']}</option>");
?>
                   </select></td></tr>
<? if ($id && $_SESSION['userId'] == $toId) { ?>
                <tr><th>Action:</th><td>
                    <input type="radio" name="action" id="rejectChallenge" value="Reject">
                    <label for="rejectChallenge">Reject</label>
                    <input type="radio" name="action" id="acceptChallenge" value="Accept">
                    <label for="acceptChallenge">Accept</label>
                </td></tr>
<? } elseif ($id && $_SESSION['userId'] == $fromId) { ?>
                <tr><th>Action:</th><td>
                    <input type="radio" name="action" id="cancelChallenge" value="Cancel">
                    <label for="cancelChallenge">Cancel</label>
                    <input type="radio" name="action" id="reissueChallenge" value="Reissue">
                    <label for="reissueChallenge">Reissue</label>
                </td></tr>
<? } ?>
<? if (!$id && $_SESSION['userId'] == $fromId) { ?>
                <tr><th>Action:</th><td>
                    <input type="radio" name="action" id="issueChallenge" value="Issue" checked>
                    <label for="issueChallenge">Issue</label>
                </td></tr>
<? } ?>
<? if ($_SESSION['userId'] == $toId || $_SESSION['userId'] == $fromId) { ?>
                <tr><th>Message:</th><td><textarea name="message" placeholder="Optional text to accompany your challenge"></textarea></td></tr>
<? } ?>
<? if ($mailed) { ?>
                <tr><th>Sent:</th><td class="Sent"><pre><?= $mailed ?></pre></td></tr>
<? } ?>
            <div class="ButtonBar">
<? if ($_SESSION['userId'] == $toId || $_SESSION['userId'] == $fromId) { ?>
                <input type="submit" value="Save" onclick="for(var i = 0, nodes = document.querySelectorAll('select'); i < nodes.length; i++) nodes[i].disabled = false;">
                <button class="Cancel" onclick="document.location='challenges.php';return false;"><?= $_POST ? 'Close' : 'Cancel' ?></a>
<? } else { ?>
                <button class="Cancel" onclick="document.location='challenges.php';return false;">Close</a>
<? } ?>
            </div>
        </div>
    </form>
<?  htmlFooter() ?>

