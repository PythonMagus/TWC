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
$stmt = $session->db->prepare('SELECT c.id, `from`, f.alias, `to`, t2.alias, gt.type, tournamentid, t.name, handle ' .
        'FROM challenges c LEFT JOIN gametypes gt on c.gametypeid = gt.id JOIN users f ON c.`from` = f.id ' .
        'LEFT JOIN users t2 ON c.`to` = t2.id LEFT JOIN tournaments t on c.tournamentId = t.id ORDER BY c.id DESC');
if (!$stmt->execute()) dLog("fail -SELECT `from`, f.alias, `to`, t2.alias, gt.type, tournamentid, t.name, handle ' .
        'FROM challenges c LEFT JOIN gametypes gt on c.gametypeid = gt.id JOIN users f ON c.`from` = f.id ' .
        'LEFT JOIN users t2 ON c.`to` = t2.id LEFT JOIN tournaments t on c.tournamentId = t.id ORDER BY c.id DESC");
$stmt->store_result();
$stmt->bind_result($id, $fromId, $fromAlias, $toId, $toAlias, $gametype, $tournamentId, $tournamentName, $handle);
$toMe = array();
$fromMe = array();
$admin = array();
while ($stmt->fetch()) {
    $row = array(
        'id' => $id,
        'fromId' => $fromId,
        'fromAlias' => $fromAlias,
        'toId' => $toId,
        'toAlias' => $toAlias,
        'gametype' => $gametype,
        'tournamentId' => $tournamentId,
        'tournamentName' => $tournamentName,
        'handle' => $handle
    );  
    if ($fromId == $loggedIn)
        array_push($fromMe, $row);
    elseif ($toId == $loggedIn)
        array_push($toMe, $row);
    elseif ($_SESSION['admin'])
        array_push($admin, $row);
}
function showTable($result, $label) {
    if (!sizeof($result)) return;
?>
    <h2><?= $label ?></h2>
    <table class="Challenges sortable">
        <tr><th>Tournament</th><th>Type</th><th>Challenger</th><th>Challenged</th><th>&nbsp;</th></tr>
<?  foreach ($result as $row) { 
        extract($row);
        if (!$gametype) $gametype = "General";
?>
        <tr>
            <td class="Tournament">
                <a href="/tournament.php?id=<?= $tournamentId ?>"><?= $tournamentName ?></a>
            </td>
            <td class="Type">
                <?= $gametype ?>
            </td>
            <td class="Name">
                <a href="/user.php?id=<?= $fromId ?>">
                    <?= $fromAlias ?>
                </a>
            </td>
            <td class="Name">
                <a href="/user.php?id=<?= $toId ?>">
                    <?= $toAlias ?>
                </a>
            </td>
            <td class="View">
                <a href="challenge.php?id=<?= $id ?>">View</a>
            </td>
        </tr>
<? } ?>
    </table>
<?
}
htmlHeader("All Challenges");
?>
        <div class="ButtonBar">
            <button onclick="document.location='/';">Home</button>
        </div>
<?
showTable($toMe, "Others Challenge Me");
showTable($fromMe, "My Challenges");
showTable($admin, "Other Challenges");
htmlFooter() 
?>
