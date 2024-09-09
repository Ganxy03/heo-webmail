<?php

if (!class_exists('rcmail_install', false) || !isset($RCI)) {
    die("Not allowed! Please open installer/index.php instead.");
}

$required_php_exts = [
    'PCRE'      => 'pcre',
    'DOM'       => 'dom',
    'Session'   => 'session',
    'XML'       => 'xml',
    'Intl'      => 'intl',
    'JSON'      => 'json',
    'PDO'       => 'PDO',
    'Multibyte' => 'mbstring',
    'OpenSSL'   => 'openssl',
    'Filter'    => 'filter',
    'Ctype'     => 'ctype',
];

$optional_php_exts = [
    'cURL'      => 'curl',
    'FileInfo'  => 'fileinfo',
    'Exif'      => 'exif',
    'Iconv'     => 'iconv',
    'LDAP'      => 'ldap',
    'GD'        => 'gd',
    'Imagick'   => 'imagick',
    'XMLWriter' => 'xmlwriter',
    'Zip'       => 'zip',
];

$required_libs = [
    'PEAR'      => 'pear.php.net',
    'Auth_SASL' => 'pear.php.net',
    'Net_SMTP'  => 'pear.php.net',
    'Mail_mime' => 'pear.php.net',
    'GuzzleHttp\Client' => 'github.com/guzzle/guzzle',
];

$optional_libs = [
    'Net_LDAP3' => 'git.kolab.org',
];

$ini_checks = [
    'file_uploads'            => 1,
    'session.auto_start'      => 0,
    'mbstring.func_overload'  => 0,
    'suhosin.session.encrypt' => 0,
];

$optional_checks = [
    'date.timezone' => '-VALID-',
];

$source_urls = [
    'cURL'      => 'https://www.php.net/manual/en/book.curl.php',
    'Sockets'   => 'https://www.php.net/manual/en/book.sockets.php',
    'Session'   => 'https://www.php.net/manual/en/book.session.php',
    'PCRE'      => 'https://www.php.net/manual/en/book.pcre.php',
    'FileInfo'  => 'https://www.php.net/manual/en/book.fileinfo.php',
    'Multibyte' => 'https://www.php.net/manual/en/book.mbstring.php',
    'OpenSSL'   => 'https://www.php.net/manual/en/book.openssl.php',
    'JSON'      => 'https://www.php.net/manual/en/book.json.php',
    'DOM'       => 'https://www.php.net/manual/en/book.dom.php',
    'Iconv'     => 'https://www.php.net/manual/en/book.iconv.php',
    'Intl'      => 'https://www.php.net/manual/en/book.intl.php',
    'Exif'      => 'https://www.php.net/manual/en/book.exif.php',
    'oci8'      => 'https://www.php.net/manual/en/book.oci8.php',
    'PDO'       => 'https://www.php.net/manual/en/book.pdo.php',
    'LDAP'      => 'https://www.php.net/manual/en/book.ldap.php',
    'GD'        => 'https://www.php.net/manual/en/book.image.php',
    'Imagick'   => 'https://www.php.net/manual/en/book.imagick.php',
    'XML'       => 'https://www.php.net/manual/en/book.xml.php',
    'XMLWriter' => 'https://www.php.net/manual/en/book.xmlwriter.php',
    'Zip'       => 'https://www.php.net/manual/en/book.zip.php',
    'Filter'    => 'https://www.php.net/manual/en/book.filter.php',
    'Ctype'     => 'https://www.php.net/manual/en/book.ctype.php',
    'pdo_mysql'   => 'https://www.php.net/manual/en/ref.pdo-mysql.php',
    'pdo_pgsql'   => 'https://www.php.net/manual/en/ref.pdo-pgsql.php',
    'pdo_sqlite'  => 'https://www.php.net/manual/en/ref.pdo-sqlite.php',
    'pdo_sqlite2' => 'https://www.php.net/manual/en/ref.pdo-sqlite.php',
    'pdo_sqlsrv'  => 'https://www.php.net/manual/en/ref.pdo-sqlsrv.php',
    'pdo_dblib'   => 'https://www.php.net/manual/en/ref.pdo-dblib.php',
    'PEAR'      => 'https://pear.php.net',
    'Net_SMTP'  => 'https://pear.php.net/package/Net_SMTP',
    'Mail_mime' => 'https://pear.php.net/package/Mail_mime',
    'Net_LDAP3' => 'https://git.kolab.org/diffusion/PNL',
];

?>

<!-- <link rel="stylesheet" type="text/css" href="./bootstrap/css/bootstrap.min.css"> -->
<form action="index.php" method="get">

<?php
echo '<input type="hidden" name="_step" value="' . ($RCI->configured ? 3 : 2) . '" />';
?>

<h3>PHP环境</h3>

<?php
define('MIN_PHP_VERSION', '7.3.0');
$phpVersionStatus = '未知';
if (version_compare(PHP_VERSION, MIN_PHP_VERSION, '>=')) {
    // $RCI->pass('版本', 'PHP ' . PHP_VERSION . ' 已检测');
    $phpVersionStatus = '<span style="color:green;">通过</span>';
}
else {
    $RCI->fail('Version', 'PHP Version ' . MIN_PHP_VERSION . ' or greater is required ' . PHP_VERSION . ' detected');
    $phpVersionStatus = '<span style="color:red;">失败</span>';
}
?>
<table class="table table-striped table-bordered table-hover">
    <thead>
        <tr>
            <!-- <th>序号</th> -->
            <th>名称</th>
            <th>版本</th>
            <th>状态</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <!-- <td>1</td> -->
            <td>PHP</td>
            <td><?= PHP_VERSION ?></td>
            <td><?= $phpVersionStatus ?></td>
        </tr>
        <!-- 你可以在这里添加更多的 <tr> 来显示其他行 -->
    </tbody>
</table>


<h3>PHP扩展</h3>
<p class="hint">以下模块/扩展是 <em>必须</em> 配置为webmail服务:</p>


<?php
// 初始化一个数组来存储扩展的状态
$extensions_status = [];

// get extensions location
$ext_dir = ini_get('extension_dir');

$prefix = PHP_SHLIB_SUFFIX === 'dll' ? 'php_' : '';
foreach ($required_php_exts as $name => $ext) {
    $status = extension_loaded($ext) ? '<span style="color:green;">已安装</span>' : '<span style="color:red;">未安装</span>';
    $action = extension_loaded($ext) ? '' : '如需配置。请在php.ini内添加';
    $extensions_status[] = [
        'name' => $name,
        'status' => $status,
        'action' => $action,
        'url' => isset($source_urls[$name]) ? $source_urls[$name] : '#'
    ];
}
?>
<table class="table table-striped table-bordered table-hover">
    <thead>
        <tr>
            <th>扩展名称</th>
            <th>状态</th>
            <th>说明</th>
            <th>更多信息</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($extensions_status as $ext) { ?>
        <tr>
            <td><?= htmlspecialchars($ext['name']) ?></td>
            <td><?= $ext['status'] ?></td>
            <td><?= $ext['action'] ?></td>
            <td><a href="<?= htmlspecialchars($ext['url']) ?>">查看详情</a></td>
        </tr>
        <?php } ?>
    </tbody>
</table>

<p class="hint">下面几个扩展是 <em>可选的</em> 并建议配置以获取最佳性能：</p>

<?php
// 初始化一个数组来存储扩展的状态
$optional_extensions_status = [];

// get extensions location
$ext_dir = ini_get('extension_dir');

$prefix = PHP_SHLIB_SUFFIX === 'dll' ? 'php_' : '';
foreach ($optional_php_exts as $name => $ext) {
    $status = extension_loaded($ext) ? '<span style="color:green;">已安装</span>' : '<span style="color:orange;">未安装</span>';
    $action = extension_loaded($ext) ? '' : '如需配置。请在php.ini内添加';
    $optional_extensions_status[] = [
        'name' => $name,
        'status' => $status,
        'action' => $action,
        'url' => isset($source_urls[$name]) ? $source_urls[$name] : '#'
    ];
}
?>
<table class="table table-striped table-bordered table-hover">
    <thead>
        <tr>
            <th>可选扩展名称</th>
            <th>状态</th>
            <th>说明</th>
            <th>更多信息</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($optional_extensions_status as $ext) { ?>
        <tr>
            <td><?= htmlspecialchars($ext['name']) ?></td>
            <td><?= $ext['status'] ?></td>
            <td><?= $ext['action'] ?></td>
            <td><a href="<?= htmlspecialchars($ext['url']) ?>">查看详情</a></td>
        </tr>
        <?php } ?>
    </tbody>
</table>


<h3>检查可用数据库</h3>
<p class="hint">检查安装了哪些受支持的扩展，至少其中一个是必需的。</p>




<!-- $prefix = PHP_SHLIB_SUFFIX === 'dll' ? 'php_' : '';
foreach ($RCI->supported_dbs as $database => $ext) {
    if (extension_loaded($ext)) {
        $RCI->pass($database);
        $found_db_driver = true;
    }
    else {
        $_ext = $ext_dir . '/' . $prefix . $ext . '.' . PHP_SHLIB_SUFFIX;
        $msg = @is_readable($_ext) ? '如需配置. 请在 php.ini 内添加' : '';
        $RCI->na($database, $msg, $source_urls[$ext]);
    }
    echo '<br />';
}
if (empty($found_db_driver)) {
  $RCI->failures++;
} -->
<?php
// 初始化一个数组来存储数据库驱动的状态
$db_drivers_status = [];

// get extensions location
$ext_dir = ini_get('extension_dir');

$prefix = PHP_SHLIB_SUFFIX === 'dll' ? 'php_' : '';
$found_db_driver = false;
foreach ($RCI->supported_dbs as $database => $ext) {
    $status = extension_loaded($ext) ? '<span style="color:green;">已安装</span>' : '<span style="color:orange;">未安装</span>';
    $action = extension_loaded($ext) ? '' : '如需配置。请在php.ini内添加';
    $db_drivers_status[] = [
        'name' => $database,
        'status' => $status,
        'action' => $action,
        'url' => isset($source_urls[$ext]) ? $source_urls[$ext] : '#'
    ];
    if (extension_loaded($ext)) {
        $found_db_driver = true;
    }
}

// 如果没有找到任何数据库驱动，则记录失败
if (empty($found_db_driver)) {
    $RCI->failures++;
}
?>
<table class="table table-striped table-bordered table-hover">
    <thead>
        <tr>
            <th>数据库驱动</th>
            <th>状态</th>
            <th>说明</th>
            <th>更多信息</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($db_drivers_status as $driver) { ?>
        <tr>
            <td><?= htmlspecialchars($driver['name']) ?></td>
            <td><?= $driver['status'] ?></td>
            <td><?= $driver['action'] ?></td>
            <td><a href="<?= htmlspecialchars($driver['url']) ?>">查看详情</a></td>
        </tr>
        <?php } ?>
    </tbody>
</table>


<h3>检查所需的第三方库</h3>
<p class="hint">这还检查包含路径是否设置正确。</p>
<?php
// 初始化一个数组来存储第三方库的状态
$required_libs_status = [];
$optional_libs_status = [];

foreach ($required_libs as $classname => $vendor) {
    $status = class_exists($classname) ? '<span style="color:green;">已安装</span>' : '<span style="color:red;">未安装</span>';
	$action = extension_loaded($ext) ? '' : '如需配置。请在php.ini内添加';
    $required_libs_status[] = [
        'name' => $classname,
        'status' => $status,
        'action' => $action,
        'url' => isset($source_urls[$classname]) ? $source_urls[$classname] : '#'
    ];
}

foreach ($optional_libs as $classname => $action) {
    $status = class_exists($classname) ? '<span style="color:green;">已安装</span>' : '<span style="color:orange;">未安装</span>';
    $optional_libs_status[] = [
        'name' => $classname,
        'status' => $status,
        'action' => $action,
        'url' => isset($source_urls[$classname]) ? $source_urls[$classname] : '#'
    ];
}
?>
<table class="table table-striped table-bordered table-hover">
    <thead>
        <tr>
            <th>必需第三方库</th>
            <th>状态</th>
            <th>说明</th>
            <th>更多信息</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($required_libs_status as $lib) { ?>
        <tr>
            <td><?= htmlspecialchars($lib['name']) ?></td>
            <td><?= $lib['status'] ?></td>
            <td><?= htmlspecialchars($lib['vendor']) ?></td>
            <td><a href="<?= htmlspecialchars($lib['url']) ?>">查看详情</a></td>
        </tr>
        <?php } ?>
    </tbody>
</table>

<table class="table table-striped table-bordered table-hover">
    <thead>
        <tr>
            <th>可选第三方库</th>
            <th>状态</th>
            <th>说明</th>
            <th>更多信息</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($optional_libs_status as $lib) { ?>
        <tr>
            <td><?= htmlspecialchars($lib['name']) ?></td>
            <td><?= $lib['status'] ?></td>
            <td><?= htmlspecialchars($lib['vendor']) ?></td>
            <td><a href="<?= htmlspecialchars($lib['url']) ?>">查看详情</a></td>
        </tr>
        <?php } ?>
    </tbody>
</table>




<h3>检查 php.ini/.htaccess 的设置</h3>
<p class="hint">以下设置 <em>必须</em> 配置为webmail服务:</p>

<?php
// 初始化一个数组来存储INI设置的状态
$ini_checks_status = [];

// 遍历INI设置检查
foreach ($ini_checks as $var => $val) {
    $status = ini_get($var);
    $action = '';
    if ($val === '-NOTEMPTY-') {
        if (empty($status)) {
            $RCI->fail($var, "empty value detected");
            $status = '<span style="color:red;">空值</span>';
            $action = "检测到空值，应有有效值";
        }
        else {
            $RCI->pass($var);
            $status = '<span style="color:green;">非空</span>';
        }
    }
    else if (filter_var($status, FILTER_VALIDATE_BOOLEAN) == $val) {
        // $RCI->pass($var);
        $status = '<span style="color:green;">通过</span>';
    }
    else {
        $RCI->fail($var, "is '$status', should be '$val'");
        $status = '<span style="color:red;">错误</span>';
        $action = "当前值 '$status'，应为 '$val'";
    }
    $ini_checks_status[] = [
        'name' => $var,
        'status' => $status,
        'action' => $action
    ];
}

// 显示INI设置状态
echo '<table class="table table-striped table-bordered table-hover">';
echo '<thead><tr><th>INI设置</th><th>状态</th><th>说明</th></tr></thead>';
echo '<tbody>';
foreach ($ini_checks_status as $check) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($check['name']) . '</td>';
    echo '<td>' . $check['status'] . '</td>';
    echo '<td>' . $check['action'] . '</td>';
    echo '</tr>';
}
echo '</tbody>';
echo '</table>';
?>



<p class="hint">以下设置 <em>可选</em> 并建议:</p>
<?php
// 初始化一个数组来存储可选检查的状态
$optional_checks_status = [];

// 遍历可选检查
foreach ($optional_checks as $var => $val) {
    $status = ini_get($var);
    $action = '';
    if ($val === '-NOTEMPTY-') {
        if (empty($status)) {
            $RCI->optfail($var, "Could be set");
            $status = '<span style="color:orange;">未设置</span>';
            $action = "建议设置";
        }
        else {
            $RCI->pass($var);
            $status = '<span style="color:green;">已设置</span>';
        }
    }
    elseif ($val === '-VALID-') {
        if ($var == 'date.timezone') {
            try {
                $tz = new DateTimeZone($status);
                // $RCI->pass($var);
                $status = '<span style="color:green;">通过</span>';
            }
            catch (Exception $e) {
                $RCI->optfail($var, empty($status) ? "not set" : "invalid value detected: $status");
                $status = '<span style="color:red;">无效</span>';
                $action = "无效值：" . htmlspecialchars($status);
            }
        }
        else {
            $RCI->pass($var);
            $status = '<span style="color:green;">通过</span>';
        }
    }
    else if (filter_var($status, FILTER_VALIDATE_BOOLEAN) == $val) {
        $RCI->pass($var);
        $status = '<span style="color:green;">通过</span>';
    }
    else {
        $RCI->optfail($var, "is '$status', could be '$val'");
        $status = '<span style="color:orange;">建议修改</span>';
        $action = "当前值 '$status'，建议 '$val'";
    }
    $optional_checks_status[] = [
        'name' => $var,
        'status' => $status,
        'action' => $action
    ];
}

// 显示可选检查状态
echo '<table class="table table-striped table-bordered table-hover">';
echo '<thead><tr><th>可选检查</th><th>状态</th><th>说明</th></tr></thead>';
echo '<tbody>';
foreach ($optional_checks_status as $check) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($check['name']) . '</td>';
    echo '<td>' . $check['status'] . '</td>';
    echo '<td>' . $check['action'] . '</td>';
    echo '</tr>';
}
echo '</tbody>';
echo '</table>';
?>


<?php

if ($RCI->failures) {
    echo '<p class="warning">很抱歉，您的服务器不符合webMail的要求!<br />
            请根据以上检查结果安装缺失的模块或修复php.ini设置。<br />
            提示:只有检查显示<span class="fail">不 OK</span>需要修复。</p>';
}
echo '<p><br /><input class="btn btn-success" type="submit" value="下一步" ' . ($RCI->failures ? 'disabled' : '') . ' /></p>';

?>
</form>