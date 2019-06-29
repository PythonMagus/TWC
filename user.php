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

htmlHeader("Status for " . $alias, 'new');
setLeftBlock($navigationTabs);

?>
<?
outputUserDetails($id);
htmlFooter() ?>
