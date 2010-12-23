<?php
/*
 * FCKeditor - The text editor for Internet - http://www.fckeditor.net
 * Copyright (C) 2003-2009 Frederico Caldeira Knabben
 *
 * == BEGIN LICENSE ==
 *
 * Licensed under the terms of any of the following licenses at your
 * choice:
 *
 *  - GNU General Public License Version 2 or later (the "GPL")
 *    http://www.gnu.org/licenses/gpl.html
 *
 *  - GNU Lesser General Public License Version 2.1 or later (the "LGPL")
 *    http://www.gnu.org/licenses/lgpl.html
 *
 *  - Mozilla Public License Version 1.1 or later (the "MPL")
 *    http://www.mozilla.org/MPL/MPL-1.1.html
 *
 * == END LICENSE ==
 *
 * Configuration file for the File Manager Connector for PHP.
 */

require_once 'check_acl.php';
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../../../../../../../').'/');


global $Config ;
global $AUTH; 
global $dwfck_client;
global $topLevelFolder;
global $sep;
global $Dwfck_conf_values;
$Dwfck_conf_values = doku_config_values();

$DWFCK_con_dbg = false;  
// SECURITY: You must explicitly enable this "connector". (Set it to "true").
// WARNING: don't just set "$Config['Enabled'] = true ;", you must be sure that only
//		authenticated users can access this file or use some kind of session checking.
$Config['Enabled'] = true ;

  if(isset($_REQUEST ) && isset($_REQUEST['DWFCK_Client'])) {
     $dwfck_client = $_REQUEST['DWFCK_Client'];
     if(!$dwfck_client) $AUTH_INI = 255;
  }
  else $AUTH_INI = 255;


$Config['osDarwin'] = DWFCK_is_OS('DARWIN') ? true : false;

  
/** 

   PATHS
   This script will atempt to implement the two paths described below automatically.  
   But if that fails, here is what you must do.

   Two Paths Required:  absolute and relative.  Both must refer to the same directory.
   On a Windows System the Absolute Path is the complete path from the Drive Letter to the
   userfiles directory.

   The relative or UserfilesPath starts at the directory where you have your DokuWiki
   installed and refers to the data/media directory:  /<dokuwiki>/data/media/
   You fill in <dokuwiki> with the name of your own dokuwiki directory.  If <dokuwiki>
   is in a subdirectory, then your relative path must begin with that subdirectory.  In other
   words what you want here is the directory that would appear in the browser's url.  

   If your web site is http://my_website.org and dokuwiki is accesses as
               http://my_website.org/dokuwiki
  then your relative path is as above
              /dokuwiki/data/media/
  But if the web address is
              http://my_website.org/software/dokuwiki
  then your relative path is
              /software/dokuwiki/data/media 

   What you want is the paths to your dokuwiki data/media directory.

  On a Windows system:
  The Absolute Path uses the Windows style with back-slashes, while the relative path uses
  the Unix-style with forward slashes.  
  NOTE:  The PHP programming language requires two backslashes to represent a single backslash:
    'C:\\Program Files\\. . . . \\'

  Both paths must be enclosed in single quotes and both paths must end with slashes--a single
  forward slash in the relative path and a double backslash in the Windows.  And the relative
  path must begin with a single forward slash:
     Windows Absolute:  'C:\\Program Files\\. . . . \\'
     Linux/Unix Absolute:  /var/www/htdocs . . ./'

     Relative on all Systems:  '/. . /data/media/'

  The absolute path is assigned to  $Config['UserFilesAbsolutePath'] 
  The relative path is assigned to  $Config['UserFilesPath']
*/

/**
 Example absolute paths
   Example absolute On Windows System:
  $Config['UserFilesAbsolutePath'] = 'C:\\Program Files\\Apache Software Foundation\\Apache2.2\\htdocs\\dokuwiki\\data\\media\\';
 
  Example absolute on Unix/Linux system:
  $Config['UserFilesAbsolutePath'] = '/var/www/htdocs/dokuwiki/data/media/';

*/

/** 
 Example relative path
   This is the path to user files relative to the document root.
   This must use the Unix style path, even on a Windows system, i.e. single forward slashes
   $Config['UserFilesPath'] = '/dokuwiki/data/media/';

*/



$isWindows = DWFCK_isWinOS();
$Config['osWindows'] = $isWindows;
$useWinStyle = false;
$sep = $isWindows ? '\\' : '/';
$dwfck_local = false;


if(isset($_REQUEST['DWFCK_Browser']) && $_REQUEST['DWFCK_Browser'] == 'local') {
     $useWinStyle = true;
     $dwfck_local = true;
}

$Config['isWinStyle'] = $useWinStyle;

if(!isset($Config['UserFilesAbsolutePath']) || !isset($Config['UserFilesPath'])) {
   if(isset($_COOKIE['FCKConnector']) && $_COOKIE['FCKConnector'] == 'WIN') {
      $useWinStyle = true;  
   }

   if($isWindows || $useWinStyle) {
    setupBasePathsWin();
    if($dwfck_local) {
     $Config['UserFilesPath'] = str_replace('/media', '/pages', $Config['UserFilesPath']);
     if($isWindows) {
         $Config['UserFilesAbsolutePath'] = str_replace('\\media', '\\pages', $Config['UserFilesAbsolutePath']);
     }
     else {
        $Config['UserFilesAbsolutePath'] = str_replace('/media', '/pages', $Config['UserFilesAbsolutePath']);
     }
    }
    if($DWFCK_con_dbg) DWFCK_cfg_dbg('win_paths.txt');
   }
   else {
     setupBasePathsNix();
     if($DWFCK_con_dbg) DWFCK_cfg_dbg('nix_paths.txt');   
   }

  
}
//$isWindows=false;
setUpMediaPaths();
//$isWindows=true; 

// Due to security issues with Apache modules, it is recommended to leave the
// following setting enabled.
$Config['ForceSingleExtension'] = true ;

// Perform additional checks for image files.
// If set to true, validate image size (using getimagesize).
$Config['SecureImageUploads'] = true;

// What the user can do with this connector.
$Config['ConfigAllowedCommands'] = array('QuickUpload', 'FileUpload', 'GetFolders', 'GetFoldersAndFiles', 'CreateFolder', 'GetDwfckNs', 'UnlinkFile') ;

// Allowed Resource Types.
$Config['ConfigAllowedTypes'] = array('File', 'Image', 'Flash', 'Media') ;

// For security, HTML is allowed in the first Kb of data for files having the
// following extensions only.
$Config['HtmlExtensions'] = array("html", "htm", "xml", "xsd", "txt", "js") ;

// After file is uploaded, sometimes it is required to change its permissions
// so that it was possible to access it at the later time.
// If possible, it is recommended to set more restrictive permissions, like 0755.
// Set to 0 to disable this feature.
// Note: not needed on Windows-based servers.
if(isset($Dwfck_conf_values)) {
    $Config['ChmodOnUpload'] =  $Dwfck_conf_values['fmode'] ;
    $Config['ChmodOnFolderCreate'] = $Dwfck_conf_values['dmode']  ;
}
else {
   $Config['ChmodOnUpload'] =  0755 ;
   $Config['ChmodOnFolderCreate'] = 0755 ;
}

// See comments above.
// Used when creating folders that does not exist.

  

function setupBasePathsNix() {
  global $Config;
    $dir = dirname(__FILE__) ;
    $dir = preg_replace('/editor\/filemanager\/connectors\/.*/', 'userfiles/',$dir);
    $Config['UserFilesAbsolutePath'] = $dir;
    $document_root = $_SERVER['DOCUMENT_ROOT'];
    $relative_dir = str_replace($document_root, "", $dir);
    $Config['UserFilesPath'] = $relative_dir;
}

function setupBasePathsWin() {
  global $Config;
  global $isWindows;

    $data_media = $isWindows ? 'data\\media\\' : 'data/media/';
    $regex = $isWindows ? 'lib\plugins\fckg\fckeditor\editor\filemanager\connectors' : 'lib/plugins/fckg/fckeditor/editor/filemanager/connectors'; 
    $dir = dirname(__FILE__) ;   
       
    $regex = preg_quote($regex, '/');
    
    $dir = preg_replace('/'. $regex .'.*/', $data_media, $dir);

    $Config['UserFilesAbsolutePath'] = $dir;
     
    $base_url = getBaseURL_fck();
    $Config['UserFilesPath'] =  $base_url . 'data/media/';

}

/**
*   find hierarchically highest level parent namespace which allows acl CREATE  
*/
function get_start_dir() {
global $Config ;
global $AUTH; 
global $AUTH_INI;
global $sep;
global $dwfck_client;
 if(!$dwfck_client || $AUTH_INI == 255) return "";
  
  if(isset($_REQUEST['DWFCK_usergrps'])) {
      $usergrps = get_conf_array($_REQUEST['DWFCK_usergrps']);
  }
  else $usergrps = array();

   $elems = explode(':', $_COOKIE['FCK_NmSp']);  
   array_pop($elems);
   $ns = "";
   $prev_auth = -1;
   while(count($elems) > 0) {       
      $ns_tmp = implode(':',$elems);
      $test = $ns_tmp . ':*';          
      $AUTH = auth_aclcheck($test,$dwfck_client,$usergrps);           
      if($AUTH < 4) {  
          if(!$ns) {
             $ns = $ns_tmp;             
             break;
          }
           $AUTH = $prev_auth;
           break; 
      }
      $prev_auth = $AUTH; 
      $ns = $ns_tmp;
      array_pop($elems);        

   }

      
    if($ns) {      
       if(strpos($ns, ':')) {   
          return str_replace(':', '/', $ns);
       }
      $AUTH = auth_aclcheck(':*', $dwfck_client,$usergrps);     
        
      if($AUTH >= 8)  return "";
      return $ns;
    }
    $AUTH = auth_aclcheck(':*', $dwfck_client,$usergrps);      
    return "";
 
}

function setUpMediaPaths() {

  global $Config;
  global $isWindows;
  global $useWinStyle; 
  global $AUTH;
  global $dwfck_client;
  
  

  
  $ALLOWED_MIMES = DOKU_INC . 'conf/mime.conf';
  
  $out=@file($ALLOWED_MIMES,FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  
  if(file_exists(DOKU_INC . 'conf/mime.local.conf'))
  {
  	$out_local = @file(DOKU_INC . 'conf/mime.local.conf',FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);  	
  	$out = array_merge($out,$out_local);
  }
  
  $extensions = array();
  foreach($out as $line) {
      if(strpos($line,'#') ===  false) {
         list($ext,$mtype)  = preg_split('/\s+/', $line); 
         $extensions[] = $ext;
     }
  }

  

    // if !$dwfck_client then the file browser is not restricted to the client's permissions 
   if(!$dwfck_client) {
      $unrestricted_browser = true;
   }
   else $unrestricted_browser = false;

  if(isset($_REQUEST['DWFCK_usergrps'])) {
      $usergrps = get_conf_array($_REQUEST['DWFCK_usergrps']);
  }
  else $usergrps = array();

if(count($extensions) > 10) {
   $Config['AllowedExtensions']['File'] = $extensions;
}
else {
   $Config['AllowedExtensions']['File']	= array('7z', 'aiff', 'asf', 'avi', 'bmp', 'csv',
      'doc', 'docx','fla', 'flv', 'gif', 'gz', 'gzip', 'jpeg', 'jpg',
      'mid', 'mov', 'mp3', 'mp4', 'mpc', 'mpeg', 'mpg', 'ods', 'odt', 
      'pdf', 'png', 'ppt', 'psd', 'pxd', 'qt', 'ram', 'rar', 'rm', 'rmi', 'rmvb',
      'rtf', 'sdc', 'sitd', 'swf', 'sxc', 'sxw', 'tar', 'tgz', 'tif',
      'tiff', 'txt', 'vsd', 'wav', 'wma', 'wmv', 'xls', 'xml', 'zip') ;
}
    $Config['DeniedExtensions']['File']		= array() ;
    $Config['AllowedExtensions']['Image']	= array('bmp','gif','jpeg','jpg','png') ;
    $Config['DeniedExtensions']['Image']	= array() ;
    $Config['AllowedExtensions']['Flash']	= array('swf','flv') ;
    $Config['DeniedExtensions']['Flash']	= array() ;
    $Config['AllowedExtensions']['Media']	= array('aiff', 'asf', 'avi', 'bmp', 'fla', 'flv', 'gif', 'jpeg', 'jpg', 'mid', 'mov', 'mp3', 'mp4', 'mpc', 'mpeg', 'mpg', 'png', 'qt', 'ram', 'rm', 'rmi', 'rmvb', 'swf', 'tif', 'tiff', 'wav', 'wma', 'wmv') ;
    $Config['DeniedExtensions']['Media']	= array() ;

    $DWFCK_MediaTypes = array('File','Image', 'Flash','Media'); 
    $DWFCK_use_acl = true;
    if($unrestricted_browser) $DWFCK_use_acl = false;
    $current__Folder = ""; 
    if($DWFCK_use_acl && isset($_COOKIE['FCK_NmSp'])) {      
        if(strpos($_COOKIE['FCK_NmSp'], ':')) {         
          $current__Folder=get_start_dir();           
        }
   } 
    
   session_id($_COOKIE['FCK_NmSp_acl']);
   session_start();      
   if($_SESSION['dwfck_openfb'] == 'y') {
          $current__Folder = "";
   }
  
   $topLevelFolder=$current__Folder ? $current__Folder : '/';
   if($current__Folder) $current__Folder .= '/';        
   if($unrestricted_browser) $AUTH = 255;   
   setcookie("TopLevel", "$topLevelFolder;;$AUTH", time()+3600, '/'); 
   foreach($DWFCK_MediaTypes as $type) {   

        $abs_type_dir = strtolower($type) . '/';
        if($isWindows || $useWinStyle) {
          $abs_type_dir = "";
        }
        else {
           $abs_type_dir = strtolower($type) . '/';
        }
        $Config['FileTypesPath'][$type]		= $Config['UserFilesPath'] . $abs_type_dir; // $dir_type; 
        $Config['FileTypesAbsolutePath'][$type] = $Config['UserFilesAbsolutePath'] . $abs_type_dir; // $abs_type_dir ;
        $Config['QuickUploadPath'][$type]		= $Config['UserFilesPath'] . $abs_type_dir; // $dir_type ;
        $Config['QuickUploadAbsolutePath'][$type]= $Config['UserFilesAbsolutePath'] . $abs_type_dir;
        
        $Config['FileTypesPath'][$type]		= $Config['UserFilesPath'] . $abs_type_dir; //$dir_type; 
        $Config['FileTypesAbsolutePath'][$type] = $Config['UserFilesAbsolutePath'] . $abs_type_dir ;
        
        
    }

}

function getBaseURL_fck(){
 
  if(substr($_SERVER['SCRIPT_NAME'],-4) == '.php'){
    $dir = dirname($_SERVER['SCRIPT_NAME']);
  }elseif(substr($_SERVER['PHP_SELF'],-4) == '.php'){
    $dir = dirname($_SERVER['PHP_SELF']);
  }elseif($_SERVER['DOCUMENT_ROOT'] && $_SERVER['SCRIPT_FILENAME']){
    $dir = preg_replace ('/^'.preg_quote($_SERVER['DOCUMENT_ROOT'],'/').'/','',
                         $_SERVER['SCRIPT_FILENAME']);
    $dir = dirname('/'.$dir);
  }else{
    $dir = '.'; //probably wrong
  }

  $dir = str_replace('\\','/',$dir);             // bugfix for weird WIN behaviour
  $dir = preg_replace('#//+#','/',"/$dir/");     // ensure leading and trailing slashes

  //handle script in lib/exe dir
  $dir = preg_replace('!lib/exe/$!','',$dir);

  //handle script in lib/plugins dir
  $dir = preg_replace('!lib/plugins/.*$!','',$dir);

  //finish here for relative URLs
  return $dir;
}

function DWFCK_isWinOS() {
  global $Config;  
  if(isset($_SERVER['WINDIR']) && $_SERVER['WINDIR']) {
      return true;
  }
  elseif(stristr(PHP_OS, 'WIN') && !DWFCK_is_OS('DARWIN')) {
     return true;
  }
  
  return false;
}


function DWFCK_is_OS($os) {
  $os = strtolower($os);
  $_OS = strtolower(PHP_OS);

  if($os == $_OS || stristr(PHP_OS, $os) || stristr($os,PHP_OS) ) {
        return true;
  }
  return false;
}

function DWFCK_cfg_dbg($fname) {
   global $Config;
   $request = print_r($_REQUEST,true);
   file_put_contents($fname, $Config['UserFilesAbsolutePath'] . "\r\n" . $Config['UserFilesPath'] . "\r\n" .$request ."\r\n");
}

function doku_config_values() {
  $dwphp = DOKU_INC . 'conf/dokuwiki.php';
  $localphp = DOKU_INC . 'conf/local.php';
  if(file_exists($dwphp))
  {
  	include($dwphp);
    if(file_exists($localphp))
    {
      include($localphp);
    }
    return $conf;
  }

  return false;
}

?>
