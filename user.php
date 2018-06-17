<?
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
