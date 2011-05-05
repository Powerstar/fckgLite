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
            $controller->register_hook( 'HTML_EDITFORM_INJECTION', 'AFTER', $this, 'postprocess');              
            $controller->register_hook( 'TPL_METAHEADER_OUTPUT', 'AFTER', $this, 'loadScript');    
            $controller->register_hook( 'HTML_EDITFORM_INJECTION', 'AFTER', $this, 'preprocess'); 
            $controller->register_hook( 'HTML_EDITFORM_OUTPUT', 'BEFORE', $this, 'insertFormElement');
            
            $controller->register_hook('DOKUWIKI_STARTED', 'BEFORE', $this, 'file_type');
         
            $controller->register_hook('TPL_CONTENT_DISPLAY', 'AFTER', $this, 'prevent_output');       
            $controller->register_hook('TPL_CONTENT_DISPLAY', 'BEFORE', $this, 'output_before');
           $controller->register_hook('DOKUWIKI_STARTED', 'AFTER', $this, 'fnencode_check');
      
  }



 function  insertFormElement(&$event, $param) {	 
   global $FCKG_show_preview;  

 $param = array();

 $label = "Show Preview Button"; 
 if($_REQUEST['prefix'] || $_REQUEST['suffix']) { 
    $label .= " (disabled during section edits)";
 }
 $checkbox = array('_elem'=>'checkboxfield', '_text'=>$label, '_class'=>"", 
                           'id'=>'fckL_show_prev', 'name'=>'fckL_show_prev', 'value'=>'show');
 $checkbox['onclick'] = 'fckL_show_preview()';
 $checkbox['title'] = 'Use with caution';
 
 if(!isset($_COOKIE['FCKW_USE'])) {
   $pos = $event->data->findElementByAttribute('type','submit');
       //inserts HTML data after that position.
     if($_REQUEST['prefix'] || $_REQUEST['suffix']) { 
           $checkbox['disabled'] = 1;
     }
     $event->data->insertElement($pos+=3,$checkbox);
     
     echo "<style type = 'text/css'> #edbtn__save, #edbtn__save { display: none; }\n";
     echo "div.summary { display: none; } </style>\n";
 }
 elseif(isset($_REQUEST['mode']) && $_REQUEST['mode'] == 'dwiki') {
 $button = array
        (
            '_elem' => 'button',
            'type' => 'submit',
            '_action' => 'cancel',
            'value' => 'Exit',
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
   if(isset($dwedit_ns)) {
       $ns_choices = explode(',',$dwedit_ns);
       foreach($ns_choices as $ns) {
         $ns = trim($ns);
         if(preg_match("/$ns/",$ID)) {
            echo "<style type = 'text/css'> #edbtn__save, #edbtn__save { display: inline; } </style>";         
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

echo <<<JSFN
 <script type="text/javascript">
 //<![CDATA[ 
 var ShowPreviewButtonAlerted = false;
  function fckL_show_preview() { 
      var chkbx = document.getElementById('fckL_show_prev');
      var but = document.getElementById('edbtn__preview');
      if(chkbx.value == 'show') {
           if(!ShowPreviewButtonAlerted) {
               alert("Clicking 'Preview' will result in the loss of all work that has not been previously saved.");
               but.style.display="inline";
               chkbx.value = 'hide';
           }
           else {
               but.style.display="inline";
               chkbx.value = 'hide';
           }
          ShowPreviewButtonAlerted=true;
      }
      else {
        but.style.display="none";
        chkbx.value = 'show';
      }
       
  }

  function FCKL_edit_disable() {
      var but = document.getElementById('edbtn__save');
      but.disabled=true;
  }
//]]> 
 </script>
JSFN;
 global $fckg_lang;

 $recovery_action = false;
 if(isset($_REQUEST['do']))  {  
     $do = $_REQUEST['do'];
     if(is_array($do) && array_key_exists('recover', $do)) {
          $recovery_action = true;
     }
     else {
        if($do == 'recover') {
            $recovery_action = true;
        }
     }
 }
 if($recovery_action) {
   echo "<style type = 'text/css'>#edbtn__preview { display: none; } </style>";
 }

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

// The below recovery facility was for FCK Preview mode, which has now been removed
/*
 $recovery_action = false;
 if(isset($_REQUEST['do']))  {  
     $do = $_REQUEST['do'];
     if(is_array($do) && array_key_exists('recover', $do)) {
          $recovery_action = true;
     }
     else {
        if($do == 'recover') {
            $recovery_action = true;
        }
     }
 }


   $section_edit = false;
   if(preg_match('/([=]{3,})|(<code>)|(class\s*=\s*code)/mi',$_REQUEST['wikitext'])) {
          $section_edit = true;
   }

   if($recovery_action) { 
     if($section_edit) {
          $button['title'] = 'Section edit cannot be recovered';
          $button['value'] = 'Restore Original';
     }
     else {
          $button['title'] = 'Edit Recovered Draft';
          $button['value'] = 'Edit Recovered Draft';
     }
     $button['id'] = 'btn_recover';                  
     $button['_action']   = 'edit';
     echo "<style type = 'text/css'> #edbtn__save, #edbtn__preview { display: none; } </style>";
    }



   // get position of the submit button in the form.
    $pos = $event->data->findElementByAttribute('type','submit');
       //inserts HTML data after that position.
    $event->data->insertElement(++$pos,$button);

      $event->data->_hidden['mode'] = "";

      if($recovery_action) {
          $event->data->_hidden['recovery'] = "";
          $event->data->_hidden['do'] = 'edit';    
      }
      else {
           $event->data->_hidden['do'] = 'edit'; 
      }
    
      $event->data->_hidden['fckEditor_text'] = '';

 */
 
  }


 function  postprocess(&$event, $param) {	 

   $act = $event->data;
   if(is_string($act) && $act != 'edit') {  
        return;
   }



if(isset($_REQUEST['do']) && is_array($_REQUEST['do'])) {
  if(isset($_REQUEST['do']['preview'])) {
     return;
/*
  echo <<<BUT
 <br />
 <input id = "fckg_mode_type"  type="hidden" name="mode" value="" />
 <input class="button" id="ebtn__edit"
  onclick ="return setDWEditCookie(1, this);" 
  type="submit" name="do[edit]" value="FCK Edit" accesskey="d" title="FCK Edit [ALT+E]" tabindex="6"  />

BUT;
*/

}

}

if(!isset($_REQUEST['mode']) && !isset($_COOKIE['FCKW_USE'])) return;

if(isset($_REQUEST['mode']) &&  $_REQUEST['mode'] == 'fck') {
        return;
}
elseif(isset($_COOKIE['FCKW_USE']) && $_COOKIE['FCKW_USE'] != '_false_') return;

if(!$_REQUEST['mode'] && !$_COOKIE['FCKW_USE']) return;

echo <<<BUT

<br />
 <input id = "fckg_mode_type"  type="hidden" name="mode" value="" />
 <input class="button" id="ebtn__edit"
  onclick ="return setDWEditCookie(1, this);" 
  type="submit" name="do[edit]" value="FCK Edit" accesskey="d" title="FCK Edit" [ALT+E]" tabindex="6" />

BUT;

  }


 function disable_textarea(&$event, $param) {	
/* 
 $recovery_action = false;
 if(isset($_REQUEST['do']))  {  
     $do = $_REQUEST['do'];
     if(is_array($do) && array_key_exists('recover', $do)) {
          $recovery_action = true;
     }
     else {
        if($do == 'recover') {
            $recovery_action = true;
        }
     }
 }

   if($recovery_action) {
     echo "<script type='text/javascript'>;\n";
     echo  "document.getElementById('wiki__text').focus = function() {};";  //otherwise IE complains
     echo  "document.getElementById('wiki__text').disabled = true; </script>\n";
   }
*/
 }


 function preprocess(&$event, $param) {	 
    $act = $event->data;
 
   if(is_string($act) && $act != 'edit') {  
        return;
   }

  
  $this->fck_editor($event, $param);
  global $lang;
  $notSavedStr = "'" . $lang['notsavedyet']. "'";
 
  echo <<<SCRIPT
    <script type="text/javascript">
    //<![CDATA[ 
    //var oldChangeCheck = changeCheck;
    function setDWEditCookie(which, e) { 
         
      remove_draft();
      var dwform = $('dw__editform');   
      //dwform.rev.value = "";
      if(dwform && dwform.elements && dwform.elements.recovery) {           
           dwform.elements.recovery.value = dwform.elements.wikitext.value; 
      }
         /**
             The contents of the FCKeditor are saved in a hidden DIV when
             FCK Preview is clicked
         */
      if(dwform && dwform.elements && dwform.elements.fckEditor_text) {           
            var dom = document.getElementById('fckEd__text');
            if(dom) {
               dwform.elements.fckEditor_text.value = dom.innerHTML;  
              if(dwform.elements.changecheck) {
                     var parent = dwform.elements.changecheck.parentNode;
                     parent.removeChild(dwform.elements.changecheck);
                     parent.removeChild(dwform.elements.sectok);
                     parent.removeChild(dwform.elements.date);
              }
            }
      }
      
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

function fck_editor(&$event) {


  
    if($_REQUEST['fckEditor_text'] ) {
        echo "<div id = 'fckEd__text' style='position:absolute; visibility:hidden;'>";   
        echo $_REQUEST['fckEditor_text'];     
        echo '</div>';
    }


}
 
function output_before(&$event) {


}

/** 
 *  Insures that textarea is empty when empty DW window is requested
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
   if($ACT != 'edit') return;


   if(isset($_REQUEST['fckg_bak'])) {    
     echo "<script type='text/javascript'>;\n";
     echo  "document.getElementById('wiki__text').value = ''; </script>\n";
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



