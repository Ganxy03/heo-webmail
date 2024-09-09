<?php
if (!class_exists('rcmail_install', false) || !isset($RCI)) {
    die("没有权限!请打开installer/index.php。");
}

?>

<h3>检查配置文件</h3>

<fieldset>
    <!-- <legend>配置文件检查</legend> -->
    <table class="table table-noborder">
        <colgroup>
            <col class="col-xs-2">
            <col class="col-xs-7">
        </colgroup>
        <thead>
            <tr>
                <th colspan="2">配置文件状态</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>默认配置文件</td>
                <td>
                    <?php
                    if ($read_config = is_readable(RCUBE_CONFIG_DIR . 'defaults.inc.php')) {
                        $config = $RCI->load_config_file(RCUBE_CONFIG_DIR . 'defaults.inc.php');
                        if (!empty($config)) {
                            echo '<span style="color:green;">配置文件已通过</span>';
                        } else {
                            echo '<span style="color:red;">配置文件存在语法错误</span>';
                        }
                    } else {
                        echo '<span style="color:red;">默认配置文件无法读取</span>';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td>自定义配置文件</td>
                <td>
                    <?php
                    if ($read_config = is_readable(RCUBE_CONFIG_DIR . 'config.inc.php')) {
                        $config = $RCI->load_config_file(RCUBE_CONFIG_DIR . 'config.inc.php');
                        if (!empty($config)) {
                            echo '<span style="color:green;">配置文件已通过</span>';
                        } else {
                            echo '<span style="color:red;">配置文件存在语法错误</span>';
                        }
                    } else {
                        echo '<span style="color:red;">配置文件无法读取。请确保已创建配置文件。</span>';
                    }
                    ?>
                </td>
            </tr>
            <!-- Additional rows for messages, dependencies, etc. would be added here in a similar fashion -->
        </tbody>
    </table>
</fieldset>


<fieldset>
    <legend>目录写权限检查</legend>
    <table class="table table-noborder">
        <colgroup>
            <col class="col-xs-2">
            <col class="col-xs-7">
        </colgroup>
        <thead>
            <tr>
                <th>目录</th>
                <th>写权限</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $dirs = [];
            $dirs[] = !empty($RCI->config['temp_dir']) ? $RCI->config['temp_dir'] : 'temp';
            if ($RCI->config['log_driver'] != 'syslog') {
                $dirs[] = $RCI->config['log_dir'] ? $RCI->config['log_dir'] : 'logs';
            }

            foreach ($dirs as $dir) {
                $dirpath = rcube_utils::is_absolute_path($dir) ? $dir : INSTALL_PATH . $dir;
                $writable = is_writable(realpath($dirpath));
                $status = $writable ? '<span style="color:green;">可写</span>' : '<span style="color:red;">不可写</span>';
                echo '<tr>';
                echo '<td>' . htmlspecialchars($dir) . '</td>';
                echo '<td>' . $status . '</td>';
                echo '</tr>';
                if (!$writable) {
                    // $RCI->fail($dir, 'not writeable for the webserver');
                }
            }
            ?>
        </tbody>
    </table>
    <?php
    if (empty($pass)) {
        echo '<p class="hint">使用 <tt>chmod</tt> 或 <tt>chown</tt> 命令授予Web服务器写权限。</p>';
    }
    ?>
</fieldset>

<fieldset>
    <legend>数据库配置检查</legend>
    <h3>Check DB config</h3>
    <table class="table table-noborder">
        <colgroup>
            <col class="col-xs-2">
            <col class="col-xs-7">
        </colgroup>
        <tbody>
            <tr>
                <td>数据库连接状态</td>
                <td>
                    <?php
                    $db_working = false;
                    if ($RCI->configured) {
                        if (!empty($RCI->config['db_dsnw'])) {
                            $DB = rcube_db::factory($RCI->config['db_dsnw'], '', false);
                            $DB->set_debug((bool)$RCI->config['sql_debug']);
                            $DB->db_connect('w');

                            if (!($db_error_msg = $DB->is_error())) {
                                $RCI->pass('DSN (write)');
                                echo '<br />';
                                $db_working = true;
                            }
                            else {
                                $RCI->fail('DSN (write)', $db_error_msg);
                                echo '<p class="hint">Make sure that the configured database exists and that the user has write privileges<br />';
                                echo 'DSN: ' . rcube::Q($RCI->config['db_dsnw']) . '</p>';
                            }
                        }
                        else {
                            $RCI->fail('DSN (write)', 'not set');
                        }
                    }
                    else {
                        $RCI->fail('DSN (write)', 'Could not read config file');
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td>数据库初始化</td>
                <td>
                    <?php
                    // initialize db with schema found in /SQL/*
                    if ($db_working && !empty($_POST['initdb'])) {
                        if (!$RCI->init_db($DB)) {
                            $db_working = false;
                            echo '<p class="warning">Please try to initialize the database manually as described in the INSTALL guide. Make sure that the configured database exists and that the user as write privileges</p>';
                        }
                    }
                    else if ($db_working && !empty($_POST['updatedb'])) {
                        if (!$RCI->update_db($_POST['version'])) {
                            echo '<p class="warning">Database schema update failed.</p>';
                        }
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td>数据库测试</td>
                <td>
                    <?php
                    // test database
                    if ($db_working) {
                        $db_read = $DB->query("SELECT count(*) FROM " . $DB->quote_identifier($RCI->config['db_prefix'] . 'users'));
                        if ($DB->is_error()) {
                            $RCI->fail('DB Schema', "Database not initialized");
                            echo '<form action="index.php?_step=3" method="post">'
                                . '<p><input type="submit" name="initdb" value="Initialize database" /></p>'
                                . '</form>';

                            $db_working = false;
                        }
                        else if ($err = $RCI->db_schema_check($DB, $update = !empty($_POST['updatedb']))) {
                            $RCI->fail('DB Schema', "Database schema differs");
                            echo '<ul style="margin:0"><li>' . join("</li>\n<li>", $err) . "</li></ul>";

                            $select = $RCI->versions_select(['name' => 'version']);
                            $select->add('0.9 or newer', '');

                            echo '<form action="index.php?_step=3" method="post">'
                                . '<p class="suggestion">You should run the update queries to get the schema fixed.'
                                . '<br/><br/>Version to update from: ' . $select->show('')
                                . '&nbsp;<input type="submit" name="updatedb" value="Update" /></p>'
                                . '</form>';

                            $db_working = false;
                        }
                        else {
                            $RCI->pass('DB Schema');
                            echo '<br />';
                        }
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td>更多数据库测试</td>
                <td>
                    <?php
                    // more database tests
                    if ($db_working) {
                        // Using transactions to workaround SQLite bug (#7064)
                        if ($DB->db_provider == 'sqlite') {
                            $DB->startTransaction();
                        }

                        // write test
                        $insert_id = md5(uniqid());
                        $db_write = $DB->query("INSERT INTO " . $DB->quote_identifier($RCI->config['db_prefix'] . 'session')
                            . " (`sess_id`, `changed`, `ip`, `vars`) VALUES (?, ".$DB->now().", '127.0.0.1', 'foo')", $insert_id);

                        if ($db_write) {
                            $RCI->pass('DB Write');
                            $DB->query("DELETE FROM " . $DB->quote_identifier($RCI->config['db_prefix'] . 'session')
                                . " WHERE `sess_id` = ?", $insert_id);
                        }
                        else {
                            $RCI->fail('DB Write', $RCI->get_error());
                        }
                        echo '<br />';

                        // Transaction end
                        if ($DB->db_provider == 'sqlite') {
                            $DB->rollbackTransaction();
                        }

                        // check timezone settings
                        $tz_db = 'SELECT ' . $DB->unixtimestamp($DB->now()) . ' AS tz_db';
                        $tz_db = $DB->query($tz_db);
                        $tz_db = $DB->fetch_assoc($tz_db);
                        $tz_db = (int) $tz_db['tz_db'];
                        $tz_local = (int) time();
                        $tz_diff  = $tz_local - $tz_db;

                        // sometimes db and web servers are on separate hosts, so allow a 30 minutes delta
                        if (abs($tz_diff) > 1800) {
                            $RCI->fail('DB Time', "Database time differs {$tz_diff}s from PHP time");
                        }
                        else {
                            $RCI->pass('DB Time');
                        }
                    }
                    ?>
                </td>
            </tr>
        </tbody>
    </table>
</fieldset>


<fieldset>
    <legend>文件类型检测测试</legend>
    <table class="table table-noborder">
        <colgroup>
            <col class="col-xs-2">
            <col class="col-xs-7">
        </colgroup>
        <tbody>
            <tr>
                <td>MIME类型检测</td>
                <td>
                    <?php
                    if ($errors = $RCI->check_mime_detection()) {
                        $RCI->fail('Fileinfo/mime_content_type configuration');
                        if (!empty($RCI->config['mime_magic'])) {
                            echo '<p class="hint">Try setting the <tt>mime_magic</tt> config option to <tt>null</tt>.</p>';
                        }
                        else {
                            echo '<p class="hint">Check the <a href="http://www.php.net/manual/en/function.finfo-open.php">Fileinfo functions</a> of your PHP installation.<br/>';
                            echo 'The path to the magic.mime file can be set using the <tt>mime_magic</tt> config option in Roundcube.</p>';
                        }
                    }
                    else {
                        $RCI->pass('Fileinfo/mime_content_type configuration');
                        echo "<br/>";
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td>MIME类型到文件扩展名映射</td>
                <td>
                    <?php
                    if ($errors = $RCI->check_mime_extensions()) {
                        $RCI->fail('Mimetype to file extension mapping');
                        echo '<p class="hint">Please set a valid path to your webserver\'s mime.types file to the <tt>mime_types</tt> config option.<br/>';
                        echo 'If you can\'t find such a file, download it from <a href="http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types">svn.apache.org</a>.</p>'; 
                    }
                    else {
                        $RCI->pass('Mimetype to file extension mapping');
                        echo "<br/>";
                    }
                    ?>
                </td>
            </tr>
            
        </tbody>
    </table>
</fieldset>





<form action="index.php?_step=3" method="post">
    <h3>测试 SMTP 配置</h3>
    <table class="table table-noborder">
        <colgroup>
            <col class="col-xs-2">
            <col class="col-xs-7">
        </colgroup>
        <tbody>
            <tr>
                <th colspan="2">SMTP 服务器信息</th>
            </tr>
            <tr>
                <td><label for="smtp_host">主机</label></td>
                <td>
                </td>
            </tr>
            <tr>
                <td><label for="smtp_user">用户名</label></td>
                <td>
                    
                </td>
            </tr>
            <tr>
                <td><label for="smtp_pass">密码</label></td>
                <td>
                    
                </td>
            </tr>
            <tr>
                <th colspan="2">SMTP 测试邮件</th>
            </tr>
            <tr>
                <td><label for="sendmailfrom">发件人</label></td>
                <td>
                    <?php
                    $from_field = new html_inputfield(['name' => '_from', 'id' => 'sendmailfrom']);
                    echo $from_field->show(isset($_POST['_from']) ? $_POST['_from'] : '');
                    ?>
                </td>
            </tr>
            <tr>
                <td><label for="sendmailto">收件人</label></td>
                <td>
                    <?php
                    $to_field = new html_inputfield(['name' => '_to', 'id' => 'sendmailto']);
                    echo $to_field->show(isset($_POST['_to']) ? $_POST['_to'] : '');
                    ?>
                </td>
            </tr>
        </tbody>
    </table>
    <p><input class="btn btn-info" type="submit" name="sendmail" value="发送测试邮件" /></p>
    <?php
    if (isset($_POST['sendmail'])) {
        echo '<p>Trying to send email...<br />';

        $smtp_host = trim($_POST['_smtp_host']);
        $from = rcube_utils::idn_to_ascii(trim($_POST['_from']));
        $to = rcube_utils::idn_to_ascii(trim($_POST['_to']));

        if (
            preg_match('/^' . $RCI->email_pattern . '$/i', $from)
            && preg_match('/^' . $RCI->email_pattern . '$/i', $to)
        ) {
            $headers = [
                'From' => $from,
                'To' => $to,
                'Subject' => 'Test message from Roundcube',
            ];

            $body = 'This is a test to confirm that Roundcube can send email.';

            // send mail using configured SMTP server
            $CONFIG = $RCI->config;

            if (!empty($_POST['_smtp_user'])) {
                $CONFIG['smtp_user'] = $_POST['_smtp_user'];
            }
            if (!empty($_POST['_smtp_pass'])) {
                $CONFIG['smtp_pass'] = $_POST['_smtp_pass'];
            }

            $mail_object = new Mail_mime();
            $send_headers = $mail_object->headers($headers);
            $head = $mail_object->txtHeaders($send_headers);

            $SMTP = new rcube_smtp();
            $SMTP->connect($smtp_host, null, $CONFIG['smtp_user'], $CONFIG['smtp_pass']);

            $status = $SMTP->send_mail($headers['From'], $headers['To'], $head, $body);
            $smtp_response = $SMTP->get_response();

            if ($status) {
                $RCI->pass('SMTP send');
            }
            else {
                $RCI->fail('SMTP send', join('; ', $smtp_response));
            }
        }
        else {
            $RCI->fail('SMTP send', 'Invalid sender or recipient');
        }

        echo '</p>';
    }
    ?>
</form>









<form action="index.php?_step=3" method="post">
    <h3>测试 IMAP 配置</h3>
    <table class="table table-noborder">
        <colgroup>
            <col class="col-xs-2">
            <col class="col-xs-7">
        </colgroup>
        <tbody>
            <tr>
                <th colspan="2">IMAP 服务器信息</th>
            </tr>
            <tr>
                <td><label for="imaphost">主机</label></td>
                <td>
                    <?php
                    $default_hosts = $RCI->get_hostlist();
                    if (!empty($default_hosts)) {
                        $host_field = new html_select(['name' => '_host', 'id' => 'imaphost']);
                        $host_field->add($default_hosts, $default_hosts);
                    }
                    else {
                        $host_field = new html_inputfield(['name' => '_host', 'id' => 'imaphost']);
                    }
                    echo $host_field->show(isset($_POST['_host']) ? $_POST['_host'] : '');
                    ?>
                </td>
            </tr>
            <tr>
                <td><label for="imapuser">用户名</label></td>
                <td>
                    <?php
                    $user_field = new html_inputfield(['name' => '_user', 'id' => 'imapuser']);
                    echo $user_field->show(isset($_POST['_user']) ? $_POST['_user'] : '');
                    ?>
                </td>
            </tr>
            <tr>
                <td><label for="imappass">密码</label></td>
                <td>
                    <?php
                    $pass_field = new html_passwordfield(['name' => '_pass', 'id' => 'imappass']);
                    echo $pass_field->show();
                    ?>
                </td>
            </tr>
        </tbody>
    </table>
    <p><input class="btn btn-info" type="submit" name="imaptest" value="测试登录" /></p>
    <?php
    if (isset($_POST['imaptest']) && !empty($_POST['_host']) && !empty($_POST['_user'])) {
        echo '<p>连接' . rcube::Q($_POST['_host']) . '...<br />';

        $imap_host = trim($_POST['_host']);
        $imap_port = 143;
        $imap_ssl  = false;

        $a_host = parse_url($imap_host);
        if ($a_host['host']) {
            $imap_host = $a_host['host'];
            $imap_ssl  = (isset($a_host['scheme']) && in_array($a_host['scheme'], ['ssl','imaps','tls'])) ? $a_host['scheme'] : null;
            $imap_port = $a_host['port'] ?? ($imap_ssl && $imap_ssl != 'tls' ? 993 : 143);
        }

        $imap_host = rcube_utils::idn_to_ascii($imap_host);
        $imap_user = rcube_utils::idn_to_ascii($_POST['_user']);

        $imap = new rcube_imap;
        $imap->set_options([
            'auth_type'      => $RCI->getprop('imap_auth_type'),
            'debug'          => $RCI->getprop('imap_debug'),
            'socket_options' => $RCI->getprop('imap_conn_options'),
        ]);

        if ($imap->connect($imap_host, $imap_user, $_POST['_pass'], $imap_port, $imap_ssl)) {
            $RCI->pass('IMAP 连接', 'SORT命令: ' . ($imap->get_capability('SORT') ? '支持' : '不支持'));
            $imap->close();
        }
        else {
            $RCI->fail('IMAP 连接', $RCI->get_error());
        }
    }
    ?>
</form>












<hr />

<p class="warning">
在完成安装和最终测试后，请务必从Web服务器的文档根目录中<b>删除</b>整个安装程序文件夹，或者确保禁用<tt>config.inc.php</tt>中的<tt>enable_installer</tt>选项。

这些文件可能会向公众暴露敏感的配置数据，如服务器密码和加密密钥。请确保你无法通过浏览器访问此安装程序。
</p>
