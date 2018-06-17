<?
require "inc.php";
$loggedIn = array_key_exists('userId', $_SESSION) ? $_SESSION['userId'] : '';
if (!$loggedIn)
{
    header('Location: /');
    $_SESSION['thence'] = $_SERVER[REQUEST_URI];
    exit;
}
$stmt = $session->db->prepare('SELECT id, alias FROM users ORDER BY alias');
if (!$stmt->execute()) dLog("fail - SELECT id, alias FROM users ORDER BY alias");
$stmt->store_result();
$stmt->bind_result($id, $alias);
htmlHeader("All Generals");
?>
        <div class="ButtonBar">
            <button onclick="document.location='/';">Home</button>
            <button onclick="document.location='battles.php';">All Battles</button>
<? if ($_SESSION['admin']) { ?>
            <button onclick="document.location='editUser.php';">Create</button>
<? } ?>
        </div>
        <script>
            setUserFilters();
        </script>
    <h2>All Generals</h2>
    <p>A list of generals containing <input class="TextFilter" placeholder="any"> text.</p>
    <table class="Users sortable" >
        <tr><th>Joined</th><th>Alias</th><th>Email</th><th class="sorttable_numeric">Rank</th><th class="sorttable_numeric">Points</th><th class="sorttable_numeric">Games</th><th class="sorttable_numeric">Wins</th><th>Ribbons</th></tr>
<?  
while ($stmt->fetch()) { 
    $user = getUserDetails($id);
?>
        <tr>
            <td class="Joined" sorttable_customkey="<?= date('Y-m-d', $user['since']) ?>">
                <?= date('d/M/Y', $user['since']) ?>
            </td>
            <td class="Alias">
                <a href="/user.php?id=<?= $id ?>"><?= $alias ?></a> <?= $user['admin'] ? '<span title="Club admin">👮</span>' : '' ?>
            </td>
            <td class="Email">
                <a href="mailto:<?= $user['email'] ?>">
                    <?= $user['email'] ?>
                </a>
            </td>
            <td class="Rank" sorttable_customkey="<?= $user['points'] ?>">
                <? if (array_key_exists('rankRibbonName', $user)) {?>
                    <img class="Ribbon" title="<?= $user['rankRibbonName'] ?>" src="/images/<?= $user['rankRibbonImage'] ?>.png">
                <? } ?>
            </td>
            <td class="Points">
                <?= $user['points'] ?>
            </td>
            <td class="Games" sorttable_customkey="<?= $user['battles'] ?>">
                <? if (array_key_exists('playRibbonName', $user)) {?>
                    <img class="Ribbon" title="<?= $user['playRibbonName'] ?>" src="/images/<?= $user['playRibbonImage'] ?>.png">
                <? } ?>
            </td>
            <td class="Wins" sorttable_customkey="<?= $user['victories'] ?>">
                <? if (array_key_exists('winRibbonName', $user)) {?>
                    <img class="Ribbon" title="<?= $user['winRibbonName'] ?>" src="/images/<?= $user['winRibbonImage'] ?>.png">
                <? } ?>
            </td>
            <td class="Ribbons" sorttable_customkey="<?= count($user['otherRibbons']) ?>">
                <? if (array_key_exists('yearsRibbonName', $user)) {?>
                     <img class="Ribbon" title="<?= $user['yearsRibbonName'] ?>" src="/images/<?= $user['yearsRibbonImage'] ?>.png">
                <? 
                } 
                foreach ($user['otherRibbons'] as $name => $image) { ?>
                    <img class="Ribbon" title="<?= $name ?>" src="/images/<?= $image ?>.png">
                <? } ?>
            </td>
        </tr>
<? } ?>
    </table>
<?  htmlFooter() ?>
