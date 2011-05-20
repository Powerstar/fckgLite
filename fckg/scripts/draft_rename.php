<?php
define('DOKU_INC', realpath(dirname(__FILE__)) . '/../../../../');
require_once(DOKU_INC.'inc/init.php');
require_once(DOKU_INC.'inc/io.php');

$cname = $_REQUEST['draft_id'];
$cname = urldecode($cname);
$fckg_cname = $cname . '.fckl';


if(file_exists($cname)) {
   @io_lock($cname);
   if(file_exists($fckg_cname)) {
      unlink($fckg_cname); 
   }
   if(rename($cname,$fckg_cname)) {
      @io_unlock($cname);
      echo  $cname;
      exit;
   }
}

echo "done";

