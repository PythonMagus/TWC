<?
require "inc.php";
$loggedIn = array_key_exists('userId', $_SESSION) ? $_SESSION['userId'] : '';
if (!$loggedIn)
{
    header('Location: /');
    $_SESSION['thence'] = $_SERVER[REQUEST_URI];
    exit;
}
$known = array();
$stmt = $session->db->prepare('SELECT t.id, t.name, t.type, t.state, t.started, u.id, u.alias, gt.type FROM tournaments t  ' .
    'LEFT JOIN tournamentusers tu on t.id = tu.tournamentId LEFT JOIN users u ON tu.userId = u.id '.
    'LEFT JOIN gametypes gt on t.gametypeId = gt.id ORDER BY started DESC, t.id, u.alias');
if (!$stmt->execute()) dLog('fail -SELECT t.id, t.name, t.type, t.state, t.started, u.id, u.alias, gt.type FROM tournaments t  ' .
    'LEFT JOIN tournamentusers tu on t.id = tu.tournamentId LEFT JOIN users u ON tu.userId = u.id '.
    'LEFT JOIN gametypes gt on t.gametypeId = gt.id ORDER BY started DESC, t.id, u.alias');
$stmt->store_result();
$stmt->bind_result($id, $name, $type, $state, $started, $userId, $userName, $gametype);
htmlHeader("All Battles");
$lastId = 0;
$tournaments = array();
?>
        <div class="ButtonBar">
            <button onclick="document.location='/';">Home</button>
<? if ($_SESSION['admin']) { ?>
            <button onclick="document.location='tournament.php';">Create</button>
<? } ?>
        </div>
        <script>
            var statuses = <?= json_encode($tournamentStates); ?>;
            var types = <?= json_encode($tournamentTypes); ?>;
            setTournamentFilters();
        </script>
    <h2>All Tournaments</h2>
    <p>A list of <select class="StatusFilter"><option>All</option></select> tournaments of type <select class="TypeFilter"><option>All</option></select> 
        (game type <select class="GameTypeFilter"><option>All</option></select>) containing <input class="TextFilter" placeholder="any"> text.</p>
    <table class="Tournaments sortable">
        <tr><th>Started</th><th>Type</th><th>Game</th><th>Name</th><th>State</th><th>Players</th></tr>
<?  while ($stmt->fetch()) { 
        if (!$gametype) $gametype = "General";
        $known[$gametype] = true;
        if ($lastId == $id)
            echo (", <a href=\"/user.php?id=$userId\">$userName</a>");
        else { 
            if ($lastId) echo("</td></tr>");
            $lastId = $id;
?>
        <tr>
            <td class="Date" sorttable_customkey="<?= date('Y-m-d', strtotime($started)) ?>">
                <?= date('d/M/Y', strtotime($started)) ?>
            </td>
            <td class="Type">
                <?= $tournamentTypes[$type] ?>
            </td>
            <td class="GameType">
                <?= $gametype ?>
            </td>
            <td class="Name">
                <a href="/tournament.php?id=<?= $id ?>">
                    <?= $name ?>
                </a>
            </td>
            <td class="State">
                <?= $tournamentStates[$state] ?>
            </td>
            <td class="Players">
                <a href="/user.php?id=<?= $userId ?>"><?= $userName ?></a><?
        }
   } ?>
            </td>
        </tr>
    </table>
    <script>
        var gametypes = [
<?
    foreach($known as $key => $dummy) echo("'$key',");
?>
        ];
    </script>
<?  htmlFooter() ?>
