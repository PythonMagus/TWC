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
$look = 'new';
htmlHeader("All Battles", $look);
$lastId = 0;
$tournaments = array();
setLeftBlock($navigationTabs);
?>
        <script>
            var look = '<?= $look ?>';
            var statuses = <?= json_encode($tournamentStates); ?>;
            var types = <?= json_encode($tournamentTypes); ?>;
            setTournamentFilters();
        </script>
        <div id="tab-tournament" class="tab-pane active">
          <div class="body-box">
            <div class="title">
                <h2><span><img src="/html/images/text-all-tournament.png" alt=""></span></h2>
                <div class="button-block">
                    <a href="/">Home</a>
<?
    if ($_SESSION['admin']) echo '<a href="/tournament.php">Create</a>';
?>
                </div>
            </div>
            <div class="sort-block">
                <h3>
                    <span class="White">A list of </span>
                    <div id="statusFilter">All </div>
                    <span class="White">tournaments of type </span>
                    <div id="typeFilter">All</div>
                    <span class="White">(game type </span>
                    <div id="gameTypeFilter">All</div>
                    <span class="White">) containing </span>
                    <input class="TextFilter" placeholder="any">
                    <span class="White">Text </span>
                </h3>
            </div>
            <div class="content-block">
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
<?  htmlFooter($look) ?>
