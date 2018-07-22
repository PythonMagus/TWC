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
$id = $_GET['id'];
if (!$id)
{
    dLog('Visit to battle.php without an id');
    header('Location: /');
    exit;
}
$stmt = $session->db->prepare('SELECT b.name, gt.name, b.started, b.ended, b.state, t.id, t.name FROM battles b LEFT JOIN gametypes gt ON b.typeid = gt.id ' .
                    'LEFT JOIN tournamentbattles tb ON b.id = tb.battleId LEFT JOIN tournaments t ON tb.tournamentid = t.id WHERE b.id = ?');
$stmt->bind_param('i', $id);
if (!$stmt->execute()) dLog("fail - SELECT b.name, gt.name, b.started, b.ended, d.state, t.id, t.name FROM battles b LEFT JOIN gametypes gt ON b.typeid = gt.id " .
                    "LEFT JOIN tournamentbattles tb ON b.id = tb.battleId LEFT JOIN tournaments t ON tb.tournamentid = t.id WHERE b.id = $id");
$stmt->store_result();
$stmt->bind_result($name, $type, $started, $ended, $state, $tournamentId, $tournamentName);
if (!$type) $type = '';
if (!$stmt->fetch())
{
    dLog("Failed to find battle $id");
    header('Location: /');
    exit;
}
$emailMessage = "";
$emailRecipients = array();
function newState($newState, $action)
{
    global $state, $emailMessage, $name, $id, $session, $site, $type;
    $state = $newState;
    $emailMessage = "Battle $type $name ($site/battle.php?id=$id) has $action!";
    dLog($emailMessage);
    $setStartDate = $action == 'started' ? ', started = NOW()' : '';
    $stmt = $session->db->prepare("UPDATE battles SET state = $newState WHERE id = ?");
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) dLog("fail - UPDATE battles SET state = $newState WHERE id = $id");
}
if ($_POST) {
    if ($_POST['action'] == 'start' && $state == 0) 
        newState(1, 'started');
     else if ($_POST['action'] == 'suspend' && $state == 1) 
        newState(4, 'been suspended');
     else if ($_POST['action'] == 'cancel' && $state == 1) 
        newState(3, 'been cancelled');
     else if ($_POST['action'] == 'unsuspend' && $state == 4) 
        newState(1, 'been resumed');
}

htmlHeader("Battle Details - $type $name");


?>
        <div class="ButtonBar">
            <button onclick="document.location='/';">Home</button>
            <button onclick="document.location='battles.php';">All battles</button>
<? if ($_SESSION['admin'] && $state < 2) { ?>
            <button onclick="document.location='registerBattle.php?id=<?= $id ?>';">Edit players</button>
    <? if ($state == 0) { ?>
            <button onclick="requestEmailText('start');">Start</button>
    <? } else if ($state == 1) { ?>
            <button onclick="document.location='endbattle.php?id=<?= $id ?>';">Complete</button>
            <button onclick="requestEmailText('suspend');">Suspend</button>
            <button onclick="requestEmailText('cancel');">Cancel</button>
    <? } else if ($state == 4) { ?>
            <button onclick="requestEmailText('unsuspend');">Resume</button>
    <? } ?>
<? } ?>
        </div>
        <form class="EmailText" method="post" action="battle.php?id=<?= $id ?>" style="display:none;">
            <button class="Hide">X</button>
            <h2></h2>
            <p class="Explanation">
                This will result in an email going to all players. Please provide some text to those players, perhaps with a link to the TWC web site.
            </p>
            <input type="hidden" name="action">
            <textarea name="message"></textarea>
            <div class="ButtonBar">
                <input type="submit" value="Send">
            </div>
        </form>

        <div class="BattleDetails">
            <h2>Details for <?= "$type $name" ?></h2>
            <table>
                <tr><th>Started:</th><td><?= date('d/M/Y h:i a', strtotime($started)) ?></td></tr>
<? if ($state == 2) { ?>
                <tr><th>Ended:</th><td><?= date('d/M/Y h:i a', strtotime($ended)) ?></td></tr>
<? } ?>
<? if ($tournamentId) { ?>
                <tr><th>Tournament:</th><td><a href="/tournament.php?id=<?= $tournamentId ?>"><?= $tournamentName ?></a></td></tr>
<? } ?>

                <tr><th>State:</th><td><?= $battleStates[$state] ?></td></tr>
                <tr><th>Players:</th><td><ul>

<?

$stmt = $session->db->prepare('SELECT u.id, alias, email, ub.points, result FROM userbattles ub JOIN users u ON ub.userId = u.id WHERE battleId = ? ORDER BY ub.points DESC, alias');
$stmt->bind_param('i', $id);
if (!$stmt->execute()) dlog("fail - SELECT u.id, alias, email, ub.points, result FROM userbattles ub JOIN users u ON ub.userId = u.id WHERE battleId = $id ORDER BY ub.points DESC, alias");
$stmt->store_result();
$stmt->bind_result($userId, $alias, $email, $points, $result);
while ($stmt->fetch()) {
    array_push($emailRecipients, $email);
?>
            <li><a href="user.php?id=<?= $userId ?>"><?= $alias ?></a> <a href="mailto:<?= $email ?>?subject=Re <?= "$type $name" ?>"><?= $email ?></a>
<? if ($state == 2) { ?>
                <b><?= array_key_exists($result, $results) ? $results[$result] : $result ?></b> (<?= $points ?> points)
<? } ?>
            </li>
<?
} 
if ($emailMessage)
    sendMail($emailRecipients, "TWC Battle Update - $type $name", "$emailMessage\n \n{$_POST['message']}");
?>
                </ul></td></tr>
            </table>
        </div>
<?  htmlFooter() ?>

