<?
    error_reporting( E_ALL );
    $site = 'https://twc.redwaratah.com';
    $adminEmail = 'twc@twc.redwaratah.com';
    $GLOBALS['version'] = '0.6';
    if (strpos(getcwd(), 'twctest') === false)
    {
        $GLOBALS['DEBUG_SESSION'] = false;
        $GLOBALS['TESTING'] = true;
        $GLOBALS['DATABASE'] = 'maneschi_twc';
        $GLOBALS['TITLE'] = 'The Wargaming Club';
    }
    else
    {
        $site = 'https://twctest.redwaratah.com';
        $adminEmail = 'twc@twc.redwaratah.com';
        $GLOBALS['version'] .= 'test';
        $GLOBALS['DEBUG_SESSION'] = true;
        $GLOBALS['TESTING'] = true;
        $GLOBALS['DATABASE'] = 'maneschi_twc_test';
        $GLOBALS['TITLE'] = '***TEST*** The Wargaming Club ***TEST***';
    }

    class session {
        function __construct() {
            // set our custom session functions.
            session_set_save_handler(array($this, 'open'), array($this, 'close'), array($this, 'read'), array($this, 'write'), array($this, 'destroy'), array($this, 'gc'));
 
            // This line prevents unexpected effects when using objects as save handlers.
            register_shutdown_function('session_write_close');
        }

        function start_session($session_name, $secure) {
           // Make sure the session cookie is not accessable via javascript.
           $httponly = true;
         
           // Hash algorithm to use for the sessionid. (use hash_algos() to get a list of available hashes.)
           $session_hash = 'sha512';
         
           // Check if hash is available
           if (in_array($session_hash, hash_algos())) {
              // Set the has function.
              ini_set('session.hash_function', $session_hash);
           }
           // How many bits per character of the hash.
           // The possible values are '4' (0-9, a-f), '5' (0-9, a-v), and '6' (0-9, a-z, A-Z, "-", ",").
           ini_set('session.hash_bits_per_character', 5);
         
           // Force the session to only use cookies, not URL variables.
           ini_set('session.use_only_cookies', 1);
         
           // Get session cookie parameters 
           $cookieParams = session_get_cookie_params(); 
           // Set the parameters
           session_set_cookie_params($cookieParams["lifetime"], $cookieParams["path"], $cookieParams["domain"], $secure, $httponly); 
           // Change the session name 
           session_name($session_name);
           // Now we cat start the session
           session_start();
           // This line regenerates the session and delete the old one. 
           // It also generates a new encryption key in the database. 
           // session_regenerate_id(true);    
        }
        function open() {
            $this->db = new mysqli('localhost', 'maneschi_twc', 'k,={iJ5e}O!Q', $GLOBALS['DATABASE']);
            return true;
        }
        function close() {
           $this->db->close();
           return true;
        }
        function read($id) {
           if(!isset($this->read_stmt)) {
              $this->read_stmt = $this->db->prepare("SELECT data FROM sessions WHERE id = ? LIMIT 1");
           }
           $this->read_stmt->bind_param('s', $id);
           $this->read_stmt->execute();
           $this->read_stmt->store_result();
           $this->read_stmt->bind_result($data);
           $this->read_stmt->fetch();
           $key = $this->getkey($id);
           $data = $this->decrypt($data, $key);
           return $data;
        }

        function write($id, $data) {
           // Get unique key
           $key = $this->getkey($id);
           // Encrypt the data
           $data = $this->encrypt($data, $key);
         
           $time = time();
           if(!isset($this->w_stmt)) {
              $this->w_stmt = $this->db->prepare("REPLACE INTO sessions (id, set_time, data, session_key) VALUES (?, ?, ?, ?)");
           }
         
           $this->w_stmt->bind_param('siss', $id, $time, $data, $key);
           $this->w_stmt->execute();
           $sId = substr($id, 0,8);
           if ($GLOBALS['DEBUG_SESSION']) dLog("Updated $sId...");
           return true;
        }

        function destroy($id) {
           if(!isset($this->delete_stmt)) {
              $this->delete_stmt = $this->db->prepare("DELETE FROM sessions WHERE id = ?");
           }
           $this->delete_stmt->bind_param('s', $id);
           $this->delete_stmt->execute();
           if ($GLOBALS['DEBUG_SESSION']) dLog("Destroyed $id");
           return true;
        }

        function gc($max) {
           if(!isset($this->gc_stmt)) {
              $this->gc_stmt = $this->db->prepare("DELETE FROM sessions WHERE set_time < ?");
           }
           $old = time() - $max;
           $this->gc_stmt->bind_param('s', $old);
           $this->gc_stmt->execute();
           $this->gc_stmt->store_result();
           if ($GLOBALS['DEBUG_SESSION']) dLog("GCed {$this->gc_stmt->num_rows} (older than $max)");
           return true;
        }

        private function getkey($id) {
           if(!isset($this->key_stmt)) {
              $this->key_stmt = $this->db->prepare("SELECT session_key FROM sessions WHERE id = ? LIMIT 1");
           }
           $this->key_stmt->bind_param('s', $id);
           $this->key_stmt->execute();
           $this->key_stmt->store_result();
           if($this->key_stmt->num_rows == 1) { 
              $this->key_stmt->bind_result($key);
              $this->key_stmt->fetch();
              return $key;
           } else {
              $random_key = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));
              return $random_key;
           }
        }
        private function encrypt($data, $key) {
           $salt = 'cH!swe!retReGu7W6bEDRup7usuDUh9THeD2CHeGE*ewr4n39=E@rAsp7c-Ph@pH';
           $key = substr(hash('sha256', $salt.$key.$salt), 0, 32);
           $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
           $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
           $encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $data, MCRYPT_MODE_ECB, $iv));
           return $encrypted;
        }
        private function decrypt($data, $key) {
           $salt = 'cH!swe!retReGu7W6bEDRup7usuDUh9THeD2CHeGE*ewr4n39=E@rAsp7c-Ph@pH';
           $key = substr(hash('sha256', $salt.$key.$salt), 0, 32);
           $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
           $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
           $decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, base64_decode($data), MCRYPT_MODE_ECB, $iv);
           return $decrypted;
        }
    }

    $session = new session();
    // Set to true if using https
    $session->start_session('_s', false);

    function dLog($msg) {
        $now = date('d-M H:i:s');
        $alias = array_key_exists('alias', $_SESSION) ? $_SESSION['alias'] : 'NotLoggedIn';
        file_put_contents("/home/maneschi/logs/twc{$GLOBALS['version']}.log", "$now|$alias|$msg\n", FILE_APPEND | LOCK_EX);
    }

    function doLogin($email, $password) {
        global $session;
        $stmt = $session->db->prepare("SELECT id, alias, admin FROM users WHERE email = ? and `password` = PASSWORD(?) AND suspended = 0");
        $stmt->bind_param('ss', $email, $password);
        if (!$stmt->execute()) echo 'fail';
        $stmt->store_result();
        if($stmt->num_rows == 1) { 
            $stmt->bind_result($id, $alias, $admin);
            $stmt->fetch();
            $_SESSION['alias'] = $alias;
            $_SESSION['userId'] = $id;
            $_SESSION['email'] = $email;
            $_SESSION['admin'] = $admin;
            $stmt = $session->db->prepare("UPDATE users SET lastLogin = NOW() WHERE id = ?");
            $stmt->bind_param('i',  $id);
            $stmt->execute();
            return $id;
        } else {
            return "";
        }        
    }

    function getUserDetails($id) {
        global $session, $results;
        $result = array();
        $stmt = $session->db->prepare('SELECT alias, email, battles, victories, points, created, lastLogin, rankType, realName, admin FROM users WHERE users.id = ?');
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) dLog("fail - SELECT alias, email, battles, victories, points, created, lastLogin, rankType, realName, admin FROM users WHERE users.id = $id");
        $stmt->store_result();
        if ($stmt->num_rows == 1) {
            $stmt->bind_result($alias, $email, $battles, $victories, $points, $created, $lastLogin, $rankType, $realName, $admin);
            $stmt->fetch();
            $result['realName'] = $realName ? $realName : $alias;
            $result['email'] = $email;
            $result['alias'] = $alias;
            $result['since'] = strtotime($created);
            $result['years'] = 1 + (new DateTime(date('Y-m-d', $result['since'])))->diff(new DateTime())->y;
            $result['lastLogin'] = $lastLogin > '0000-00-00 00:00:00' ? strtotime($lastLogin) : '';
            $result['otherRibbons'] = array();
            $result['tournaments'] = array();
            $result['setPoints'] = $points;
            $result['setVictories'] = $victories;
            $result['setBattles'] = $battles;
            $result['admin'] = boolval($admin);
        }
        $stmt = $session->db->prepare('SELECT count(*), sum(if(result = 1, 1, 0)), sum(points) FROM userbattles WHERE userid = ? and result != 0');
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) echo 'fail';
        $stmt->store_result();
        $stmt->bind_result($battlesInc, $victoriesInc, $pointsInc);
        $stmt->fetch();
        $result['victories'] = $victories + $victoriesInc;
        $result['battles'] = $battles + $battlesInc;
        $result['points'] = $points + $pointsInc;

        foreach(array('play' => 'battles', 'win' => 'victories', 'years' => 'years', 'rank' => 'points') as $type => $score) {
            $ty = $type == 'rank' ? $rankType : $type;
            $stmt = $session->db->prepare("SELECT name, image FROM ribbons WHERE family = '$ty' AND level <= ? ORDER BY level DESC LIMIT 1");
            $stmt->bind_param('i', $result[$score]);
            if (!$stmt->execute()) dLog("fail - SELECT name FROM ribbons WHERE type = '$ty' AND level <= {$result[$score]} ORDER BY level DESC LIMIT 1");
            $stmt->store_result();
            $stmt->bind_result($ribbonName, $ribbonImage);
            if ($stmt->fetch()) {
                $result["{$type}RibbonName"] = $ribbonName;
                $result["{$type}RibbonImage"] = $ribbonImage;
            }
        }
        $stmt = $session->db->prepare("SELECT name, image FROM ribbons r JOIN userribbons ur ON ur.ribbonId = r.id WHERE ur.userId = ?");
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) dLog("fail - SELECT name, image FROM ribbons r JOIN userribbons ur ON ur.ribbonId = r.id WHERE ur.userId = $id");
        $stmt->store_result();
        $stmt->bind_result($ribbonName, $ribbonImage);
        while ($stmt->fetch()) 
            $result['otherRibbons'][$ribbonName] = $ribbonImage;

        $stmt = $session->db->prepare("SELECT t.id,name, level, url FROM tournamentusers tu JOIN tournaments t on tu.tournamentid = t.id JOIN tournamentawards ta ON tu.awardid = ta.id WHERE tu.userId = ? ORDER BY level");
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) dLog("fail - SELECT t.id,name, level, url FROM tournamentusers tu JOIN tournaments t on tu.tournamentid = t.id JOIN tournamentawards ta ON tu.awardid = ta.id WHERE tu.userId = $id ORDER BY level");
        $stmt->store_result();
        $stmt->bind_result($tournamentId, $tournamentName, $awardLevel, $url);
        while ($stmt->fetch()) {
            array_push($result['tournaments'], array('id' => $tournamentId, 'name' => $tournamentName, 'type' => $results[$awardLevel],'url' => $url));
        }

        return $result;
    }

    function outputRibbon($result, $family)
    {
        if (preg_match('/^(win|play|rank|years)$/', $family)) {
            if (array_key_exists($family ."RibbonName", $result)) {
?>
                <img class="Ribbon" src="/images/<?= $result[$family.'RibbonImage'] ?>.png" title="<?= $result[$family .'RibbonName'] ?>">
<?
            }
        }
        else
        {
            if (array_key_exists($family, $result['otherRibbons'])) {
?>
                <img class="Ribbon" src="/images/<?= $result['otherRibbons'][$family] ?>.png" title="<?= $family ?>">
<?
            }
        }
    }
    function outputUserDetails($id) {
        global $session, $results;
        $user = getUserDetails($id);
?>
        <div class="UserDetails">
            <h2>Details for <?= $user['alias'] ?></h2>
            <table>
                <tr><th>Name:</th><td><?= $user['realName'] ?></td></tr>
                <tr><th>Email:</th><td><a href="mailto:<?= $user['email'] ?>"><?= $user['email'] ?></a></td></tr>
                <tr><th>Points:</th><td><?= $user['points'] ?> points</td><th>
                <tr><th>Rank:</th><td class="Rank"><? outputRibbon($user, 'rank') ?><span><?= array_key_exists('rankRibbonName', $user) ? $user['rankRibbonName'] : '' ?></span></td></tr>
                <tr><th>Since:</th><td><? outputRibbon($user, 'years') ?><span><?= date('d/M/Y', $user['since']) ?></span></td><th>
                <tr><th>Played:</th><td><? outputRibbon($user, 'play') ?><span><?= $user['battles'] ?> battles</span></td><th>
                <tr><th>Gained:</th><td><? outputRibbon($user, 'win') ?><span><?= $user['victories'] ?> victories</span></td><th>
                <tr><th>Tournaments:</th><td class="Tournaments">
<?
        foreach($user['tournaments'] as $rec) {
?>
                    <div class="Award">
                        <img src="<?= $rec['url'] ?>">
                        <span><?= $rec['type'] ?></span>
                        <a href="/tournament.php?id=<?= $rec['id'] ?>"><?= $rec['name'] ?></a>
                    </div>
<? } ?>
                </td></tr>
                <tr><th>Ribbons:</th><td class="Ribbons">
<?
        foreach($user['otherRibbons'] as $name => $image) {
?>
                    <div class="RibbonWrapper">
                        <img class="Ribbon" src="/images/<?= $image ?>.png" title="<?= $name ?>"><br>
                        <span><?= $name ?></span>
                    </div>
<? } ?>
                </td></tr>
                <tr><th>Last login:</th><td><?= $user['lastLogin'] ? date('d/M/Y h:m a', $user['lastLogin']) : '' ?></td><th>
            </table>
        </div>
        <div class="CurrentBattles">
            <h2>Current Battles</h2>
            <table class="sortable">
                <tr><th>Start</th><th>Name</th></tr>
<?
        $stmt = $session->db->prepare('SELECT b.id, b.started, b.name, gt.name FROM userbattles ub JOIN battles b ON ub.battleId = b.id LEFT JOIN gametypes gt ON b.typeid = gt.id WHERE userid = ? and b.state = 1 ORDER BY b.started');
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) dLog("fail- SELECT b.id, b.started, b.name, gt.name FROM userbattles ub JOIN battles b ON ub.battleId = b.id LEFT JOIN gametypes gt ON b.typeid = gt.id WHERE userid = $id and b.state = 1 ORDER BY b.started");
        $stmt->store_result();
        $stmt->bind_result($battleId, $started, $name, $type);
        while ($stmt->fetch())
        {
            if (!$type) $type = '';
?>
            <tr><td class="Date" sorttable_customkey="<?= date('Y-m-d', strtotime($started)) ?>"><?= date('', strtotime($started)); ?></td><td class="Name"><a href="battle.php?id=<?= $battleId ?>"><?= "$type $name" ?></a></td></tr>
<?
        }
?>

            </table>
        </div>
        <div class="CompletedBattles">
            <h2>Completed Battles</h2>
            <table class="sortable">
                <tr><th>Start</th><th>End</th><th>Name</th><th>Result</th><th>Points</th></tr>
<?
        $stmt = $session->db->prepare('SELECT b.id, b.started, b.ended, b.name, gt.name, ub.result, ub.points FROM userbattles ub JOIN battles b ON ub.battleId = b.id LEFT JOIN gametypes gt ON b.typeid = gt.id WHERE userid = ? and b.state=2 ORDER BY b.ended DESC');
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) dLog("fail - SELECT b.id, b.started, b.ended, b.name, gt.name, ub.result, ub.points FROM userbattles ub JOIN battles b ON ub.battleId = b.id LEFT JOIN gametypes gt ON b.typeid = gt.id WHERE userid = $id and b.state=2 ORDER BY b.ended DESC");
        $stmt->store_result();
        $stmt->bind_result($battleId, $started, $ended, $name, $type, $result, $points);
        while ($stmt->fetch())
        {
            if (!$type) $type = '';
?>
            <tr>
                <td class="Date" sorttable_customkey="<?= date('Y-m-d', strtotime($started)) ?>"><?= date('d/M/Y', strtotime($started)); ?></td>
                <td class="Date" sorttable_customkey="<?= date('Y-m-d', strtotime($ended)) ?>"><?= date('d/M/Y', strtotime($ended)); ?></td>
                <td class="Name"><a href="battle.php?id=<?= $battleId ?>"><?= "$type $name" ?></a></td>
                <td class="Result"  sorttable_customkey="<?=$result ?>"><?= $results[$result] ?></td>
                <td class="Points"><?= $points ?></td>
            </tr>
<?
        }
?>
            </table>
        </div>
<?
    }


function htmlHeader($title)
{
?>
<!DOCTYPE html>
<html>
    <head>
        <title>TWC OoR - <?= $title ?></title>
        <link rel="SHORTCUT ICON" href="../images/favicon.ico">
        <link rel="stylesheet" type="text/css" href="/css/pikaday.css">
        <link rel="stylesheet" type="text/css" href="/css/main.css">
        <script src="/js/main.js"></script>
        <script src="/js/pikaday.js"></script>
        <script src="/js/sorttable.js"></script>
    </head>
    <body onload="">
        <div class="Banner">
            <img src="/images/Logo.jpg" width="64">
            <div class="Title"><?= $GLOBALS['TITLE'] ?></div>
            <div class="Subtitle">Office of Records</div>
        </div>
<?
}
function htmlFooter() {
?>
        <div class="PushUp">&nbsp;</div>
        <div class="Footer">TWC - Play by Email (PBeM) Strategy Wargaming Club - Battle on!..</div>
    </body>
</html>
<?
}
function getToken($length){
     $token = "";
     $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
     $codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
     $codeAlphabet.= "0123456789";
     $max = strlen($codeAlphabet); // edited

    for ($i=0; $i < $length; $i++) {
        $token .= $codeAlphabet[rand(0, $max-1)];
    }

    return $token;
}
function getAllUsers() {
    global $session;
    $users = array();
    $stmt = $session->db->prepare('SELECT id, alias, email FROM users ORDER BY alias');
    if (!$stmt->execute()) dLog("fail - SELECT users, alias, email FROM users  ORDER BY alias");
    $stmt->store_result();
    $stmt->bind_result($userId, $alias, $email);
    while ($stmt->fetch())
        array_push($users, array(
            'id' => $userId,
            'alias' => $alias,
            'email' => $email,
        ));
    return $users;
}
function sendMail($recipients, $subject, $message) {
    global $adminEmail;
    if ($GLOBALS['TESTING'])
        foreach($recipients as $index => $email)
            if (array_search($email, explode('.','nlancier@gmail.com,pythonmagus@redwaratah.com,lordlau1@gmail.com,nedfn1@comcast.net')) !== FALSE)
                $recipients[$index] = str_replace('@', '_', $email) . "@redwaratah.com";
    mail(join(",", $recipients), $subject, $message, "From: Club Admin <$adminEmail>");
}

$battleStates = array(
    0 => 'Not started',
    1 => 'Started',
    2 => 'Completed',
    3 => 'Cancelled',
    4 => 'Suspended'
);

$results = array(
    0 => '',
    1 => 'First',
    2 => 'Second',
    3 => 'Third',
    4 => 'Fourth',
    5 => 'Fifth',
    6 => 'Sixth',
    7 => 'Seventh',
    8 => 'Eighth',
    9 => 'Ninth',
    90 => 'Participated',
    99 => 'Lost'
);

$tournamentStates = array(
    0 => 'Recruiting',
    1 => 'Underway',
    2 => 'Complete'
);
$tournamentTypes = array(
    0 => 'Elimination',
    1 => 'Pyramid'
    /* 2 => 'Round Robin' */
);
?>