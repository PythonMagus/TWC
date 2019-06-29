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
$stmt = $session->db->prepare('SELECT id, alias FROM users ORDER BY alias');
if (!$stmt->execute()) dLog("fail - SELECT id, alias FROM users ORDER BY alias");
$stmt->store_result();
$stmt->bind_result($id, $alias);
htmlHeader("All Generals", 'new');
setLeftBlock($navigationTabs);
?>
    <div id="s3" class="tab-pane active">
      <div class="body-box all-battle">
        <div class="title">
            <h2><span><img src="images/all-general-logo.png" alt=""></span></h2>
            <div class="button-block">
                <a href="/" id="home-btn">Home</a>
                <a href="/battles.php" id="all-btl-btn">All Battles</a>
<?
    if ($_SESSION['admin']) echo '<a id="create-btn" href="/editUser.php">Create</a>';
?>
            </div>
        </div>
        <div class="sort-block all-short">
            <h3>
                <span class="White">A list of generals containing </span>
                <input class="TextFilter" placeholder="any">
                <span class="White">text</span>
            </h3>
            <script>
                setUserFilters();
            </script>
        </div>
        <div class="content-block all-content">
          <table class="sortable">
            <thead><tr>
              <th>Joined</th> <th>Alias</th> <th>Email</th> <th class="sorttable_numeric">Rank</th> <th class="sorttable_numeric">Points</th> 
              <th class="sorttable_numeric">Games</th> <th class="sorttable_numeric">Wins</th> <th>Ribbons</th>
            </tr> </thead>
            <tbody>
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
                      </tbody></table>
                    </div>
                  </div>
                </div>
    </table>
<?  htmlFooter() ?>
