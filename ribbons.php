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
htmlHeader("All Ribbons");
foreach (array('play' => 'battles', 'win' => 'victories', 'years' => 'years', 'military' => 'points', 'club' => 'service') as $type => $score) {
?>
	<div class="RibbonSet <?= $type ?>">
		<h2>Ribbons for <?= $score ?></h2>
		<table>
<?
            $stmt = $session->db->prepare("SELECT name, image FROM ribbons WHERE family = '$type' ORDER BY level DESC");
            $stmt->bind_param('i', $result[$score]);
            if (!$stmt->execute()) dLog("fail - SELECT name FROM ribbons WHERE type = '$type' ORDER BY level DESC");
            $stmt->store_result();
            $stmt->bind_result($ribbonName, $ribbonImage);
            while ($stmt->fetch())
                echo "<tr><td><img class=\"Ribbon\" src=\"/images/$ribbonImage.png\"><td class=\"Label\">$ribbonName</td></tr>";
?>	
		</table>
	</div>
<?
}
htmlFooter();
?>
