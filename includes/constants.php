<?php

defined("DB_SERVER") ? null : define("DB_SERVER","localhost");
defined("DB_USER") ? null : define("DB_USER","chat_admin");
defined("DB_PASS") ? null : define("DB_PASS","secretpassword");
defined("DB_NAME") ? null : define("DB_NAME","chat_up");

defined('DS') ? null : define('DS', DIRECTORY_SEPARATOR);
defined('SITE_ROOT') ? null : define('SITE_ROOT','C:' . DS . DS . 'Apache24' . DS . 'htdocs' . DS . 'Nanda' . DS. 'chat_up');
defined('LIB_PATH') ? null: define('LIB_PATH',SITE_ROOT.DS.'includes');

?>