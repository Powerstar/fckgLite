<?php
$VERSION = realpath(dirname(__FILE__).'/../../../').'/version';
if(file_exists($VERSION)) {
  echo "sub-version:<br />";
  readfile($VERSION);
}
?>
