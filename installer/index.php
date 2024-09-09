<?php

ini_set('display_errors', 1);

define('INSTALL_PATH', realpath(__DIR__ . '/../').'/');

require INSTALL_PATH . 'program/include/iniset.php';

if (function_exists('session_start')) {
    session_start();
}

$RCI = rcmail_install::get_instance();
$RCI->load_config();

if (isset($_GET['_getconfig'])) {
    $filename = 'config.inc.php';
    if (!empty($_SESSION['config']) && $_GET['_getconfig'] == 2) {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;
        @unlink($path);
        file_put_contents($path, $_SESSION['config']);
        exit;
    }

    if (!empty($_SESSION['config'])) {
        header('Content-type: text/plain');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        echo $_SESSION['config'];
        exit;
    }

    header('HTTP/1.0 404 Not found');
    die("The requested configuration was not found. Please run the installer from the beginning.");
}

if (
    $RCI->configured
    && !empty($_GET['_mergeconfig'])
    && ($RCI->getprop('enable_installer') || !empty($_SESSION['allowinstaller']))
) {
    $filename = 'config.inc.php';

    header('Content-type: text/plain');
    header('Content-Disposition: attachment; filename="'.$filename.'"');

    $RCI->merge_config();
    echo $RCI->create_config();
    exit;
}

// go to 'check env' step if we have a local configuration
if ($RCI->configured && empty($_REQUEST['_step'])) {
    header("Location: ./?_step=1");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <link rel="stylesheet" type="text/css" href="styles.css" /> -->
    <link rel="stylesheet" type="text/css" href="./index.css" />
    <link rel="stylesheet" type="text/css" href="./bootstrap/css/bootstrap.min.css" />
    <title>Webmail Installer</title>
</head>
<body>
    
    <div id="banner">
        <h1>Webmail <small>安装脚本</small></h1>
    </div>
    <div id="topnav">
    <a href="/skins/hnuu/docs">说明文档</a>
    </div>

    <div id="content">
        <ul id="progress">
            <?php
            $include_steps = [
                1 => './check.php',
                2 => './config.php',
                3 => './test.php',
            ];

            if (!in_array($RCI->step, array_keys($include_steps))) {
                $RCI->step = 1;
            }

            foreach (['检查环境', '创建配置文件', '检查配置文件'] as $i => $item) {
                $j = $i + 1;
                $link = ($RCI->step >= $j || $RCI->configured) ? '<a href="./index.php?_step='.$j.'">' . rcube::Q($item) . '</a>' : rcube::Q($item);
                printf('<li class="step%d%s">%s</li>', $j+1, $RCI->step > $j ? ' passed' : ($RCI->step == $j ? ' current' : ''), $link);
            }
            ?>
        </ul>

        <?php

            include $include_steps[$RCI->step];

        ?>
    </div>


    <div id="footer">


        Webmail &copy;Ganxy | 2024 <br>


        <small>Powered by <a href="https://roundcube.net/">RoundCube</a></small>

    </div>
</body>
</html>