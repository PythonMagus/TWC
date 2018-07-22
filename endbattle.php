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
if (!$loggedIn || !$_SESSION['admin'])
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
$stmt = $session->db->prepare('SELECT b.name, gt.name, started, state, tournamentId FROM battles b LEFT JOIN gametypes gt ON gt.id = b.typeid LEFT JOIN tournamentbattles tb ON b.id = tb.battleId WHERE b.id = ?');
$stmt->bind_param('i', $id);
if (!$stmt->execute()) dLog("fail - SELECT b.name, gt.name, started, state, tournamentId FROM battles b LEFT JOIN gametypes gt ON gt.id = b.typeid LEFT JOIN tournamentbattles tb ON b.id = tb.battleId WHERE b.id = $id");
$stmt->store_result();
$stmt->bind_result($name, $type, $started, $state, $tournamentId);
if (!$type) $type = "";
if (!$stmt->fetch())
{
    dLog("Failed to find battle $id");
    header('Location: /');
    exit;
}
function rollTheTournament($tournamentId, $winnerId, $loserId) {
    global $session, $id, $adminEmail;
    $stmt = $session->db->prepare('SELECT t.name, numRounds, gt.id, gt.type, gt.name, t.type FROM tournaments t LEFT JOIN gametypes gt ON gt.id = t.gametypeid WHERE t.id = ?');
    $stmt->bind_param('i', $tournamentId);
    if (!$stmt->execute()) dLog("fail - SELECT t.name, numRounds, gt.id, gt.type, gt.name, t.type FROM tournaments t LEFT JOIN gametypes gt ON gt.id = t.gametypeid WHERE t.id = $tournamentId");
    $stmt->store_result();
    $stmt->bind_result($name, $numRounds, $gametypeid, $gametype, $gametypeName, $type);
    $stmt->fetch();
    if (!$gametype) {
        $gametypeid = 0;
        $gametype = "";
        $gametypeName = "General";
    }
    if ($type == 0) {// Elimination
        $stmt = $session->db->prepare('SELECT round, playoff FROM tournamentbattles WHERE tournamentId = ? AND battleId = ?');
        $stmt->bind_param('ii', $tournamentId, $id);
        if (!$stmt->execute()) dLog("fail - SELECT round, playoff FROM tournamentbattles WHERE tournamentId = $tournamentId AND battleId = $id");
        $stmt->store_result();
        $stmt->bind_result($round, $playoff);
        if (!$stmt->fetch()) {
            dLog("Failed to find tournament battle record for tournamentId = $tournamentId AND battleId = $id");
            return;
        }
        $report = "Tournament $gametype $name, Round $round, playoff $playoff has completed and the victor is $winnerId";
        $otherGame = $playoff % 2 == 0 ? $playoff + 1 : ($playoff - 1);
        $stmt = $session->db->prepare('SELECT ub.userId, result, points, tb.battleId FROM tournamentbattles tb JOIN userbattles ub ON tb.battleId = ub.battleId WHERE tournamentId = ? AND round = ? and playoff = ?');
        $stmt->bind_param('iii', $tournamentId, $round, $otherGame);
        if (!$stmt->execute()) dLog("fail - SELECT ub.userId, result, points, tb.battleId FROM tournamentbattles tb JOIN userbattles ub ON tb.battleId = ub.battleId " .
            "WHERE tournamentId = $tournamentId AND round = $round and playoff = $playoff");
        $stmt->store_result();
        $stmt->bind_result($userId, $result, $points, $battleId);
        $winner = array('id' => null, 'points' => 0);
        $loser = array('id' => null, 'points' => 0);
        while ($stmt->fetch()) {
            if (!$winner['id'] || $winner['points'] < $points) $winner = array('id' => $userId, 'points' => $points);
            if (!$loser['id'] || $loser['points'] > $points) $loser = array('id' => $userId, 'points' => $points);
        }
        if (!$winner['id']) {
            dLog($report . ". The matching battle ($round playoff) has not started yet.");
            return;
        } else if (!$winner['points']) {
            dLog($report . ". The matching battle #$battleId ($otherGame playoff) has not finished yet.");
            return;
        }
        $playerB = $winner['id'];
        if ($round == $numRounds)
            finishTournament($tournamentId, $playoff % 2 ? $winner['id'] : $winnerId, $playoff % 2 ? $loser['id'] : $loserId, $playoff % 2 ? $winnerId : $winner['id'], $name, $gametypeName, $gametype);
        else {
            dLog($report . ". The winner of battle #$battleId ($otherGame playoff) is $playerB and shall now be matched with $winnerId.");
            $playoff = floor($playoff / 2);
            $round++;
            setUpBattle($tournamentId, $round, $playoff, $gametypeid, $battlename, $winnerId, $winner['id'], $numRounds, $name, $gametypeName, $gametype);
            if ($round == $numRounds)
                setUpBattle($tournamentId, $round, 1, $gametypeid, $battlename, $loserId, $loser['id'], $numRounds, $name, $gametypeName, $gametype, "Consolation Finals");
        }
    } elseif ($type == 1) {
        resetThePyramid($tournamentId);
    }
}
function finishTournament($tournamentId, $firstId, $secondId, $thirdId, $name, $gametypeName, $gametype) {
    global $session, $site, $adminEmail;
    $aliases = array();
    $recipients = array();
    $stmt = $session->db->prepare('SELECT u.id, alias, email FROM users u JOIN tournamentusers tu ON u.id = tu.userid WHERE tournamentid = ? ORDER BY alias');
    $stmt->bind_param("i", $tournamentId);
    if (!$stmt->execute()) dLog("fail - SELECT u.id, alias, email FROM users u JOIN tournamentusers tu ON u.id = tu.userid WHERE tournamentid = $id ORDER BY alias");
    $stmt->store_result();
    $stmt->bind_result($userId, $alias, $email);
    while ($stmt->fetch()) {
        $aliases[$userId] = array('alias' => $alias, 'email' => $email);
        array_push($recipients, $email);
    }
    $awards = array();
    $stmt = $session->db->prepare('SELECT id, level, url FROM tournamentawards ta WHERE ta.tournamentid = ?');
    $stmt->bind_param('i', $tournamentId);
    if (!$stmt->execute()) dLog("fail - SELECT level, url FROM tournamentawards ta WHERE tournamentid = $tournamentId");
    $stmt->store_result();
    $stmt->bind_result($awardId, $level, $url);
    while ($stmt->fetch()) {
        $awards[$level] = $awardId;
    }
    if (array_key_exists(90, $awards)) {
        $awardId = $awards[90];
        $stmt = $session->db->prepare('UPDATE tournamentusers tu SET awardid = ? WHERE tournamentid = ?');
        $stmt->bind_param("ii", $awardId, $tournamentId);
        if (!$stmt->execute()) dLog("fail - UPDATE tournamentusers tu SET awardid = $awardId WHERE tournamentid = $tournamentId");
    }
    foreach ($awards as $key => $value) {
        if ($key == 90) continue;
        $userId = $key == 1 ? $firstId : ($key == 2 ? $secondId : $thirdId);
        $stmt = $session->db->prepare('UPDATE tournamentusers tu SET awardid = ? WHERE tournamentid = ? AND userId = ?');
        $stmt->bind_param("iii", $value, $tournamentId, $userId);
        if (!$stmt->execute()) dLog("fail - UPDATE tournamentusers tu SET awardid = $value WHERE tournamentid = $tournamentId AND userid = $userId");
    }
    $stmt = $session->db->prepare('UPDATE tournaments SET ended = NOW(), state=2 WHERE id = ?');
    $stmt->bind_param("i", $tournamentId);
    if (!$stmt->execute()) dLog("fail - UPDATE tournaments SET ended = NOW(), state=2 WHERE id = $tournamentId");
    
    sendMail($recipients, "$gametype $name Tournament has completed", 
        "Completion of $gametypeName $name Tournament\nWinner is {$aliases[$firstId]['alias']}\nRunner up is {$aliases[$secondId]['alias']}\n" .
            "Third is {$aliases[$thirdId]['alias']}\n\nCongratulations all!\n\nCheck $site/tournament.php?id=$tournamentId for details.");
    dLog("Tournament #$tournamentId complete. Winner is $firstId, then $secondId, then $thirdId");
}
function resetThePyramid($tournamentId) {
    global $session;
    $usersLevel = array();
    $stmt = $session->db->prepare('SELECT ub.userId, ub.battleId, ub.result FROM tournamentbattles tb ' .
                'JOIN userbattles ub on tb.battleid = ub.battleid WHERE tb.tournamentid = ? AND ub.result != 0 ORDER BY tb.battleId');
    $stmt->bind_param('i', $tournamentId);
    if (!$stmt->execute()) dLog("fail - SELECT ub.userId, ub.battleId, ub.result FROM tournamentbattles tb ".
                "JOIN userbattles ub on tb.battleid = ub.battleid WHERE tb.tournamentid = $tournamentId AND ub.result != 0 ORDER BY tb.battleId");
    $stmt->store_result();
    $stmt->bind_result($userId, $battleId, $result);
    while ($stmt->fetch()) {
        if (!array_key_exists($userId, $usersLevel)) $usersLevel[$userId] = array('level' => 1, 'battleId' => 0);
        $usersLevel[$userId]['battleId'] = $battleId;
        if ($result == 1)
            $usersLevel[$userId]['level']++;
        elseif ($result !== NULL && $result != 0) {
            if ($usersLevel[$userId]['level'] > 1) $usersLevel[$userId]['level']--;
        }
    }
    $leaders = array();
    foreach ($usersLevel as $userId => $level)
        if ($level['battleId'])
            array_push($leaders, array('level' => $level['level'], 'battleId' => $level['battleId'], 'userId' => $userId));
    function levelThenOlder($a, $b) {
        if ($a['level'] == $b['level'])
            return $a['battleId'] - $b['battleId'];
        else
            return $b['level'] - $a['level'];
    }
    usort($leaders, "levelThenOlder");
    $awards = array();
    $participation = 0;
    $stmt = $session->db->prepare('SELECT id, level FROM tournamentawards WHERE tournamentid = ?');
    $stmt->bind_param('i', $tournamentId);
    if (!$stmt->execute()) dLog("fail - SELECT id, level FROM tournamentawards WHERE tournamentid = $tournamentId");
    $stmt->store_result();
    $stmt->bind_result($awardId, $level);
    while ($stmt->fetch()) {
        if ($level == 90) // Participated
            $participation = $awardId;
        else
            $awards[$level] = $awardId;
    }
    $stmt = $session->db->prepare('UPDATE tournamentusers SET awardid = ? WHERE tournamentId = ?');
    $stmt->bind_param('ii', $participation, $tournamentId);
    if (!$stmt->execute()) dLog("fail - UPDATE tournamentusers SET awardid = $participation WHERE tournamentId = $tournamentId");
    foreach($awards as $level => $awardId) {
        if ($level > sizeof($leaders)) continue;
        extract($leaders[$level - 1]);
        $stmt = $session->db->prepare('UPDATE tournamentusers SET awardid = ? WHERE tournamentId = ? AND userId = ?');
        $stmt->bind_param('iii', $awardId, $tournamentId, $userId);
        if (!$stmt->execute()) dLog("fail - UPDATE tournamentusers SET awardid = $awardId WHERE tournamentId = $tournamentId AND userId = $userId");
    }    
}    
function setUpBattle($tournamentId, $round, $playoff, $gametypeid, $battlename, $playerA, $playerB, $numRounds, $name, $gametypeName, $gametype, $roundName) {
    global $session, $site, $adminEmail;
    $playOffStr = sprintf("%d%s", $round, chr(ord('A') + $playOff));
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
    $stmt = $session->db->prepare('SELECT alias, email FROM users WHERE id in (?,?) ORDER BY alias');
    $stmt->bind_param("ii", $playerA, $playerB);
    if (!$stmt->execute()) dLog("fail - SELECT alias, email FROM users WHERE id in ($playerA,$playerB) ORDER BY alias");
    $stmt->store_result();
    $stmt->bind_result($alias, $email);
    while ($stmt->fetch()){
        array_push($recipients, $email);
        array_push($aliases, $alias);
    }
    $finals = $round + 3 - $numRounds;
    if (!$roundName)
        $roundName = $finals < 0 ? 'Round ' . ($round + 1) : array('Quarter-Finals','Semi-Finals','Finals','Victor')[$finals];
    dLog("Set up $gametype $battlename (#$battleid) between {$aliases[0]} and {$aliases[1]}");
    sendMail($recipients, "$gametype $battlename $roundName has started", 
        "{$aliases[0]} has been paired with {$aliases[1]} in $roundName of $gametypeName $battlename\n\nCheck $site/tournament.php?id=$tournamentId for details.");
    $stmt = $session->db->prepare("INSERT INTO tournamentbattles (battleid, tournamentid, round, playoff) VALUES (?, ?, ?, ?)");
    $byePlays = $playerB == 'BYE' ? $playerA : 0;
    $stmt->bind_param('iiii', $battleid, $tournamentId, $round, $playoff);
    if (!$stmt->execute()) dLog("fail - INSERT INTO tournamentbattles (battleid, tournamentid, round, playoff) VALUES ($battleid, $tournamentId, $round, $playoff)");
}
$emailMessage = "";
if ($_POST)
{
    $state = 2;
    dLog("Battle $name has ended");
    $stmt = $session->db->prepare("UPDATE battles SET state = 2, ended = NOW() WHERE id = ?");
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) dLog("fail - UPDATE battles SET state = 2, ended = NOW() WHERE id = $id");
    $winner = array('id' => null, 'points' => 0);
    $loser = array('id' => null, 'points' => 0);
    $stmt = $session->db->prepare('SELECT u.id, alias, email  FROM userbattles ub JOIN users u ON ub.userId = u.id WHERE battleId = ? ORDER BY alias');
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) dlog("fail - SELECT u.id, alias, email FROM userbattles ub JOIN users u ON ub.userId = u.id WHERE battleId = $id ORDER BY alias");
    $stmt->store_result();
    $stmt->bind_result($userId, $alias, $email);
    while ($stmt->fetch()) {
        $stmt2 = $session->db->prepare("UPDATE userbattles SET result=?, points = ? WHERE userId = ? AND battleId = ?");
        $result = $_POST['result_'. $userId];
        $points = $_POST['points_' . $userId];
        $stmt2->bind_param('iiii', $result, $points, $userId, $id);
        if (!$stmt2->execute()) dLog("fail - UPDATE userbattles SET result=$result, points = $points WHERE userId = $userId AND battleId = $id");
        $user = getUserDetails($userId);
        $desc = ($result == 90 ? 'lost' : ($result == 1 ? 'WON!!!' : ("came " . $results[$result] . " in"))) . " $type $name and scored $points points.";
        dLog("Player {$user['alias']} $desc");
        sendMail(array($user['email']), "TWC Battle $type $name has completed", 
            "You $desc\n{$_POST['message']}\n \nCheck the results: $site/battle.php?id=$id\nCheck your ribbons: $site");
        if ($tournamentId) {
            if (!$winner['id'] || $winner['points'] < $points) $winner = array('id' => $userId, 'points' => $points);
            if (!$loser['id'] || $loser['points'] > $points) $loser = array('id' => $userId, 'points' => $points);
        }
    }
    if ($tournamentId)
        rollTheTournament($tournamentId, $winner['id'], $loser['id']);
    header("Location: /battle.php?id=$id");
    exit;
}


htmlHeader("End Battle - $type $name");
?>
        <form class="EndBattle" method="post" action="endbattle.php?id=<?= $id ?>">
        <script>
            var readonly = false;       // changed my mind
            validateEndBattle();
        </script>
        <div class="BattleDetails">
            <h2>End of Game Scoring for <?= "$type $name" ?></h2>
            <table>
                <tr><th>Started:</th><td colspan="2"><?= date('d/M/Y h:m a', strtotime($started)) ?></td></tr>

<?

function outputResultSelect($userId) {
    global $results;
?>
    <select name="result_<?= $userId ?>" class="Results">
        <option selected>Select result</option>
<?  foreach ($results as $key => $value) 
        if ($value) { ?>
        <option value="<?= $key ?>" <?= $_POST && ($key == $_POST["result_$userId"]) ? ' selected': '' ?>><?= $value ?></option>
<?  } ?>
    </select>
<?
}
$stmt = $session->db->prepare('SELECT u.id, alias, email  FROM userbattles ub JOIN users u ON ub.userId = u.id WHERE battleId = ? ORDER BY alias');
$stmt->bind_param('i', $id);
if (!$stmt->execute()) dlog("fail - SELECT u.id, alias, email FROM userbattles ub JOIN users u ON ub.userId = u.id WHERE battleId = $id ORDER BY alias");
$stmt->store_result();
$stmt->bind_result($userId, $alias, $email);
while ($stmt->fetch()) {
?>
            <tr>
                <th><a href="mailto:<?= $email ?>?subject=Re <?= "$type $name" ?>"><?= $alias ?></a></th>
                <td><? outputResultSelect($userId) ?></td>
                <td><input name="points_<?= $userId ?>" value="<?= $_POST ? $_POST["points_$userId"] : '0' ?>" class="Points"></td>
            </tr>
<? } ?>
            </table>
        </div>
            <p class="Explanation">
                This will result in an email going to all players. Please provide some text to those players, perhaps with a link to the TWC web site.
            </p>
            <input type="hidden" name="action">
            <textarea name="message" placeholder="Give some acknowledgement of the result and a link to the web site."></textarea>
            <div class="ButtonBar">
                <input type="submit" value="Send" disabled>
                <button class="Cancel" onclick="document.location='<?= $id ? "battle.php?id=$id" : "index.php" ?>';return false;"><?= $_POST ? 'Close' : 'Cancel' ?></a>
            </div>
        </form>
<?  htmlFooter() ?>
