<?php

defined('DS') ? null : define('DS', DIRECTORY_SEPARATOR);
defined('SITE_ROOT') ? null : define('SITE_ROOT','C:' . DS . DS . 'Apache24' . DS . 'htdocs' . DS . 'Nanda' . DS. 'chat_up');
defined('LIB_PATH') ? null: define('LIB_PATH',SITE_ROOT.DS.'includes');

require_once(LIB_PATH.DS."constants.php");
require_once(LIB_PATH.DS."session.php");
require_once(LIB_PATH.DS."functions.php");
require_once(LIB_PATH.DS."databaseobject.php");
require_once(LIB_PATH.DS."database.php");
require_once(LIB_PATH.DS."user.php");
require_once(LIB_PATH.DS."contact.php");
require_once(LIB_PATH.DS."conversation.php");
require_once(LIB_PATH.DS."message.php");

?>