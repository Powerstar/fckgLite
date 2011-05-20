<?php
/**
 *
 */
 
if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');
global $conf;
global $fckg_lang;


$default_english_file = DOKU_PLUGIN . 'fckg/action/lang/en.php';
require_once($default_english_file);
if(isset($conf['lang'])) {
  $default_lang_file = DOKU_PLUGIN . 'fckg/action/lang/' . $conf['lang'] . '.php';
  if(file_exists($default_lang_file)) {                                       
    @include_once($default_lang_file);
  }
}
 
class action_plugin_fckg_meta extends DokuWiki_Action_Plugin {
  var $session_id = false;    
  var $draft_file;
  /**
   * return some info
   */
  function getInfo(){
    return array(
		 'author' => 'Myron Turner',
		 'email'  => 'turnermm02@shaw.ca',
		 'date'   => '2007-09-18',
		 'name'   => 'meta',
		 'desc'   => 'fckg  meta handler',
		 'url'    => 'http://www.mturner.org/fckgLite',
		 );
  }
 

  /*
   * Register its handlers with the dokuwiki's event controller
   */
  function register(&$controller) {            
            $controller->register_hook( 'TPL_METAHEADER_OUTPUT', 'AFTER', $this, 'loadScript');    
            $controller->register_hook( 'HTML_EDITFORM_INJECTION', 'AFTER', $this, 'preprocess'); 
            $controller->register_hook( 'HTML_EDITFORM_OUTPUT', 'BEFORE', $this, 'insertFormElement');            
            $controller->register_hook('DOKUWIKI_STARTED', 'BEFORE', $this, 'file_type');         
            $controller->register_hook('TPL_CONTENT_DISPLAY', 'AFTER', $this, 'prevent_output');       
            $controller->register_hook('DOKUWIKI_STARTED', 'AFTER', $this, 'fnencode_check');      
            $controller->register_hook('DOKUWIKI_DONE', 'BEFORE', $this, 'remove_draftfile');      
   
  }


 function remove_draftfile(&$event,$param) {

   global $ACT;
   global $FCKG_draft_file; 
   if($ACT != 'show') return;
   if(isset($_REQUEST['id']) && !$_REQUEST['id']) return;

   if(file_exists($this->draft_file)) { 
          unlink($this->draft_file);
   }
 
 }
 function  insertFormElement(&$event, $param) {	 
   global $FCKG_show_preview;  

 $param = array();

 if(isset($_REQUEST['mode']) && $_REQUEST['mode'] == 'dwiki') {
 $button = array
        (
            '_elem' => 'button',
            'type' => 'submit',
            '_action' => 'cancel',
            'value' => '__Exit__',
            'class' => 'button',
            'id' => 'edbtn__edit',
            'title' => 'Exit to Switch Editors',              
        );

   $pos = $event->data->findElementByAttribute('type','submit');
       //inserts HTML data after that position.
   $event->data->insertElement($pos+=1,$button);
   echo "<style type = 'text/css'> #edbtn__save, #edbtn__save { display: none; } </style>";
 }

   global $ID;
   $dwedit_ns = @$this->getConf('dwedit_ns');
   if(isset($dwedit_ns) && $dwedit_ns) {
       $ns_choices = explode(',',$dwedit_ns);
       foreach($ns_choices as $ns) {
         $ns = trim($ns);
         if(preg_match("/$ns/",$ID)) {
            echo "<style type = 'text/css'>#edbtn__preview,#edbtn__save, #edbtn__save { display: inline; } </style>";         
            break;
         }
       }
   }
   $act = $event->data;
   if(is_string($act) && $act != 'edit') {  
        return;
   }

  // restore preview button if standard DW editor is in place
  // $FCKG_show_preview is set in edit.php in the register() function
 if($_REQUEST['fck_preview_mode'] != 'nil' && !isset($_COOKIE['FCKW_USE'])) {    
     echo '<style type="text/css">#edbtn__preview { display:none; }</style>';
 }
 elseif($FCKG_show_preview) {
      echo '<style type="text/css">#edbtn__preview { display: inline; } </style>';
 }
 else {
    echo '<style type="text/css">#edbtn__preview { position:absolute; visibility:hidden; }</style>';
 }

 global $fckg_lang;

  if($_REQUEST['fck_preview_mode']== 'preview'){
    return;
  }

 $param = array();
 $this->preprocess($event, $param);  // create the setDWEditCookie() js function
 $button = array
        (
            '_elem' => 'button',
            'type' => 'submit',
            '_action' => 'cancel',
            'value' => $fckg_lang['btn_fck_edit'],
            'class' => 'button',
            'id' => 'edbtn__edit',            
            'title' => $fckg_lang['btn_fck_edit']             
        );

     $pos = strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE');
     if($pos === false) {
                 $button['onclick'] = 'return setDWEditCookie(1, this);';
     }
     else {
                $button['onmousedown'] = 'return setDWEditCookie(1, this);';
     }

    $pos = $event->data->findElementByAttribute('type','submit');
       //inserts HTML data after that position.
    $event->data->insertElement(++$pos,$button);

   return;
 
  }


 function preprocess(&$event, $param) {	 
    $act = $event->data;
 
   if(is_string($act) && $act != 'edit') {  
        return;
   }
  
  ///$this->fck_editor($event, $param);
 
  echo <<<SCRIPT
    <script type="text/javascript">
    //<![CDATA[ 
    //var oldChangeCheck = changeCheck;
    function setDWEditCookie(which, e) { 
      
       var dom = document.getElementById('fckg_mode_type');          
       if(which == 1) {
           document.cookie='FCKW_USE=other;expires=Thu,01-Jan-70 00:00:01 GMT;'
           if(e && e.form) {
                    if(e.form['mode']) {
                       e.form['mode'].value = 'fck';
                    }
                    else {
                       e.form['mode'] = new Object;
                       e.form['mode'].value = 'fck';  
                    }
           }
           else dom.value = 'fck';  
       }
        else {
            var nextFCKyear=new Date();
            nextFCKyear.setFullYear(nextFCKyear.getFullYear() +1 );
            document.cookie = 'FCKW_USE=_false_;expires=' + nextFCKyear.toGMTString() + ';';    
            dom.value = 'dwiki';        
        }
         e.form.submit();
    }
  
 
    //]]> 

    </script>
SCRIPT;

  }


  function set_session() {	
      global $USERINFO, $INFO; 
      global $conf; 
      global $ID;

      if($this->session_id) return;       
           $cname = getCacheName($INFO['client'].$ID,'.draft');   
           if(file_exists($cname)) {
              $fckl_draft = $cname . '.fckl';
              if(file_exists($fckl_draft)) {
                    unlink($fckl_draft);
              }
              rename($cname, $fckl_draft);
           }
           $this->draft_file = $cname . '.fckl';
           $session_string =  session_id(); 
           $this->session_id = $session_string;      
       

           $_SESSION['dwfck_id'] = $session_string; 
           $default_fb = $this->getConf('default_fb');

           if($default_fb == 'none') {
               $acl = 255; 
           }
           else {
              $acl = auth_quickaclcheck($ID); 
           }
           $_SESSION['dwfck_acl'] = $acl; 

           if($this->getConf('openfb') || $acl == 255) {
             $_SESSION['dwfck_openfb'] = 'y';
           }
           else { 
              $_SESSION['dwfck_openfb'] = 'n';
           }

           $_SESSION['dwfck_grps'] = $USERINFO['grps'];
           $_SESSION['dwfck_client'] = $INFO['client'];   
           $_SESSION['dwfck_sepchar'] = $conf['sepchar'] ;   
           $_SESSION['dwfck_conf'] = array('sepchar'=> $conf['sepchar'],
                  'isadmin'=>($INFO['isadmin'] || $INFO['ismanager']), 
                  'deaccent'=>$conf['deaccent'], 'useslash'=>$conf['useslash']);
           $elems = explode(':', $ID);  
           array_pop($elems);
 
           $_SESSION['dwfck_ns'] = implode(':',$elems);        
           $_SESSION['dwfck_top'] = implode('/',$elems);           
           $_SESSION['dwfck_del'] = $this->getConf('acl_del');
           
            // temp fix for puzzling encoding=url bug in frmresourceslist.html,
           // where image loading is processed in GetFileRowHtml()

           if(preg_match('/fckg:fckeditor:userfiles:image/',$ID)) {
                      $_SESSION['dwfck_ns'] = "";        
                      $_SESSION['dwfck_top'] = "";      

            }

          // $expire = time()+60*60*24*30;
           $expire = null;
           setcookie('FCK_NmSp_acl',$session_string, $expire, '/'); 
  
        
  }

  function file_type(&$event, $param) {	 
       global $ACT, $TEXT;
       global $USERINFO, $INFO, $ID; 
     
       if(isset($_COOKIE['FCK_NmSp'])) $this->set_session(); 
       /* set cookie to pass namespace to FCKeditor's media dialog */
      // $expire = time()+60*60*24*30;
       $expire = null;
       setcookie ('FCK_NmSp',$ID, $expire, '/');     
      
          

      /* Remove TopLevel cookie */         
       if(isset($_COOKIE['TopLevel'])) {
            setcookie("TopLevel", $_REQUEST['TopLevel'], time()-3600, '/');
       }

    
       if(!isset($_REQUEST['id']) || isset($ACT['preview'])) return;
       if(isset($_REQUEST['do']) && isset($_REQUEST['do']['edit'])) {
              $_REQUEST['do'] = 'edit';
       }
     
  } 

function loadScript(&$event) {
  echo <<<SCRIPT

    <script type="text/javascript">
    //<![CDATA[ 
    function LoadScript( url )
    {
     document.write( '<scr' + 'ipt type="text/javascript" src="' + url + '"><\/scr' + 'ipt>' ) ;        

    }
//]]> 

 </script>

SCRIPT;

}
/*
function fck_editor(&$event) {
  
    if($_REQUEST['fckEditor_text'] ) {
        echo "<div id = 'fckEd__text' style='position:absolute; visibility:hidden;'>";   
        echo $_REQUEST['fckEditor_text'];     
        echo '</div>';
    }


}
*/

/** 
 *  Handle features need for DW Edit: 
 *    1. load script, if not loaded
 *    2. Re-label Cancel Button "Exit" when doing a preview  
*/
  function prevent_output(&$event) {
  global $ACT;

  $url = DOKU_URL . 'lib/plugins/fckg/scripts/script-cmpr.js';
  echo <<<SCRIPT

    <script type="text/javascript">
    //<![CDATA[ 

    try {
    if(!HTMLParserInstalled){};
    }
    catch (ex) {  
         LoadScript("$url");        
    }             

//]]> 
 </script>

SCRIPT;

  if(isset($_REQUEST['do']) && is_array($_REQUEST['do'])) {
    if(isset($_REQUEST['do']['preview'])) {
           echo '<script type="text/javascript">';
           echo ' var dwform = $("dw__editform"); dwform["do[draftdel]"].value = "Exit"';
           echo  '</script>';
    }
  }
 

  }

function fnencode_check() {
   
       global $conf;
       global $updateVersion;
       $rencode = false;
        if($conf['fnencode'] != 'safe') return;
        if(isset($updateVersion) && $updateVersion >= 31) {           
          $rencode = true;     
        }
        else {
            $list = plugin_list('action');
            if(in_array('safefnrecode', $list)){
                $rencode = true;   
     
            }
            elseif(file_exists($conf['datadir'].'_safefn.recoded') ||
               file_exists($conf['metadir'].'_safefn.recoded') ||
               file_exists($conf['mediadir'].'_safefn.recoded') )
            { 
               $rencode = true;
            }
        }

      if($rencode && !file_exists(DOKU_PLUGIN . 'fckg/saferencode')) {
         msg("This version of fckgLiteSafe does not support the re-encoded safe filenames. "
         . "You risk corrupting your file system.  Download an fnrencode version from either gitHub or the fckgLite web site."
         . " <a style='color:blue' href='http://www.dokuwiki.org/plugin:fckglite?&#fckglitesafe'>See fckgLite at Dokuwiki.org</a>  ",
            -1);
      }
}




      


function write_debug($data) {
  return;
  if (!$handle = fopen(DOKU_INC .'meta.txt', 'a')) {
    return;
    }
  if(is_array($data)) {
     $data = print_r($data,true);
  }
    // Write $somecontent to our opened file.
    fwrite($handle, "$data\n");
    fclose($handle);

}

}



