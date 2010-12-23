<?php 
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../../../../../../../').'/');
require_once DOKU_INC.'inc/utf8.php';

// some ACL level defines
  define('AUTH_NONE',0);
  define('AUTH_READ',1);
  define('AUTH_EDIT',2);
  define('AUTH_CREATE',4);
  define('AUTH_UPLOAD',8);
  define('AUTH_DELETE',16);
  define('AUTH_ADMIN',255);
  global $AUTH_ACL;
  $AUTH_ACL = file(DOKU_INC . '/conf/acl.auth.php');
  global $cache_authname; $cache_authname = array();
 
/**
 * Returns the maximum rights a user has for
 * the given ID or its namespace
 *
 * @author  Andreas Gohr <andi@splitbrain.org>
 *
 * @param  string  $id     page ID
 * @param  string  $user   Username
 * @param  array   $groups Array of groups the user is in
 * @return int             permission level
 */
function auth_aclcheck($id,$user,$groups, $_auth=1){
 
  global $AUTH_ACL;
  $AUTH_ACL = str_replace('%USER%',$user,$AUTH_ACL);
   
  if($_auth == 255) {
        return 255; 
  }
  elseif(isset($_SESSION['dwfck_acl']) && $_SESSION['dwfck_acl'] == 255) {
      return 255;
  }
  //make sure groups is an array
  if(!is_array($groups)) $groups = array();

  //if user is superuser or in superusergroup return 255 (acl_admin)
 // if(auth_isadmin($user,$groups)) { return AUTH_ADMIN; }

  $user = auth_nameencode($user);

  //prepend groups with @ and nameencode
  $cnt = count($groups);
  for($i=0; $i<$cnt; $i++){
    $groups[$i] = '@'.auth_nameencode($groups[$i]);
  }

  $ns    = getNS($id);
  $perm  = -1;

  if($user || count($groups)){
    //add ALL group
    $groups[] = '@ALL';
    //add User
    if($user) $groups[] = $user;
    //build regexp
    $regexp   = join('|',$groups);
  }else{
    $regexp = '@ALL';
  }

  //check exact match first
  $matches = preg_grep('/^'.preg_quote($id,'/').'\s+('.$regexp.')\s+/',$AUTH_ACL);
  
  if(count($matches)){
    foreach($matches as $match){
      $match = preg_replace('/#.*$/','',$match); //ignore comments
      $acl   = preg_split('/\s+/',$match);
      if($acl[2] > AUTH_DELETE) $acl[2] = AUTH_DELETE; //no admins in the ACL!
      if($acl[2] > $perm){
        $perm = $acl[2];
      }
    }
    if($perm > -1){
      //we had a match - return it
      return $perm;
    }
  }

  //still here? do the namespace checks
  if($ns){
    $path = $ns.':\*';
  }else{
    $path = '\*'; //root document
  }

  do{
    $matches = preg_grep('/^'.$path.'\s+('.$regexp.')\s+/',$AUTH_ACL);
      
    if(count($matches)){
      foreach($matches as $match){
        
        $match = preg_replace('/#.*$/','',$match); //ignore comments
        $acl   = preg_split('/\s+/',$match);
        if($acl[2] > AUTH_DELETE) $acl[2] = AUTH_DELETE; //no admins in the ACL!
        if($acl[2] > $perm){
          $perm = $acl[2];
        //   checkacl_write_debug("$match;;$perm");
        }
      }
      //we had a match - return it
      return $perm;
    }

    //get next higher namespace
    $ns   = getNS($ns);

    if($path != '\*'){
      $path = $ns.':\*';
      if($path == ':\*') $path = '\*';
    }else{
      //we did this already
      //looks like there is something wrong with the ACL
      //break here
   //   msg('No ACL setup yet! Denying access to everyone.');
      return AUTH_NONE;
    }
  }while(1); //this should never loop endless

  //still here? return no permissions
  return AUTH_NONE;
}

function auth_nameencode($name,$skip_group=false){
  global $cache_authname;
  $cache =& $cache_authname;
  $name  = (string) $name;

  if (!isset($cache[$name][$skip_group])) {
    if($skip_group && $name{0} =='@'){
      $cache[$name][$skip_group] = '@'.preg_replace('/([\x00-\x2f\x3a-\x40\x5b-\x60\x7b-\x7f])/e',
                                                    "'%'.dechex(ord(substr('\\1',-1)))",substr($name,1));
    }else{
      $cache[$name][$skip_group] = preg_replace('/([\x00-\x2f\x3a-\x40\x5b-\x60\x7b-\x7f])/e',
                                                "'%'.dechex(ord(substr('\\1',-1)))",$name);
    }
  }

  return $cache[$name][$skip_group];
}

function getNS($id){
  $pos = strrpos((string)$id,':');
  if($pos!==false){
    return substr((string)$id,0,$pos);
  }
  return false;
}

/**
 * Remove unwanted chars from ID
 *
 * Cleans a given ID to only use allowed characters. Accented characters are
 * converted to unaccented ones
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 * @param  string  $raw_id    The pageid to clean
 * @param  boolean $ascii     Force ASCII
 * @param  boolean $media     Allow leading or trailing _ for media files
 */
function cleanID($raw_id,$ascii=false,$media=false){
  global $dwfck_conf;
  
  static $sepcharpat = null;
  static $cache = array();  

  // check if it's already in the memory cache
  if (isset($cache[(string)$raw_id])) {
    return $cache[(string)$raw_id];
    }


  $sepchar = $dwfck_conf['sepchar'];
  if($sepcharpat == null) // build string only once to save clock cycles
    $sepcharpat = '#\\'.$sepchar.'+#';

  $id = trim((string)$raw_id);
  $id = utf8_strtolower($id);

  //alternative namespace seperator
  $id = strtr($id,';',':');
  if($dwfck_conf['useslash']){
    $id = strtr($id,'/',':');
  }else{
    $id = strtr($id,'/',$sepchar);
  }

  if($dwfck_conf['deaccent'] == 2 || $ascii) $id = utf8_romanize($id);
  if($dwfck_conf['deaccent'] || $ascii) $id = utf8_deaccent($id,-1);

  //remove specials
  $id = utf8_stripspecials($id,$sepchar,'\*');

  if($ascii) $id = utf8_strip($id);

  //clean up
  $id = preg_replace($sepcharpat,$sepchar,$id);
  $id = preg_replace('#:+#',':',$id);
  $id = ($media ? trim($id,':.-') : trim($id,':._-'));
  $id = preg_replace('#:[:\._\-]+#',':',$id);

  $cache[(string)$raw_id] = $id;
  return($id);
}

function checkacl_write_debug($data) {
    

  if (!$handle = fopen('acl.txt', 'a')) {
    return;
    }

    fwrite($handle, "$data\n");
    fclose($handle);

}
  
 function get_conf_array($str) {
     $str = preg_replace('/\s+/',"",$str);
     return explode(';;', $str);
  }

 function has_acl_auth($path) {

 }

