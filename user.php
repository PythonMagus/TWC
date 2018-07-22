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
$id = $_GET['id'];
if (!$id)
{
    dLog('Visit to user.php without an id');
    header('Location: /');
    exit;
} elseif ($id == $_SESSION['userId']) {
    // Best see your own details from the index.php
    header('Location: /index.php');
    exit;
}
$stmt = $session->db->prepare('SELECT alias FROM users WHERE users.id = ?');
$stmt->bind_param('i', $id);
if (!$stmt->execute()) dLog("fail - SELECT alias FROM users WHERE users.id = $id");
$stmt->store_result();
if ($stmt->num_rows == 1) {
    $stmt->bind_result($alias);
    $stmt->fetch();
}

htmlHeader("Status for " . $alias);
?>
        <div class="ButtonBar">
            <button onclick="document.location='/';">Home</button>
            <button onclick="document.location='battles.php';">All battles</button>
            <button onclick="document.location='users.php';">All generals</button>
            <button onclick="document.location='challenge.php?to=<?= $id ?>&from=<?= $_SESSION['userId'] ?>';">Challenge</button>
<? if ($_SESSION['admin'] && $id) { ?>
            <button onclick="document.location='editUser.php?id=<?= $id ?>';">Edit</button>
<? } ?>
        </div>
<?
outputUserDetails($id);
htmlFooter() ?>
