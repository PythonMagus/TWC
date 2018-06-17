<?
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
htmlHeader("All Battles");
$lastId = 0;
$tournaments = array();
?>
        <div class="ButtonBar">
            <button onclick="document.location='/';">Home</button>
<? if ($_SESSION['admin']) { ?>
            <button onclick="document.location='registerBattle.php';">Create</button>
<? } ?>
        </div>
        <script>
            var statuses = <?= json_encode($battleStates); ?>;
            setBattleFilters();
        </script>
    <h2>All Battles</h2>
    <p>A list of <select class="StatusFilter"><option>All</option></select> battles of type <select class="TypeFilter"><option>All</option></select> containing <input class="TextFilter" placeholder="any"> text.</p>
    <p>The battles are in <select class="TournamentFilter"><option value="Any">Any</option><option value="None">No</option></select> tournament.</p>
    <table class="Battles sortable">
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
