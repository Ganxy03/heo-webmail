<?php
if (!class_exists('rcmail_install', false) || !isset($RCI)) {
    die("不允许!请打开installer/index.php。");
}

// allow the current user to get to the next step
$_SESSION['allowinstaller'] = true;

if (!empty($_POST['submit'])) {
  $_SESSION['config'] = $RCI->create_config();

  if ($RCI->save_configfile($_SESSION['config'])) {
     echo '<p class="notice">配置文件保存成功'
        . ' <tt>'.RCMAIL_CONFIG_DIR.'</tt> 您的webmail安装目录。';

     if ($RCI->legacy_config) {
        echo '<br/><br/>之后,请<b>删除</b> 旧的配置文件'
            . ' <tt>main.inc.php</tt> 和 <tt>db.inc.php</tt> 从配置目录。';
     }

     echo '</p>';
  }
  else {
    $save_button = '';
    if (($dir = sys_get_temp_dir()) && @is_writable($dir)) {
      echo '<iframe name="getconfig" style="display:none"></iframe>';
      echo '<form id="getconfig_form" action="index.php" method="get" target="getconfig" style="display:none">';
      echo '<input name="_getconfig" value="2" /></form>';

      $button_txt  = html::quote('保存在 ' . $dir);
      $save_button = '&nbsp;<input class="btn btn-success" type="button" onclick="document.getElementById(\'getconfig_form\').submit()" value="' . $button_txt . '" />';
    }

    echo '<p class="notice">复制或下载以下配置并保存';
    echo ' 作为 <tt><b>config.inc.php</b></tt> 在 <tt>'.RCUBE_CONFIG_DIR.'</tt> 您的webmail安装目录。<br/>';
    echo ' 确保字符前面没有字符<tt>&lt;?php</tt> 保存文件时的括号。<br>';
    echo '&nbsp;<input class="btn btn-success" type="button" onclick="location.href=\'index.php?_getconfig=1\'" value="下载" />';
    echo $save_button;

    if ($RCI->legacy_config) {
       echo '<br/><br/>之后,请 <b>删除</b> 旧的配置文件'
        . ' <tt>main.inc.php</tt> 和 <tt>db.inc.php</tt> 从配置目录。';
    }

    echo '</p>';

    $textbox = new html_textarea(['rows' => 16, 'cols' => 60, 'class' => 'configfile']);
    echo $textbox->show(($_SESSION['config']));
  }

  echo '<p class="hint">当然，还有更多的选项需要配置。
    查看defaults.inc.php文件或访问 <a href="https://github.com/roundcube/roundcubemail/wiki/Configuration" target="_blank">如何配置</a> 去找出答案。</p>';

  echo '<p><input class="btn btn-success" type="button" onclick="location.href=\'./index.php?_step=3\'" value="继续" /></p>';

  // echo '<style type="text/css"> .configblock { display:none } </style>';
  echo "\n<hr style='margin-bottom:1.6em' />\n";
}
?>



<form action="index.php" method="post">

<input type="hidden" name="_step" value="2" />

<fieldset>
    <table class="table table-noborder">
        <!-- <legend>日志 & 调试</legend> -->
        <colgroup>
            <col class="col-xs-2">
            <col class="col-xs-7">
        </colgroup>
        <thead>
            <tr>
                <th colspan="3">常规配置</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>服务名称</td>
                <td>
                    <?php
                    $input_prodname = new html_inputfield(['name' => '_product_name', 'id' => 'cfgprodname']);
                    echo $input_prodname->show($RCI->getprop('product_name'));
                    ?>
                    <p class="hint">服务的名称(网页标题)</p>
                </td>
            </tr>
            <tr>
                <td>服务url</td>
                <td>
                    <?php
                    $input_support = new html_inputfield(['name' => '_support_url', 'id' => 'cfgsupporturl']);
                    echo $input_support->show($RCI->getprop('support_url'));
                    ?>
                    <div>提供一个URL，用户可以在其中获得对此Roundcube安装的支持。</div>
                    <p class="hint">输入一个绝对URL(包括http://)到支持页面/表单或mailto:链接)。</p>
                </td>
            </tr>
            <tr>
                <td>临时目录</td>
                <td>
                    <?php
                    $input_tempdir = new html_inputfield(['name' => '_temp_dir', 'id' => 'cfgtempdir']);
                    echo $input_tempdir->show($RCI->getprop('temp_dir'));
                    ?>
                    <div>使用此文件夹存储临时文件(必须为web服务器可写)</div>
                </td>
            </tr>
            <tr>
                <td>对称密钥</td>
                <td>
                    <?php
                    $input_deskey = new html_inputfield(['name' => '_des_key', 'id' => 'cfgdeskey']);
                    echo $input_deskey->show($RCI->getprop('des_key'));
                    ?>
                    <div>该密钥用于加密用户的imap密码，然后将其存储在会话记录中</div>
                    <p class="hint">它是一个随机生成的字符串，以确保每个安装都有自己的密钥。</p>
                </td>
            </tr>
            <tr>
                <td>IP检查</td>
                <td>
                    <?php
                    $check_ipcheck = new html_checkbox(['name' => '_ip_check', 'id' => 'cfgipcheck']);
                    echo $check_ipcheck->show(intval($RCI->getprop('ip_check')), array('value' => 1));
                    ?>
                    <label for="cfgipcheck">检查会话授权中的客户端IP</label>
                    <p class="hint">这增加了安全性，但当有人使用具有更改ip的代理时可能会导致突然注销。</p>
                </td>
            </tr>
            <tr>
                <td>身份级别</td>
                <td>
                    <?php
                    $input_ilevel = new html_select(['name' => '_identities_level', 'id' => 'cfgidentitieslevel']);
                    $input_ilevel->add('many identities with possibility to edit all params', 0);
                    $input_ilevel->add('many identities with possibility to edit all params but not email address', 1);
                    $input_ilevel->add('one identity with possibility to edit all params', 2);
                    $input_ilevel->add('one identity with possibility to edit all params but not email address', 3);
                    $input_ilevel->add('one identity with possibility to edit only signature', 4);
                    echo $input_ilevel->show($RCI->getprop('identities_level'), 0);
                    ?>
                    <div>身份访问级别</div>
                    <p class="hint">定义用户可以用他们的身份做什么。</p>
                </td>
            </tr>
        </tbody>
    </table>
</fieldset>



<fieldset>
    <!-- <legend>日志 & 调试</legend> -->
    <table class="table table-noborder">
        <colgroup>
            <col class="col-xs-2">
            <col class="col-xs-7">
        </colgroup>
        <thead>
            <tr>
                <th colspan="3">日志 & 调试</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>日志驱动程序</td>
                <td>
                    <?php
                    $select_log_driver = new html_select(['name' => '_log_driver', 'id' => 'cfglogdriver']);
                    $select_log_driver->add(['file', 'syslog', 'stdout'], ['file', 'syslog', 'stdout']);
                    echo $select_log_driver->show($RCI->getprop('log_driver', 'file'));
                    ?>
                    <div>如何进行日志记录? 'file' -写入日志目录中的文件, 'syslog' - 使用syslog功能, 'stdout' - 写入进程stdout文件描述符。</div>
                </td>
            </tr>
            <tr>
                <td>日志目录</td>
                <td>
                    <?php
                    $input_logdir = new html_inputfield(['name' => '_log_dir', 'size' => 30, 'id' => 'cfglogdir']);
                    echo $input_logdir->show($RCI->getprop('log_dir'));
                    ?>
                    <div>使用此文件夹存储日志文件(必须为web服务器可写)。注意，这只适用于使用'file' log_driver的情况。</div>
                </td>
            </tr>
            <tr>
                <td>日志前缀</td>
                <td>
                    <?php
                    $input_syslogid = new html_inputfield(['name' => '_syslog_id', 'size' => 30, 'id' => 'cfgsyslogid']);
                    echo $input_syslogid->show($RCI->getprop('syslog_id', 'webmail'));
                    ?>
                    <div>使用syslog日志记录时使用的前缀。注意，这只适用于使用'syslog' log_driver的情况。</div>
                </td>
            </tr>
            <tr>
                <td>前缀设置</td>
                <td>
                    <?php
                    $input_syslogfacility = new html_select(['name' => '_syslog_facility', 'id' => 'cfgsyslogfacility']);
                    $input_syslogfacility->add('user-level messages', LOG_USER);
                    if (defined('LOG_MAIL')) {
                        $input_syslogfacility->add('mail subsystem', LOG_MAIL);
                    }
                    if (defined('LOG_LOCAL0')) {
                        $input_syslogfacility->add('local level 0', LOG_LOCAL0);
                        $input_syslogfacility->add('local level 1', LOG_LOCAL1);
                        $input_syslogfacility->add('local level 2', LOG_LOCAL2);
                        $input_syslogfacility->add('local level 3', LOG_LOCAL3);
                        $input_syslogfacility->add('local level 4', LOG_LOCAL4);
                        $input_syslogfacility->add('local level 5', LOG_LOCAL5);
                        $input_syslogfacility->add('local level 6', LOG_LOCAL6);
                        $input_syslogfacility->add('local level 7', LOG_LOCAL7);
                    }
                    echo $input_syslogfacility->show($RCI->getprop('syslog_facility'), LOG_USER);
                    ?>
                    <div>使用syslog日志记录时使用的前缀。注意，这只适用于使用'syslog' log_driver的情况。</div>
                </td>
            </tr>
        </tbody>
    </table>
</fieldset>

<fieldset>
    <!-- <legend>数据库设置</legend> -->
    <table class="table table-noborder">
        <colgroup>
            <col class="col-xs-2">
            <col class="col-xs-7">
        </colgroup>
        <thead>
            <tr>
                <th colspan="3">数据库设置</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>数据库类型</td>
                <td>
                    <?php
                    $select_dbtype = new html_select(['name' => '_dbtype', 'id' => 'cfgdbtype']);
                    foreach ($RCI->supported_dbs as $database => $ext) {
                        if (extension_loaded($ext)) {
                            $select_dbtype->add($database, substr($ext, 4));
                        }
                    }
                    $dsnw = rcube_db::parse_dsn($RCI->getprop('db_dsnw'));
                    echo $select_dbtype->show($RCI->is_post ? $_POST['_dbtype'] : ($dsnw['phptype'] ?? ''));
                    ?>
                </td>
            </tr>
            <tr>
                <td>数据库服务器</td>
                <td>
                    <?php
                    $input_dbhost = new html_inputfield(['name' => '_dbhost', 'size' => 20, 'id' => 'cfgdbhost']);
                    echo $input_dbhost->show($RCI->is_post ? $_POST['_dbhost'] : ($dsnw['hostspec'] ?? ''));
                    ?>
                    <p class="hint">(省略sqlite)</p>
                </td>
            </tr>
            <tr>
                <td>数据库名称</td>
                <td>
                    <?php
                    $input_dbname = new html_inputfield(['name' => '_dbname', 'size' => 20, 'id' => 'cfgdbname']);
                    echo $input_dbname->show($RCI->is_post ? $_POST['_dbname'] : ($dsnw['database'] ?? ''));
                    ?>
                    <p class="hint">(使用sqlite的绝对路径和文件名)</p>
                </td>
            </tr>
            <tr>
                <td>数据库用户名</td>
                <td>
                    <?php
                    $input_dbuser = new html_inputfield(['name' => '_dbuser', 'size' => 20, 'id' => 'cfgdbuser']);
                    echo $input_dbuser->show($RCI->is_post ? $_POST['_dbuser'] : ($dsnw['username'] ?? ''));
                    ?>
                    <p class="hint">(需要写权限)(对于sqlite省略)</p>
                </td>
            </tr>
            <tr>
                <td>数据库密码</td>
                <td>
                    <?php
                    $input_dbpass = new html_inputfield(['name' => '_dbpass', 'size' => 20, 'id' => 'cfgdbpass']);
                    echo $input_dbpass->show($RCI->is_post ? $_POST['_dbpass'] : ($dsnw['password'] ?? ''));
                    ?>
                    <p class="hint">(省略sqlite)</p>
                </td>
            </tr>
            <tr>
                <td>表前缀</td>
                <td>
                    <?php
                    $input_prefix = new html_inputfield(['name' => '_db_prefix', 'size' => 20, 'id' => 'cfgdbprefix']);
                    echo $input_prefix->show($RCI->getprop('db_prefix'));
                    ?>
                    <div>可选前缀，将添加到数据库对象名称(表和序列)。</div>
                </td>
            </tr>
        </tbody>
    </table>
</fieldset>



<fieldset>
    <!-- <legend>IMAP 设置</legend> -->
    <table class="table table-noborder">
        <colgroup>
            <col class="col-xs-2">
            <col class="col-xs-7">
        </colgroup>
        <thead>
            <tr>
                <th colspan="3">IMAP 设置</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>IMAP 主机</td>
                <td>
                    <div id="defaulthostlist">
                        <?php
                        $text_imaphost = new html_inputfield(['name' => '_imap_host[]', 'size' => 30]);
                        $default_hosts = $RCI->get_hostlist();
                        if (empty($default_hosts)) {
                            $default_hosts = [''];
                        }
                        $i = 0;
                        foreach ($default_hosts as $host) {
                            echo '<div id="defaulthostentry' . $i . '">' . $text_imaphost->show($host);
                            if ($i++ > 0) {
                                echo '<a href="#" onclick="removehostfield(this.parentNode);return false" class="removelink" title="Remove this entry">remove</a>';
                            }
                            echo '</div>';
                        }
                        ?>
                    </div>
                    <div><a href="javascript:addhostfield()" class="addlink" title="Add another field">add</a></div>
                    <p class="hint">选择用于执行登录的IMAP主机。留空以在登录时显示文本框。要使用SSL/STARTTLS连接，请添加SSL://或tls://前缀。它也可以包含端口号，例如tls://imap.domain.tld:143。</p>
                </td>
            </tr>
            <tr>
                <td>用户名域</td>
                <td>
                    <?php
                    $text_userdomain = new html_inputfield(['name' => '_username_domain', 'size' => 30, 'id' => 'cfguserdomain']);
                    echo $text_userdomain->show($RCI->getprop('username_domain'));
                    ?>
                    <div>自动将此域添加到登录的用户名中</div>
                    <p class="hint">仅适用于需要完整电子邮件地址才能登录的IMAP服务器</p>
                </td>
            </tr>
            <tr>
                <td>自动创建用户</td>
                <td>
                    <?php
                    $check_autocreate = new html_checkbox(['name' => '_auto_create_user', 'id' => 'cfgautocreate']);
                    echo $check_autocreate->show(intval($RCI->getprop('auto_create_user')), ['value' => 1]);
                    ?>
                    <label for="cfgautocreate">第一次登录时自动创建一个新的Roundcube用户</label>
                    <p class="hint">用户由IMAP服务器进行身份验证，但它需要本地记录来存储设置和通信。启用此选项后，一旦IMAP登录成功，将自动创建一个新的用户记录。</p>
                    <p class="hint">如果禁用此选项，则只有在本地Roundcube数据库中有匹配的用户记录时，登录才会成功这意味着您必须手动创建这些记录或在第一次登录后禁用此选项。</p>
                </td>
            </tr>
            <tr>
                <td>发送邮箱</td>
                <td>
                    <?php
                    $text_sentmbox = new html_inputfield(['name' => '_sent_mbox', 'size' => 20, 'id' => 'cfgsentmbox']);
                    echo $text_sentmbox->show($RCI->getprop('sent_mbox'));
                    ?>
                    <div>将发送的邮件存储在此文件夹中</div>
                    <p class="hint">如果不应存储已发送的消息，则留空。注意：文件夹必须包含命名空间前缀（如果有的话）。</p>
                </td>
            </tr>
            <tr>
                <td>回收邮箱</td>
                <td>
                    <?php
                    $text_trashmbox = new html_inputfield(['name' => '_trash_mbox', 'size' => 20, 'id' => 'cfgtrashmbox']);
                    echo $text_trashmbox->show($RCI->getprop('trash_mbox'));
                    ?>
                    <div>删除邮件时将邮件移动到此文件夹</div>
                    <p class="hint">如果需要直接删除，则留空。注意：文件夹必须包含命名空间前缀（如果有的话）。</p>
                </td>
            </tr>
            <tr>
                <td>草稿邮箱</td>
                <td>
                    <?php
                    $text_draftsmbox = new html_inputfield(['name' => '_drafts_mbox', 'size' => 20, 'id' => 'cfgdraftsmbox']);
                    echo $text_draftsmbox->show($RCI->getprop('drafts_mbox'));
                    ?>
                    <div>将草稿信息存储在此文件夹中</div>
                    <p class="hint">如果不需要保存，请留空。注意：文件夹必须包含命名空间前缀（如果有的话）。</p>
                </td>
            </tr>
            <tr>
                <td>垃圾邮箱</td>
                <td>
                    <?php
                    $text_junkmbox = new html_inputfield(['name' => '_junk_mbox', 'size' => 20, 'id' => 'cfgjunkmbox']);
                    echo $text_junkmbox->show($RCI->getprop('junk_mbox'));
                    ?>
                    <div>将垃圾邮件存储在此文件夹中</div>
                    <p class="hint">注意：文件夹必须包含命名空间前缀（如果有的话）。</p>
                </td>
            </tr>
        </tbody>
    </table>
</fieldset>

<fieldset>
    <!-- <legend>SMTP 设置</legend> -->
    <table class="table table-noborder">
        <colgroup>
            <col class="col-xs-2">
            <col class="col-xs-7">
        </colgroup>
        <thead>
            <tr>
                <th colspan="3">SMTP 设置</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>SMTP 主机</td>
                <td>
                    <?php
                    $text_smtphost = new html_inputfield(['name' => '_smtp_host', 'size' => 30, 'id' => 'cfgsmtphost']);
                    echo $text_smtphost->show($RCI->getprop('smtp_host', 'localhost:587'));
                    ?>
                    <div>使用此主机发送邮件</div>
                    <p class="hint">要使用SSL/STARTTLS连接，请添加SSL://或tls://前缀。也可以包含端口号，例如tls://smtp.domain.tld:587。</p>
                </td>
            </tr>
            <tr>
                <td>SMTP 用户/SMTP 密码</td>
                <td>
                    <?php
                    $text_smtpuser = new html_inputfield(['name' => '_smtp_user', 'size' => 20, 'id' => 'cfgsmtpuser']);
                    $text_smtppass = new html_inputfield(['name' => '_smtp_pass', 'size' => 20, 'id' => 'cfgsmtppass']);
                    echo $text_smtpuser->show($RCI->getprop('smtp_user'));
                    echo $text_smtppass->show($RCI->getprop('smtp_pass'));
                    ?>
                    <div>SMTP用户名和密码(如果需要)</div>
                    <?php
                    $check_smtpuser = new html_checkbox(['name' => '_smtp_user_u', 'id' => 'cfgsmtpuseru']);
                    echo $check_smtpuser->show($RCI->getprop('smtp_user') == '%u' || !empty($_POST['_smtp_user_u']) ? 1 : 0, ['value' => 1]);
                    ?>
                    <label for="cfgsmtpuseru">使用当前IMAP用户名和密码进行SMTP身份验证</label>
                </td>
            </tr>
            <tr>
                <td>SMTP 日志</td>
                <td>
                    <?php
                    $check_smtplog = new html_checkbox(['name' => '_smtp_log', 'id' => 'cfgsmtplog']);
                    echo $check_smtplog->show(intval($RCI->getprop('smtp_log')), ['value' => 1]);
                    ?>
                    <label for="cfgsmtplog">将发送的消息记录在<tt>{log_dir}/sendmail</tt> 或syslog中</label>
                </td>
            </tr>
        </tbody>
    </table>
</fieldset>

<fieldset>
    <!-- <legend>显示设置 & 用户偏好</legend> -->
    <table class="table table-noborder">
        <colgroup>
            <col class="col-xs-2">
            <col class="col-xs-7">
        </colgroup>
        
        <thead>
            <tr>
                <th colspan="3">显示设置 & 用户偏好</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>语言 <span class="userconf">*</span></td>
                <td>
                    <?php
                    $input_locale = new html_inputfield(['name' => '_language', 'size' => 6, 'id' => 'cfglocale']);
                    echo $input_locale->show($RCI->getprop('language'));
                    ?>
                    <div>默认的区域设置，这也定义了登录屏幕的语言。<br/>将其保留为空以自动检测用户代理语言。</div>
                    <p class="hint">输入 <a href="http://www.faqs.org/rfcs/rfc1766">RFC1766</a> 格式化的语言名称。例如:en_US, de_DE, de_CH, fr_FR, pt_BR</p>
                </td>
            </tr>
            <tr>
                <td>主题 <span class="userconf">*</span></td>
                <td>
                    <?php
                    $input_skin = new html_select(['name' => '_skin', 'id' => 'cfgskin']);
                    $skins = $RCI->list_skins();
                    $input_skin->add($skins, $skins);
                    echo $input_skin->show($RCI->getprop('skin'));
                    ?>
                    <div>主题名称(文件夹在/skins中)</div>
                </td>
            </tr>
            <tr>
                <td>邮件页大小 <span class="userconf">*</span></td>
                <td>
                    <?php
                    $pagesize = $RCI->getprop('mail_pagesize');
                    if (!$pagesize) {
                        $pagesize = $RCI->getprop('pagesize');
                    }
                    $input_pagesize = new html_inputfield(['name' => '_mail_pagesize', 'size' => 6, 'id' => 'cfgmailpagesize']);
                    echo $input_pagesize->show($pagesize);
                    ?>
                    <div>在邮件消息列表视图中最多显示X个项目。</div>
                </td>
            </tr>
            <tr>
                <td>addressbook页大小 <span class="userconf">*</span></td>
                <td>
                    <?php
                    $pagesize = $RCI->getprop('addressbook_pagesize');
                    if (!$pagesize) {
                        $pagesize = $RCI->getprop('pagesize');
                    }
                    $input_pagesize = new html_inputfield(['name' => '_addressbook_pagesize', 'size' => 6, 'id' => 'cfgabookpagesize']);
                    echo $input_pagesize->show($pagesize);
                    ?>
                    <div>在联系人列表视图中最多显示X个项目。</div>
                </td>
            </tr>
            <tr>
                <td>html <span class="userconf">*</span></td>
                <td>
                    <?php
                    $check_htmlview = new html_checkbox(['name' => '_prefer_html', 'id' => 'cfghtmlview', 'value' => 1]);
                    echo $check_htmlview->show(intval($RCI->getprop('prefer_html')));
                    ?>
                    <label for="cfghtmlview">首选显示HTML消息</label>
                </td>
            </tr>
            <tr>
                <td>编辑器 <span class="userconf">*</span></td>
                <td>
                    <p for="cfghtmlcompose">撰写HTML格式的消息</p>
                    <?php
                    $select_htmlcomp = new html_select(['name' => '_htmleditor', 'id' => 'cfghtmlcompose']);
                    $select_htmlcomp->add('从不', 0);
                    $select_htmlcomp->add('总是', 1);
                    $select_htmlcomp->add('只可回复HTML消息', 2);
                    echo $select_htmlcomp->show(intval($RCI->getprop('htmleditor')));
                    ?>
                </td>
            </tr>
            <tr>
                <td>自动保存草稿 <span class="userconf">*</span></td>
                <td>
                    <label for="cfgautosave">保存撰写消息</label>
                    <?php
                    $select_autosave = new html_select(['name' => '_draft_autosave', 'id' => 'cfgautosave']);
                    $select_autosave->add('从不', 0);
                    foreach ([1, 3, 5, 10] as $i => $min) {
                        $select_autosave->add("$min 分钟", $min * 60);
                    }
                    echo $select_autosave->show(intval($RCI->getprop('draft_autosave')));
                    ?>
                </td>
            </tr>
            <tr>
                <td>mdn请求 <span class="userconf">*</span></td>
                <td>
                    <?php
                    $mdn_opts = [
                        0 => '询问用户',
                        1 => '自动发送',
                        3 => '发送收据给用户联系人，否则向用户询问',
                        4 => '发送收据给用户联系人，否则忽略',
                        2 => '忽略',
                    ];
                    $select_mdnreq = new html_select(['name' => '_mdn_requests', 'id' => 'cfgmdnreq']);
                    $select_mdnreq->add(array_values($mdn_opts), array_keys($mdn_opts));
                    echo $select_mdnreq->show(intval($RCI->getprop('mdn_requests')));
                    ?>
                    <div>接收到的消息请求消息传递通知(读接收)时的行为</div>
                </td>
            </tr>
            <tr>
                <td>Mime参数折叠 <span class="userconf">*</span></td>
                <td>
                    <?php
                    $select_param_folding = new html_select(['name' => '_mime_param_folding', 'id' => 'cfgmimeparamfolding']);
                    $select_param_folding->add('完整的RFC 2231 (Roundcube, Thunderbird)', '0');
                    $select_param_folding->add('不完全RFC 2047/2231(MS_Outlook, OE)', '1');
                    $select_param_folding->add('完整的RFC 2047 (弃用)', '2');
                    echo $select_param_folding->show(strval($RCI->getprop('mime_param_folding')));
                    ?>
                    <div>如何编码附件长/非ascii名称</div>
                </td>
            </tr>
        </tbody>
    </table>
    <p class="hint"><span class="userconf">*</span>&nbsp; 这些设置是用户首选项的默认设置</p>
</fieldset>

<fieldset>
    <!-- <legend>插件</legend> -->
    <table class="table table-noborder">
        <colgroup>
            <col class="col-xs-2">
            <col class="col-xs-7">
        </colgroup>
        <thead>
            <tr>
                <th colspan="3">插件</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $plugins = $RCI->list_plugins();
            foreach ($plugins as $p) {
                $p_check = new html_checkbox(['name' => '_plugins_' . $p['name'], 'id' => 'cfgplugin_' . $p['name'], 'value' => $p['name']]);
                echo '<tr>';
                echo '<td><label for="cfgplugin_' . $p['name'] . '">' . $p['name'] . '</label></td>';
                echo '<td>';
                echo $p_check->show($p['enabled'] ? 1 : 0);
                echo '&nbsp;' . $p['desc'];
                echo '</td>';
                echo '</tr>';
            }
            ?>
        </tbody>
    </table>
    <p class="hint">请考虑检查已启用插件的依赖关系</p>
</fieldset>




<?php

echo '<p><input class="btn btn-success" type="submit" name="submit" value="' . ($RCI->configured ? '更新' : '创建') . ' 配置文件" ' . ($RCI->failures ? 'disabled' : '') . ' /></p>';

?>
</form>