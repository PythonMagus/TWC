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
$known = array();
$stmt = $session->db->prepare('SELECT b.id, b.name, gt.type, b.started, b.ended, b.state, u.id, u.alias, t.id, t.name FROM battles b LEFT JOIN gametypes gt on b.typeid = gt.id ' .
    'LEFT JOIN userbattles ub on b.id = ub.battleId LEFT JOIN users u ON ub.userId = u.id LEFT JOIN tournamentbattles tb on tb.battleId = b.id '.
    'LEFT JOIN tournaments t ON tb.tournamentId = t.id ORDER BY started DESC, b.id, u.alias');
if (!$stmt->execute()) dLog("fail -SELECT b.id, b.name, gt.type, started, ended, b.state, u.id, u.alias, t.id, t.name FROM battles b LEFT JOIN gametypes gt on b.typeid = gt.id " .
    "LEFT JOIN userbattles ub on b.id = ub.battleId LEFT JOIN users u ON ub.userId = u.id  LEFT JOIN tournamentbattles tb on tb.battleId = b.id ".
    "LEFT JOIN tournaments t ON tb.tournamentId = t.id ORDER BY started DESC, b.id, u.alias");
$stmt->store_result();
$stmt->bind_result($id, $name, $type, $started, $ended, $state, $userId, $userName, $tournamentId, $tournament);
htmlHeader("All Battles", 'new');
$lastId = 0;
$tournaments = array();
setLeftBlock($navigationTabs);
?>
        <div id="tab-tournament" class="tab-pane active">
          <div class="body-box">
            <div class="title">
                <h2><span><img src="/html/images/text-all-battles.png" alt=""></span></h2>
                <div class="button-block">
                    <a id="home-btn" href="/">Home</a>
                    <a href="/tournaments.php" id="all-btl-btn">All Tourneys</a>
<?
    if ($_SESSION['admin']) echo '<a id="create-btn" href="/registerBattle.php">Create</a>';
?>
                </div>
            </div>
            <div class="sort-block">
                <h3>
                    <span class="White">A list of </span>
                    <div id="statusFilter">All </div>
                    <span class="White">battles of type </span>
                    <div id="typeFilter">All</div>
                    <span class="White">containing </span>
                    <input class="TextFilter" placeholder="any">
                    <span class="White">Text.</span>
                </h3>
                <h3>
                    <span class="White">The battles are in</span>
                    <div id="tournamentFilter">Any</div>
                    <span class="White">tournament.</span>
                </h3>
            </div>
        <script>
            var look = '<?= $look ?>';
            var statuses = <?= json_encode($battleStates); ?>;
            setNewBattleFilters();
        </script>
        <div class="content-block all-content">
    <table class="Battles sortable all-battles">
        <tr><th>Started</th><th>Type</th><th>Name</th><th title="Tournament">T</th><th>State</th><th>Ended</th><th>Players</th></tr>
<?  while ($stmt->fetch()) { 
        if (!$type) $type = "General";
        $known[$type] = true;
        if ($lastId == $id)
            echo (", <a href=\"/user.php?id=$userId\">$userName</a>");
        else { 
            if ($lastId) echo("</td></tr>");
            $lastId = $id;
            if ($tournamentId && !array_key_exists($tournamentId, $tournaments)) $tournaments[$tournamentId] = $tournament;
?>
        <tr>
            <td class="Date" sorttable_customkey="<?= date('Y-m-d', strtotime($started)) ?>">
                <?= date('d/M/Y', strtotime($started)) ?>
            </td>
            <td class="Type">
                <?= $type ?>
            </td>
            <td class="Name">
                <a href="/battle.php?id=<?= $id ?>">
                    <?= $name ?>
                </a>
            </td>
            <td class="Tournament" sorttable_customkey="<?= $tournamentId ? $tournamentId : 0 ?>">
                <?= $tournamentId ? "<a title=\"$tournament\" href=\"tournament.php?id=$tournamentId\">$tournamentId</a>" : "" ?>
            </td>
            <td class="State">
                <?= $battleStates[$state] ?>
            </td>
            <td class="Date"  sorttable_customkey="<?= date('Y-m-d', strtotime($ended)) ?>">
                <?= $state == 2 ? date('d/M/Y', strtotime($ended)) : "" ?>
            </td>
            <td class="Players">
                <a href="/user.php?id=<?= $userId ?>"><?= $userName ?></a><?
        }
   } ?>
            </td>
        </tr>
    </table>
    <script>
        var types = [
<?
    foreach($known as $type => $dummy) echo("'$type',");
?>
        ];
        var tournaments = [
<?
    foreach($tournaments as $key => $value) echo("{id: '$key', name: '$value'},");
?>
        ];
    </script>
<?  htmlFooter() ?>
