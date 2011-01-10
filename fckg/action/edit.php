<?php
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');
global $conf;

$default_english_file = DOKU_PLUGIN . 'fckg/action/lang/en.php';
require_once($default_english_file);

if(isset($conf['lang']) && $conf['lang'] != 'en' ) {
  $default_lang_file = DOKU_PLUGIN . 'fckg/action/lang/' . $conf['lang'] . '.php';
  if(file_exists($default_lang_file)) {                                       
    @include($default_lang_file);
  }
}

/**
 * @license    GNU GPLv2 version 2 or later (http://www.gnu.org/licenses/gpl.html)
 * 
 * class       plugin_fckg_edit 
 * @author     Myron Turner <turnermm02@shaw.ca>
 */

class action_plugin_fckg_edit extends DokuWiki_Action_Plugin {
    //store the namespaces for sorting
    var $fck_location = "fckeditor";
    var $helper       = false;
    var $fckg_bak_file = "";
    var $debug = false;
    var $test = false;
    /**
     * Constructor
     */
    function action_plugin_fckg_edit()
    {
        $this->setupLocale();
        $this->helper =& plugin_load('helper', 'fckg');
    }

    function getInfo()
    {
              return array(
                 'author' => 'Myron Turner',
                 'email'  => 'turnermm02@shaw.ca',
                 'date'   => '2007-09-18',
                 'name'   => 'edit.php',
                 'desc'   => 'fckgLite parser',
                 'url'    => 'http://www.mturner.org/fckgLite',
                 );
    }



    function register(&$controller)
    {
        global $FCKG_show_preview;
        $FCKG_show_preview = true;
        if(isset($_REQUEST['mode']) && $_REQUEST['mode'] == 'dwiki') {
           if(isset($_REQUEST['fck_convert'])) {
            //  $controller->register_hook('IO_WIKIPAGE_READ', 'BEFORE', $this, 'fck_convert_text');  
           }
          $FCKG_show_preview = true;
          return;
        }
        elseif(isset($_COOKIE['FCKW_USE'])) {
             preg_match('/_\w+_/',  $_COOKIE['FCKW_USE'], $matches);
             if($matches[0] == '_false_') {
               if(isset($_REQUEST['fck_convert'])) {
                 // $controller->register_hook('IO_WIKIPAGE_READ', 'BEFORE', $this, 'fck_convert_text');  
               }
                  $FCKG_show_preview = true;     
                   return;
             }
        }

       
        $controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this, 'fckg_edit');
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'fckg_edit_meta');
    }


    /**
     * fckg_edit_meta 
     *
     * load fck js
     * @author Pierre Spring <pierre.spring@liip.ch>
     * @param mixed $event 
     * @access public
     * @return void
     */
    function fckg_edit_meta(&$event)
    {

        global $ACT;
        // we only change the edit behaviour
        if ($ACT != 'edit'){
            return;
        }
        global $ID;
        global $REV;
        global $INFO;

        global $WASSHOW;
        // load the text and check if it is a fck
        if($INFO['exists']){
            if($RANGE){
                list($PRE,$text,$SUF) = rawWikiSlices($RANGE,$ID,$REV);
            }else{
                $text = rawWiki($ID,$REV);
            }
        }
        $event->data['script'][] = 
            array( 
                'type'=>'text/javascript', 
                'charset'=>'utf-8', 
                '_data'=>'',
                 'src'=>DOKU_BASE.'lib/plugins/fckg/' .$this->fck_location. '/fckeditor.js'
            );

        $event->data['script'][] = 
            array( 
                'type'=>'text/javascript', 
                'charset'=>'utf-8', 
                '_data'=>'',
                 'src'=>DOKU_BASE.'lib/plugins/fckg/scripts/vki_kb.js'
            );

        return;
    }

    /**
     * function    fckg_edit
     * @author     Pierre Spring <pierre.spring@liip.ch>
     * edit screen using fck
     *
     * @param & $event
     * @access public
     * @return void
     */
    function fckg_edit(&$event)
    {
  
        // we only change the edit behaviour
        if ($event->data != 'edit') {
            return;
        }
        // load xml and acl
        if (!$this->_preprocess()){
            return;
        }
        // print out the edit screen
        $this->_print();
        // prevent Dokuwiki normal processing of $ACT (it would clean the variable and destroy our 'index' value.
        $event->preventDefault();
        // index command belongs to us, there is no need to hold up Dokuwiki letting other plugins see if its for them
        $event->stopPropagation();
    }
    
   /**
    * function _preprocess
	* @author  Myron Turner <turnermm02@shaw.ca>
    * @author  Pierre Spring <pierre.spring@liip.ch>
    */
    function _preprocess()
    {
        global $ID;
        global $REV;
        global $DATE;
        global $RANGE;
        global $PRE;
        global $SUF;
        global $INFO;
        global $SUM;
        global $lang;
        global $conf;

        //set summary default
        if(!$SUM){
            if($REV){
                $SUM = $lang['restored'];
            }elseif(!$INFO['exists']){
                $SUM = $lang['created'];
            }
        }
        //no text? Load it!
        if(!isset($text)){
            $pr = false; //no preview mode
            if($INFO['exists']){
                if($RANGE){
                    list($PRE,$text,$SUF) = rawWikiSlices($RANGE,$ID,$REV);
                }else{
                    $text = rawWiki($ID,$REV);
                }
            }else{
                //try to load a pagetemplate
                $data = array($ID);
                $text = trigger_event('HTML_PAGE_FROMTEMPLATE',$data,'pageTemplate',true);
            }
        }else{
            $pr = true; //preview mode
        }
       
       $pos = strpos($text, '<');
      
      
       if($pos !== false) {


           $text = preg_replace_callback(
            '/(<nowiki>)(.*?)(<\/nowiki>)/ms',          
            create_function(
                '$matches',         
                 '$needles =  array("[","]", "/",  ".", "*", "_","\'","<",">","%", "{", "}", "\\");
                  $replacements = array("&#91;","&#93;","&#47;", "&#46;", "&#42;", "&#95;", "&#39;", "&#60;","&#62;","&#37;", "&#123;","&#125;", "&#92;"); 
                  $matches[2] = str_replace($needles, $replacements, $matches[2]);
                  return  $matches[1] . $matches[2] . $matches[3];'            
            ),
            $text
          );
                  

           $text = preg_replace_callback(
            '/<(code|file)(.*?)(>)(.*?)(<\/\1>)/ms',
            create_function(
                '$matches',         
                 '$matches[4] = preg_replace("/<(?!\s)/ms", "< ", $matches[4]); 
                  $matches[4] = preg_replace("/(?<!\s)>/ms", " >", $matches[4]);                    
                  $matches[4] = str_replace("\"", "__GESHI_QUOT__", $matches[4]);     
                  return "<" . $matches[1] . $matches[2] . $matches[3] . $matches[4] . $matches[5];'            
            ),
            $text
          );
          $text = preg_replace('/<\/(code|file)>\s*(?=[^\w])\s*/m',"</$1>\n_fckg_NPBBR_",$text );
          $text = preg_replace_callback(
            '/(\|\s*)(<code>|<file>)(.*?)(<\/code>|<\/file>)\n_fckg_NPBBR_(?=.*?\|)/ms',
            create_function(
                '$matches',         
                 '$matches[2] = preg_replace("/<code>/ms", "TPRE_CODE", $matches[2]); 
                  $matches[2] = preg_replace("/<file>/ms", "TPRE_FILE", $matches[2]);    
                  $matches[4] = "TPRE_CLOSE";                    
                  $matches[3] = preg_replace("/^\n+/", "TC_NL",$matches[3]);  
                  $matches[3] = preg_replace("/\n/ms", "TC_NL",$matches[3]);                                   
                  return $matches[1] . $matches[2] .  trim($matches[3]) .   $matches[4];'            
            ),
            $text
          );
                  

         $text = preg_replace('/<(?!code|file|plugin|del|sup|sub|\/\/|\s|\/del|\/code|\/file|\/plugin|\/sup|\/sub)/ms',"//<//",$text);
         
         $text = preg_replace_callback('/<plugin(.*?)(?=<\/plugin>)/ms',
                        create_function(
                          '$matches', 
                           'return str_replace("//","", $matches[0]);'
                       ),
                       $text
                 ); 
         $text = str_replace('</plugin>','</plugin> ', $text);           
       }  
        
       $text = str_replace('>>','CHEVRONescC',$text);
       $text = str_replace('<<','CHEVRONescO',$text);
       $text = preg_replace('/(={3,}.*?)(\{\{.*?\}\})(.*?={3,})/',"$1$3\n$2",$text);
       $email_regex = '/\/\/\<\/\/(.*?@.*?)>/';
       $text = preg_replace($email_regex,"<$1>",$text);
      
       $this->xhtml = $this->_render_xhtml($text);
       $this->xhtml = str_replace("__GESHI_QUOT__", '&#34;', $this->xhtml);

       $this->xhtml = str_replace('CHEVRONescC', '>>',$this->xhtml);
       $this->xhtml = str_replace('CHEVRONescO', '<<',$this->xhtml);

       if($pos !== false) {
       $this->xhtml = preg_replace_callback(
                '/(TPRE_CODE|TPRE_FILE)(.*?)(TPRE_CLOSE)/ms',
                create_function(
                   '$matches', 
                   '$matches[1] = preg_replace("/TPRE_CODE/","<pre class=\'code\'>\n", $matches[1]);  
                    $matches[1] = preg_replace("/TPRE_FILE/","<pre class=\'file\'>\n", $matches[1]);  
                    $matches[2] = preg_replace("/TC_NL/ms", "\n", $matches[2]);  
                    $matches[3] = "</pre>";                    
                    return $matches[1] . $matches[2] . $matches[3];'            
                ),
                $this->xhtml
              ); 
       }
        return true;
    }

   /** 
    * function dw_edit_displayed
    * @author  Myron Turner
    * determines whether or not to show  or hide the
      'DW Edit' button
   */

   function dw_edit_displayed() 
   { 
        global $INFO;

        $dw_edit_display = @$this->getConf('dw_edit_display');
        if(!isset($dw_edit_display))return "";  //version 0. 
        if($dw_edit_display != 'all') {
            $admin_exclusion = false;
            if($dw_edit_display == 'admin' && ($INFO['isadmin'] || $INFO['ismanager']) ) {    
                    $admin_exclusion = true;
            }
            if($dw_edit_display == 'none' || $admin_exclusion === false) {
              return ' style = "display:none"; ';
            }
           return "";
        }
        return "";
      
   }

   /**
    * function _preprocess
    * @author  Myron Turner
    */ 
    function _print()
    {
        global $INFO;
        global $lang;
        global $fckg_lang;
        global $ID;
        global $REV;
        global $DATE;
        global $PRE;
        global $SUF;
        global $SUM;
        $wr = $INFO['writable'];
        if($wr){
            if ($REV) print p_locale_xhtml('editrev');
            print p_locale_xhtml($include);
            $ro=false;
        }else{
            // check pseudo action 'source'
            if(!actionOK('source')){
                msg('Command disabled: source',-1);
                return false;
            }
            print p_locale_xhtml('read');
            $ro='readonly="readonly"';
        }

        if(!$DATE) $DATE = $INFO['lastmod'];
        $guest_toolbar = $this->getConf('guest_toolbar');
        $guest_media  = $this->getConf('guest_media');
        if(!isset($INFO['userinfo']) && !$guest_toolbar) {        
            
                echo  $this->helper->registerOnLoad(
                    ' fck = new FCKeditor("wiki__text", "100%", "600"); 
                     fck.BasePath = "'.DOKU_BASE.'lib/plugins/fckg/'.$this->fck_location.'/"; 
                     fck.ToolbarSet = "DokuwikiNoGuest";  
                     fck.ReplaceTextarea();'
                     );
        }
        else if(!isset($INFO['userinfo']) && !$guest_media) {            

            echo  $this->helper->registerOnLoad(
                ' fck = new FCKeditor("wiki__text", "100%", "600"); 
                 fck.BasePath = "'.DOKU_BASE.'lib/plugins/fckg/'.$this->fck_location.'/"; 
                 fck.ToolbarSet = "DokuwikiGuest";  
                 fck.ReplaceTextarea();'
                 );
        }
        
        else {
            echo  $this->helper->registerOnLoad(
                ' fck = new FCKeditor("wiki__text", "100%", "600"); 
                 fck.BasePath = "'.DOKU_BASE.'lib/plugins/fckg/'.$this->fck_location.'/"; 
                 fck.ToolbarSet = "Dokuwiki";  
                 fck.ReplaceTextarea();'
                 );
        }

      

?>

   <form id="dw__editform" method="post" action="<?php echo script()?>"  "accept-charset="<?php echo $lang['encoding']?>">
    <div class="no">
      <input type="hidden" name="id"   value="<?php echo $ID?>" />
      <input type="hidden" name="rev"  value="<?php echo $REV?>" />
      <input type="hidden" name="date" value="<?php echo $DATE?>" />
      <input type="hidden" name="prefix" value="<?php echo formText($PRE)?>" />
      <input type="hidden" name="suffix" value="<?php echo formText($SUF)?>" />
      <input type="hidden" id="fckg_mode_type"  name="mode" value="" />
      <input type="hidden" id="fckEditor_text"  name="fckEditor_text" value="" />
      <input type="hidden" id="fck_preview_mode"  name="fck_preview_mode" value="nil" />
      <input type="hidden" id="fck_wikitext"    name="fck_wikitext" value="__false__" />
      <?php
      if(function_exists('formSecurityToken')) {
           formSecurityToken();  
      }
      ?>
    </div>

    <textarea name="wikitext" id="wiki__text" <?php echo $ro?> cols="80" rows="10" class="edit" tabindex="1"><?php echo "\n".$this->xhtml?></textarea>

<?php 

$temp=array();
trigger_event('HTML_EDITFORM_INJECTION', $temp);

$DW_EDIT_disabled = '';
$guest_perm =  $acl = auth_quickaclcheck($_REQUEST['id']);
$guest_group = false;
$guest_user = false;
if(isset($INFO['userinfo'])) {
  $user_groups = $INFO['userinfo']['grps'];
      foreach($user_groups as $group) { 
        if (strcasecmp('guest', $group) == 0) {
          $guest_group = true;
          break;
        }
     }
  if($INFO['client'] == 'guest') $guest_user = true; 
}

if(($guest_user || $guest_group) && $guest_perm < 2) $DW_EDIT_disabled = 'disabled';


$DW_EDIT_hide = $this->dw_edit_displayed(); 

?>

    <div id="wiki__editbar">
      <div id="size__ctl"></div>
      <div id = "fck_size__ctl" style="display: none">
       
       <img src = "<?php echo DOKU_BASE ?>lib/images/smaller.gif"
                    title="edit window smaller"
                    onclick="dwfck_size_ctl('smaller');"   
                    />
       <img src = "<?php echo DOKU_BASE ?>lib/images/larger.gif"
                    title="edit window larger"
                    onclick="dwfck_size_ctl('larger');"   
           />
      </div>
      <?php if($wr){?>
         <div class="editButtons">
            <input type="checkbox" name="fckg" value="fckg" checked="checked" style="display: none"/>
             <input class="button" type="button" 
                   value="<?php echo $lang['btn_save']?>" 
                   title="<?php echo $lang['btn_save']?> "   
                   <?php echo $DW_EDIT_disabled; ?>
                   onmousedown="parse_wikitext('edbtn__save');"
                  /> 

            <input class="button" id="ebtn__delete" type="submit" 
                   <?php echo $DW_EDIT_disabled; ?>
                   name="do[delete]" value="<?php echo $lang['btn_delete']?>"
                   title="<?php echo $fckg_lang['title_dw_delete'] ?>"
                   onclick = "return confirm('<?php echo $fckg_lang['confirm_delete']?>');"
            />

            <input type="checkbox" name="fckg" value="fckg" style="display: none"/>
             
             <input class="button"  
                 <?php echo $DW_EDIT_disabled; ?>                 
                 <?php echo $DW_EDIT_hide; ?>
                 onclick ="setDWEditCookie(2, this);parse_wikitext('edbtn__save');this.form.submit();" 
                 type="submit" name="do[save]" value="<?php echo $fckg_lang['btn_dw_edit']?>"  
                 title="<?php echo $fckg_lang['title_dw_edit']?>"
                  />
           
<?php
 
global $INFO;

  $disabled = 'Disabled';
  $inline = $this->test ? 'inline' : 'none';

  $backup_btn = isset($fckg_lang['dw_btn_backup'])? $fckg_lang['dw_btn_backup'] : $fckg_lang['dw_btn_refresh'];
  $backup_title = isset($fckg_lang['title_dw_backup'])? $fckg_lang['title_dw_backup'] : $fckg_lang['title_dw_refresh'];   
 
?>
            <input class="button" type="submit" 
                 name="do[draftdel]" 
                 value="<?php echo $lang['btn_cancel']?>" 
                 title = "<?php echo $fckg_lang['title_dw_cancel']?>"
             />

            <input class="button" id="edbtn__preview" type="submit" name="do[preview]"  
                     <?php echo $DW_EDIT_disabled; ?>
                     onmousedown="saveFCKEditorNode(this);return true;"  
                     style="display:none" 
                     value="<?php echo $fckg_lang['dw_btn_fck_preview']?>"
                />

  
            <input class="button" type="button" value = "<?php echo $fckg_lang['dw_btn_lang']?>"
                   title="<?php echo $fckg_lang['title_dw_lang']?>"
                   onclick="aspell_window();"  
                  /> 

            <input class="button" type="button" value = "Test"
                   title="Test"  
                   style = 'display:<?php echo $inline ?>;'
                   onmousedown="parse_wikitext('test');"
                  /> 

 <?php if($this->debug) { ?>
         <input class="button" type="button" value = "Debug"
                   title="Debug"                     
                   onclick="HTMLParser_debug();"
                  /> 

            <br />
 <?php } ?>
  
             <input class="button" type="button"
                   value="<?php echo $backup_btn ?>"
                   title="<?php echo $backup_title ?>"  
                   onclick="renewLock('bakup');"  
                  />
 
             <input class="button" type="button"
                   value="<?php echo $fckg_lang['dw_btn_revert']?>"  
                   title="<?php echo $fckg_lang['title_dw_revert']?>"  
                   onclick="revert_to_prev()"  
                  />&nbsp;&nbsp;&nbsp;
              
 <br />


   <div id = "backup_msg" class="backup_msg" style=" display:none;">
     <table><tr><td class = "backup_msg_td">
      <div id="backup_msg_area" class="backup_msg_area"></div>
     <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
     <td align="right">
      <a href="javascript:hide_backup_msg();void(0);" class="backup_msg_close">[ close ]</a>&nbsp;&nbsp;&nbsp;
     </table>
     
 </div>


<input type="checkbox" name="fckg_timer" value="fckg_timer"  id = "fckg_timer"
                      style = 'display:none'
                      onclick="disableDokuWikiLockTimer();"
                      <?php echo $disabled  ?>
                 /><span id='fckg_timer_label'
                    style = 'display:none'>Disable editor time-out messsages </span> 


      <input style="display:none;" class="button" id="edbtn__save" type="submit" name="do[save]" 
                      value="<?php echo $lang['btn_save']?>" 
                      <?php echo $DW_EDIT_disabled; ?>
                      title="<?php echo $lang['btn_save']?> "  />

 <div id='saved_wiki_html' style = 'display:none;' ></div>

  <script type="text/javascript">
//<![CDATA[

        <?php  echo 'var backup_empty = "' . $fckg_lang['backup_empty'] .'";'; ?>

        function aspell_window() {
          var DURL = "<?php echo DOKU_URL; ?>";
          window.open( DURL + "/lib/plugins/fckg/fckeditor/aspell.php?dw_conf_lang=<?php global $conf; echo $conf['lang']?>",
                    "smallwin", "width=600,height=500,scrollbars=yes");
        }

        unsetDokuWikiLockTimer();  

       /**
             Saves contents of the FCKeditor text area so that unsaved edits in FCK TextArea are
             not lost when switching from the FCKEditor to DokuWiki preview mode: FCK Preview             
        */
        function saveFCKEditorNode(e) {
          if(!confirm("<?php echo $fckg_lang['confirm_preview']?>")) return false;
           var dwform = $('dw__editform');
           if(dwform && dwform.elements && dwform.elements.fckEditor_text && ourFCKEditorNode) {           
               dwform.elements.fckEditor_text.value = ourFCKEditorNode.innerHTML; 
         }
          $('fck_preview_mode').value = 'preview';
           parse_wikitext('edbtn__preview'); 
           return true;
        }


        function dwfck_size_ctl(which) {
           var height = parseInt(document.getElementById('wiki__text___Frame').style.height); 
           if(which == 'smaller') {
               height -= 50;
           }
           else {
              height += 50;
           }
           document.getElementById('wiki__text___Frame').style.height = height + 'px'; 
   
        }


var fckgLPluginPatterns = new Array();

<?php
   global $fckgLPluginPatterns; 
   foreach($fckgLPluginPatterns as $pat) {  
     $pat[0] = preg_replace('/\s+$/',"",$pat[0]);  
    // $pat[0] = preg_quote($pat[0], "/");    
     $pat[1] = str_replace('&','&amp;', $pat[1]);    
     $pat[0] = str_replace('&','&amp;',$pat[0]);    
     $pat[0] = str_replace('>', '&gt;',$pat[0]);    
     $pat[0] = str_replace('<', '&lt;',$pat[0]);    
     $pat[1] = str_replace('>', '&gt;',$pat[1]);    
     $pat[1] = str_replace('<', '&lt;',$pat[1]);    
     $pat[0] = str_replace(' ', '\s+',$pat[0]);    
     $pat[0] = str_replace('*', '%%\*%%',$pat[0]);    
     $pat[0] = preg_quote($pat[0], "/");   
     echo "fckgLPluginPatterns.push({'pat': '$pat[0]', 'orig': '$pat[1]' });\n"; 
 
   }

   global $fckLImmutables;
   echo "if(!fckLImmutables) var fckLImmutables = new Array();\n";

   for($i=0; $i< count($fckLImmutables); $i++) {
      echo "fckLImmutables.push('$fckLImmutables[$i]');\n";         
   }
   
   $pos = strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE');
   if($pos === false) {
     echo "var isIE = false;";
   }
   else {
     echo "var isIE = true;";
   }

   echo "var doku_base = '" . DOKU_BASE ."'"; 
     
?>  

   
   function safe_convert(value) {            

     if(oDokuWiki_FCKEditorInstance.dwiki_fnencode && oDokuWiki_FCKEditorInstance.dwiki_fnencode == 'safe') {

 		if(value.match(/%25/ && value.match(/%25[a-z0-9]/))) {
                          value = value.replace(/%25/g,"%");
                          value =  SafeFN_decode(value,'safe');
                       }
        }
        return value;

     }

var HTMLParser_DEBUG = "";
function parse_wikitext(id) {

 var line_break = "\nL_BR_K  \n";
    var markup = { 'b': '**', 'i':'//', 'em': '//', 'u': '__', 'br':line_break, 
         'del': '<del>', 'strike': '<del>', p: "\n\n" , 'a':'[[', 'img': '\{\{',
         'h1': "\n====== ", 'h2': "\n===== ", 'h3': "\n==== ", 'h4': "\n=== ", 'h5': "\n== ",
         'td': "|", 'th': "^", 'tr':" ", 'table': "\n\n", 'ol':"  - ", 'ul': "  * ", 'li': "",
         'plugin': '<plugin ', 'code': "\'\'",'pre': "\n<", 'hr': "\n\n----\n\n", 'sub': '<sub>',
         //'font': "<font ",
         'font': "\n",
         'sup': '<sup>', 'div':"\n\n",'acronym': " ", 'span': "\n", 'dl': "\n", 'dd': "\n", 'dt': "\n"
     };
    var markup_end = { 'del': '</del>', 'strike': '</del>', 'p': " ", 'br':" ", 'a': ']] ','img': '\}\}',
          'h1': " ======\n", 'h2': " =====\n", 'h3': " ====\n", 'h4': " ===\n", 'h5': " ==\n", 
          'td': " ", 'th': " ", 'tr':"|\n", 'ol':" ", 'ul': " ", 'li': "\n", 'plugin': '</plugin>',
           'pre': "\n</",'sub': '</sub>', 'sup': '</sup>', 'div':"\n\n", 'p': "\n\n",
           'font': "</font>"
     }; 
   
    markup['blank'] = "";
    markup['fn_start'] = '((';
    markup['fn_end'] = '))';
    markup['row_span'] = ":::";
    markup['p_insert'] = '_PARA__TABLE_INS_';
    markup['format_space'] = '_FORMAT_SPACE_';
    markup['pre_td'] = '<';  //removes newline from before < which corrupts table
    var format_chars = {'b':true, 'i': true, 'em':true,'u':true, 'del':true,'strike':true, 'code':true};  
    
    var results=""; 
    var HTMLParser_LBR = false;
    var HTMLParser_PRE = false;
    var HTMLParser_Geshi = false;
    var HTMLParser_TABLE = false;
    var HTMLParser_PLUGIN = false;
    var HTMLParser_FORMAT_SPACE = false;
    var HTMLParser_MULTI_LINE_PLUGIN = false;
    var HTMLParser_NOWIKI = false;
    var HTMLFormatInList = false;
    var HTMLAcroInList = false;

    var HTMLParserTopNotes = new Array();
    var HTMLParserBottomNotes = new Array();
    var HTMLParserOpenAngleBracket = false;
    var HTMLParserParaInsert = markup['p_insert'];
 //   var geshi_classes = 'br0|co0|co1|co2|co3|coMULTI|es0|kw1|kw2|kw3|kw4|kw5|me1|me2|nu0|re0|re1|re2|re3|re4|st0|sy0|sy1|sy2|sy3|sy4';
      var geshi_classes = '(br|co|coMULTI|es|kw|me|nu|re|st|sy)[0-9]';

   geshi_classes = new RegExp(geshi_classes);
   HTMLParser(oDokuWiki_FCKEditorInstance.GetData( true ), {
    attribute: "",
    link_title: "",
    link_class: "",
    td_align: "",  
    td_colspan: 0,
    td_rowspan: 0,
    rowspan_col: 0, 
    last_column: -1,
    td_no: 0,
    tr_no: 0,
    in_table: false,
    in_multi_plugin: false,
    is_rowspan: false,
    list_level: 0, 
    prev_list_level: -1,
    list_started: false,
    xcl_markup: false,      
    in_link: false,
    link_formats: new Array(),  
    last_tag: "",
    code_type: false,
    in_endnotes: false,
    is_smiley: false,
    geshi: false,
    downloadable_code: false,
    export_code: false,
    code_snippet: false,
    downloadable_file: "", 
    external_mime: false,
    in_header: false,
    is_acronym: false, 
    curid: false,
    format_in_list: false,
    prev_li: new Array(),
    immutable_plugin: false,

    backup: function(c1,c2) {
        var c1_inx = results.lastIndexOf(c1);     // start position of chars to delete
        var c2_inx = results.indexOf(c2,c1_inx);  // position of expected next character
        if(c1_inx == -1 || c2_inx == -1) return;
        if(c1.length + c2_inx == c2_inx) {
            var left_side = results.substring(0,c1_inx); //from 0 up to but not including c1
            var right_side = results.substring(c2_inx);  //from c2 to end of string      
            results = left_side + right_side;
            return true;
        }
        return false;
    },

    start: function( tag, attrs, unary ) {

    if(markup[tag]) {   
      if(format_chars[tag] && this.in_link) {                 
                  this.link_formats.push(tag);
                  return; 
         }
     else if(tag == 'acronym') {
         if(this.list_level) {
             results+='_@ACRO_SPACE@_';
             HTMLAcroInList=true;
         }
          return;
     }
        if(tag == 'ol' || tag == 'ul') {    
            this.prev_list_level = this.list_level;
            this.list_level++;     
            if(this.list_level == 1) this.list_started = false;
//            if(this.list_started) this.prev_li = markup['li'] ;
             if(this.list_started) this.prev_li.push(markup['li']) ;
            markup['li'] = markup[tag];

            return;
        }
        else if(!this.list_level) {
             markup['li'] = "";
          //  this.prev_li = ""; 
              this.prev_li = new Array(); 
        }

        if(tag == 'img') {
            var img_size="?";
            var width;
            var height;
            var style = false;            
            var img_align = '';   
            var alt = "";                     
            this.is_smiley = false;
        }

        if(tag == 'a') {
            var local_image = true;
            var type = "";
            this.xcl_markup = false;  // set to false in end() as well, double sure
            this.in_link = true;
            this.link_pos = results.length;           
            this.link_formats = new Array();
            this.footnote = false;
            var bottom_note = false; 
            this.id = "";
            external_mime = false;
            var media_class=false;   
            this.export_code = false;
            this.code_snippet = false;
            this.downloadable_file = "";
            var qs_set = false;
        }
  
       if(tag == 'p') {         
          this.in_link = false;
          if(this.in_table) { 
              tag = 'p_insert';
              HTMLParser_TABLE=true;
          }
       }
       else if(tag == 'font') {
          var font_family = "arial";
          var font_size = "9pt";
          var font_weight = "normal";
       }
       
       if(tag == 'table') {
        this.td_no = 0;
        this.tr_no = 0;
        this.in_table = true; 
        this.is_rowspan = false;
       }
       else if(tag == 'tr') {
           this.tr_no++;
           this.td_no = 0;         
       }
       else if(tag == 'td' || tag == 'th') { 
          this.td_no++;           
          if(this.td_rowspan && this.rowspan_col == this.td_no && this.td_no != this.last_column) {
               this.is_rowspan = true;   
               this.td_rowspan --;
          }
          else {
              this.is_rowspan = false;   
          }
       
           
       }
       
       
        var matches;        
        this.attr=false;    
        this.td_align = '';
        this.format_tag = false;
        if(format_chars[tag])this.format_tag = true;
        var dwfck_note = false;  

        for ( var i = 0; i < attrs.length; i++ ) {     
    
           //alert(tag + ' ' + attrs[i].name + '="' + attrs[i].escaped + '"');
             if(attrs[i].escaped == 'u' && tag == 'em' ) {
                     tag = 'u';
                     this.attr='u'    
                     break;
              }

            if(tag == 'div') {
              if(attrs[i].name == 'class' && attrs[i].value == 'footnotes') {
                     tag = 'blank';
                     this.in_endnotes = true;
              }
               break;
            }
            if(tag == 'dl' && attrs[i].name == 'class' && attrs[i].value == 'file') {                  
                   this.downloadable_code = true;
                   HTMLParser_Geshi = true;
                   return;
            }
            if(tag == 'span' && attrs[i].name == 'class') {
                 if(attrs[i].value == 'np_break') return;
            }

            if(tag == 'span' && attrs[i].name == 'class') {
                  if(attrs[i].value =='curid') { 
                    this.curid = true;
                    return;
                  }
                  if(attrs[i].value == 'multi_p_open') {
                      this.in_multi_plugin = true;                  
                      HTMLParser_MULTI_LINE_PLUGIN = true;
                      return;
                  }
                  if(attrs[i].value == 'multi_p_close') {
                      this.in_multi_plugin = false;                     
                      return;
                  }
                 if(attrs[i].value.match(geshi_classes)) {
                    tag = 'blank';    
                    this.geshi = true;  
                    break;              
                 }
            }

            if(tag == 'span' && attrs[i].name == 'id') {                   
               if((matches= attrs[i].value.match(/imm_(\d+)/))) {                  
                   this.immutable_plugin = fckLImmutables[matches[1]];
               }
            }
            else if(tag == 'font') {
               if(attrs[i].name == 'face') {
                    font_family = attrs[i].value;
               }
               else if(attrs[i].name == 'style') {
                   matches = attrs[i].value.match(/font-size:\s*(\d+(\w+|%))/);
                   if(matches){
                     font_size = matches[1];
                   }
                   matches = attrs[i].value.match(/font-weight:\s*(\w+)/);   
                   if(matches) {
                      font_weight = matches[1];
                   }
               }
            
            }
            if(tag == 'td' || tag == 'th') {
              if(attrs[i].name == 'align') {
                 this.td_align =attrs[i].escaped;  
                               
              }
              else if(attrs[i].name == 'class') {
                   matches = attrs[i].value.match(/\s+(\w+)align/);
                   if(matches) {
                       this.td_align = matches[1];
                   }
              }
              else if(attrs[i].name == 'colspan') {
                  this.td_colspan =attrs[i].escaped;                
              }
              else if(attrs[i].name == 'rowspan') {
                  this.td_rowspan =attrs[i].escaped-1; 
                  this.rowspan_col = this.td_no;                
              }

                HTMLParser_TABLE=true;
            }

            if(tag == 'a') {
               if(attrs[i].name == 'title') {
                  this.link_title = attrs[i].escaped;        
               }
               else if(attrs[i].name == 'class') {
                  if(attrs[i].value.match(/fn_top/)) {
                     this.footnote = true;  
                  }
                  else if(attrs[i].value.match(/fn_bot/)) {
                     bottom_note = true;
                  }
                  this.link_class= attrs[i].escaped;                 
                  media_class = this.link_class.match(/mediafile/);              
               }
               else if(attrs[i].name == 'id') {
                  this.id = attrs[i].value;
               }
               else if(attrs[i].name == 'type') {
                  type = attrs[i].value;
               }               
            
              else if(attrs[i].name == 'href' && !this.code_type) {        

            // alert(tag + ' ' + attrs[i].name + '="' + attrs[i].escaped + '"', "top");             
                    // required to distinguish external images from external mime types 
                    // that are on the wiki which also use {{url}}
                    var media_type = attrs[i].escaped.match(/fetch\.php.*?media=.*?\.(png|gif|jpg|jpeg)$/i);
                    if(media_type) media_type = media_type[1];
                    
                    if(attrs[i].escaped.match(/^https*:/)) {
                       this.attr = attrs[i].escaped;
                       local_image = false;
                    }
                    else if(attrs[i].escaped.match(/do=export_code/)) {
                            this.export_code = true;
                    }
                    else if(attrs[i].escaped.match(/^nntp:/)) {
                       this.attr = attrs[i].escaped;
                       local_image = false;
                    }
                    else if(attrs[i].escaped.match(/^mailto:/)) {                       
                       this.attr = attrs[i].escaped.replace(/mailto:/,"");
                       local_image = false;
                    }
                    else if(attrs[i].escaped.match(/^file:/)) {  //samba share
                        var url= attrs[i].value.replace(/file:[\/]+/,"");
                        url = url.replace(/[\/]/g,'\\');
                        url = '\\\\' + url;
                        this.attr = url;
                        local_image = false;
                    }
                        // external mime types after they've been saved first time
                   else if(!media_type && (matches = attrs[i].escaped.match(/fetch\.php(.*)/)) ) { 
                         if(matches[1].match(/media=/)) {
                            elems = matches[1].split(/=/);
                            this.attr = elems[1];    
                         }
                         else {   // nice urls
                            matches[1] = matches[1].replace(/^\//,"");
                            this.attr = matches[1];
                         }
                         local_image = false;                        

                          this.attr = decodeURIComponent ? decodeURIComponent(this.attr) : unescape(this.attr);                
                          if(!this.attr.match(/^:/)) {      
                               this.attr = ':' +this.attr;
                         }
                         this.external_mime = true;
                   }
 
                    else {
                        local_image = false;

                        matches = attrs[i].escaped.match(/doku.php\?id=(.*)/); 

                        if(!matches) {
                            matches = attrs[i].escaped.match(/doku.php\/(.*)/); 
                        }
                        /* previously saved internal link with query string 
                          requires initial ? to be recognized by DW. In Anteater and later */
                        if(matches) {
                            if(!matches[1].match(/\?/) && matches[1].match(/&amp;/)) {
                                  qs_set = true;
                                  matches[1] = matches[1].replace(/&amp;/,'?')
                            }
                        }
                        if(matches && matches[1]) { 
                           if(!matches[1].match(/^:/)) {      
                               this.attr = ':' + matches[1];
                           }
                           else {
                                this.attr = matches[1];
                           }
;
                           if(this.attr.match(/\.\w+$/)) {  // external mime's first access 
                               if(type && type == 'other_mime') {
                                    this.external_mime = true; 
                               }
                               else {  
                                   for(var n = i+1; n < attrs.length; n++) { 
                                     if(attrs[n].value.match(/other_mime/)) 
                                        this.external_mime = true;  
                                        break;
                                    }

                               }
                           }

                        }
                        else {                    
                          matches = attrs[i].value.match(/\\\\/);   // Windows share
                          if(matches) {
                            this.attr = attrs[i].escaped;
                            local_image = false;
                          }
                        }
                   }

                   if(this.link_class == 'media') {
                        if(attrs[i].value.match(/http:/)) {
                         local_image = false;
                        }                        
                    }

                   if(!this.attr && this.link_title) {
                       if(this.link_class == 'media') {
                            this.attr=this.link_title;
                            local_image = true;
                       }
                    }

                   if(this.attr.match && this.attr.match(/%[a-fA-F0-9]{2}/)  && (matches = this.attr.match(/userfiles\/file\/(.*)/))) {
                      matches[1] = matches[1].replace(/\//g,':');
                      if(!matches[1].match(/^:/)) {      
                         matches[1] = ':' + matches[1];
                      }
                      this.attr = decodeURIComponent ? decodeURIComponent(matches[1]) : unescape(matches[1]);                               
                      this.attr = decodeURIComponent ? decodeURIComponent(this.attr) : unescape(this.attr); 
                      this.external_mime = true;

                   }

                   // alert('title: ' + this.link_title + '  class: ' + this.link_class + ' export: ' +this.export_code);
                    if(this.link_title.match(/Snippet/)) this.code_snippet = true;

                    /* anchors to current page without prefixing namespace:page */
                   if(attrs[i].value.match(/^#/) && this.link_class.match(/wikilink/)) {
                          this.attr = attrs[i].value;
                          this.link_title = false;
                   }

                        /* These two conditions catch user_rewrite not caught above */
                    if(this.link_class.match(/wikilink/) && this.link_title) {
                       this.external_mime = false;
                       if(!this.attr){
                             this.attr = this.link_title;          
                       }
                       if(!this.attr.match(/^:/)) {
                         this.attr = ':' + this.attr;
                       }

                   /* catch query strings attached to internal links for .htacess nice urls  */
                      if(!qs_set && attrs[i].name == 'href') { 
                         if(!this.attr.match(/\?.*?=/) && !attrs[i].value.match(/doku.php/)) {  
                           var qs = attrs[i].value.match(/(\?.*)$/);  
                            if(qs && qs[1]) this.attr += qs[1];
                         }

                      }
                    }
                   else if(this.link_class.match(/mediafile/) && this.link_title && !this.attr) {
                       this.attr =   this.link_title;         
                       this.external_mime = true;

                       if(!this.attr.match(/^:/)) {
                         this.attr = ':' + this.attr;
                       }
                   }

                   this.link_title = "";
                   this.link_class= "";

                   break;
                 }
            }

            if(tag == 'plugin') {
                  if(isIE) HTMLParser_PLUGIN = true;
                  if(attrs[i].name == 'title') {
                       this.attr = ' title="' + attrs[i].escaped + '" '; 
                       break;                          
                  }
             }

            if(tag == 'sup') {
               if(attrs[i].name == 'class') {                 
                   matches = attrs[i].value.split(/\s+/);
                   if(matches[0] == 'dwfcknote') {
                      this.attr = matches[0];
                      tag = 'blank';   
                      if(oDokuWiki_FCKEditorInstance.oinsertHtmlCodeObj.notes[matches[1]]) {
                          dwfck_note = '(('+ oDokuWiki_FCKEditorInstance.oinsertHtmlCodeObj.notes[matches[1]] + '))';
                      }
                     break;
                   }
               }
             }

            if(tag == 'pre') {
                if(attrs[i].name == 'class') {  
                  
                    var elems = attrs[i].escaped.split(/\s+/);
                    if(elems.length > 1) {                      
                        this.attr = attrs[i].value;
                        this.code_type = elems[0]; 
                    }
                    else {
                         this.attr = attrs[i].escaped;
                         this.code_type = this.attr;                   
                    }               
                    if(this.downloadable_code) {
                         this.attr = this.attr.replace(/\s*code\s*/,"");
                         this.code_type='file';    
                    }    
                    HTMLParser_PRE = true;
                    if(this.in_table) tag = 'pre_td';
                    break;                    
                }
                  
            }
   
            else if(tag == 'img') {
                if(attrs[i].name == 'alt') {                  
                     alt=attrs[i].value;
                }
                if(attrs[i].name == 'src') {                  
                  //  alert(attrs[i].name + ' = ' + attrs[i].value + ',  fnencode=' + oDokuWiki_FCKEditorInstance.dwiki_fnencode);

                    var src = "";  
                                // fetched by fetch.php
                    if(matches = attrs[i].escaped.match(/fetch\.php.*?(media=.*)/)) { 
                        var elems = matches[1].split('=');
                        src = elems[1];
                        if(matches = attrs[i].escaped.match(/(media.*)/)) {
                            var elems = matches[1].split('=');
                            var uri = elems[1];
                            src = decodeURIComponent ? decodeURIComponent(uri) : unescape(uri); 
                        }
                         if(!src.match(/http:/)  && !src.match(/^:/)) src = ':' + src;  
                     } 
                     else if(attrs[i].escaped.match(/http:\/\//)){
                              src = attrs[i].escaped;
                     }
                     // url rewrite 1
                     else if(matches = attrs[i].escaped.match(/\/_media\/(.*)/)) {                       
                         var elems  = matches[1].split(/\?/);
                         src = elems[0];
                         src = src.replace(/\//g,':');
                         if(!src.match(/^:/)) src = ':' + src;
                     }
                     // url rewrite 2
                     else if(matches = attrs[i].escaped.match(/\/lib\/exe\/fetch.php\/(.*)/)) {
                         var elems  = matches[1].split(/\?/);
                         src = elems[0];
                         if(!src.match(/^:/)) src = ':' + src;
                     }

                     else {   
                          // first insertion from media mananger   
                            matches = attrs[i].escaped.match(/^.*?\/userfiles\/image\/(.*)/); 

                            if(!matches) {  // windows style
                                var regex =  doku_base + 'data/media/';
                                regex = regex.replace(/([\/\\])/g, "\\$1");
                                regex = '^.*?' + regex + '(.*)';
                                regex = new RegExp(regex);                                                
                                matches = attrs[i].escaped.match(regex);
                            }
                            if(matches && matches[1]) { 
                               src = matches[1].replace(/\//g, ':');  
                               src = ':' + src;
                               src = safe_convert(src);
                            }
                           else {                  
                               src = decodeURIComponent ? decodeURIComponent(attrs[i].escaped) : unescape(attrs[i].escaped);        
                
                              // src = unescape(attrs[i].escaped);  // external image (or smiley) 

                           }
                          if(src.match(/lib\/images\/smileys/)) {
                                // src = 'http://' + window.location.host + src;
                                this.is_smiley = true;
                          }
                     }                 

                      this.attr = src;
                      if(this.attr.match && this.attr.match(/%[a-fA-F0-9]{2}/)) {                                         
                        this.attr = decodeURIComponent ? decodeURIComponent(this.attr) : unescape(this.attr); 
                        this.attr = decodeURIComponent ? decodeURIComponent(this.attr) : unescape(this.attr);                    
                      }



                }   // src end

                else if (attrs[i].name == 'width' && !style) {
                         width=attrs[i].value;                   
                    
                }
                else if (attrs[i].name == 'height' && !style) {
                        height=attrs[i].value;                    
                }
                else if(attrs[i].name == 'style') {  
                      var match = attrs[i].escaped.match(/width:\s*(\d+)/);
                      if(match) {
                           width=match[1];
                           var match = attrs[i].escaped.match(/height:\s*(\d+)/);
                           if(match) height = match[1];
                      }
                }
                else if(attrs[i].name == 'align' || attrs[i].name == 'class') {
                    if(attrs[i].escaped.match(/(center|middle)/)) {
                        img_align = 'center';                       
                    }
                    else if(attrs[i].escaped.match(/right/)) {                         
                          img_align = 'right';
                    }
                    else if(attrs[i].escaped.match(/left/)) {                         
                          img_align = 'left';
                    }
                   else {
                      img_align = '';
                   }
                }
            }   // End img
        }   // End Attributes Loop

           if(this.is_smiley) {
                 results += alt;
                 alt = "";
                 return;
           }
      
          if(tag == 'br') {  
                if(this.in_multi_plugin) {
                    results += "\n";
                    return;
                }

                if(!this.code_type) {
                   HTMLParser_LBR = true;
                }
                else if(this.code_type) {
                      results += "\n";
                      return;
                      
                }
                if(this.in_table) {
                   results += HTMLParserParaInsert;
                   return;
                }
                else {
                    results += '\\\\  ';
                    return;
                }
          }
          else if(tag.match(/^h(\d+|r)/)) {             
               var str_len = results.length;
               if(tag.match(/h(\d+)/)) {
                   this.in_header = true;
               }
               if(str_len) {                 
                  if(results.charCodeAt(str_len -1) == 32) {
                    results = results.replace(/\x20+$/,"");                    
                  }
               }
          }
          else if(this.last_col_pipes) {
               tag = 'blank';
          }
          else if(dwfck_note) {
           results += dwfck_note;
           return;              
         }
      
          if(tag == 'b' || tag == 'i'  && this.list_level) { 
                 if(results.match(/(\/\/|\*)(\x20)+/)) {
                     results = results.replace(/(\/\/|\*)(\x20+)\-/,"$1\n"+"$2-"); 
                  }
          }

         if(tag == 'li' && this.list_level) { 
              if(this.list_level == 1 & !this.list_started) { 
                    results += "\n";
                    this.list_started = true;
              }
              results = results.replace(/[\x20]+$/,"");  

              for(var s=0; s < this.list_level; s++) {

                  // this occurs when an acronym is enclosed in format characters  

                  if(results.match(/_@ACRO_SPACE@_\s*(\w+\s*[\-,:;!_]+)(_@ACRO_SPACE@_)/)) {  
                      results = results.replace(/_@ACRO_SPACE@_\s*(\w+\s*[\-,:;!_]+)(_@ACRO_SPACE@_)\s*$/,"$1");  
                  }

                  if(results.match(/_FORMAT_SPACE_\s*_@ACRO_SPACE@_\s*(\w+\s*[\-,:;!_]+)(_@ACRO_SPACE@_)?/)) {  
                      results = results.replace(/_FORMAT_SPACE_\s*_@ACRO_SPACE@_\s*(\w+\s*[\-,:;!_]+)(_@ACRO_SPACE@_)?\s*$/,"$1");  
                  }

                  // this handles format characters at the ends of list lines
                  if(results.match(/_FORMAT_SPACE_\s*$/)) {   
                      results = results.replace(/_FORMAT_SPACE_\s*$/,"\n");  
                  }

                  // this handles acronyms at the ends of list lines
                  if(results.match(/_@ACRO_SPACE@_\s*(\w+)$/)) {   
                      results = results.replace(/_@ACRO_SPACE@_\s*(\w+[\-,:;!_]?)$/," $1\n");  
                  }

                  results = results.replace(/_@ACRO_SPACE@_/g," ");

                  results += '  ';

              }
             
             if(this.prev_list_level > 0 && markup['li'] == markup['ol']) {
                this.prev_list_level = -1;
                /* if(this.last_tag == 'b' || this.last_tag == 'i') {                      
                     results += "\n";
                } */
             }
          }

          if(tag == 'a' &&  local_image) {
                 this.xcl_markup = true;
                 return;
          }
          else if(tag == 'a' && (this.export_code || this.code_snippet)) {
                return;
          }

          else if (tag == 'a' && this.footnote) {             
             tag = 'fn_start';      
          }
          else if(tag == 'a' && bottom_note) {
                HTMLParserTopNotes.push(this.id);
          }
          else if(tag == 'a' && this.external_mime) {
               results += markup['img'];
               if(this.attr) {
                   results += this.attr + '|';
               }
               return;
          }
          else if(tag == 'font') {
              //<font 18pt:bold/garamond>
               var font_tag = '<font ' + font_size + ':'+ font_weight + '/'+font_family+'>';
               results += font_tag;   
               return;            
       }

          if(this.in_endnotes && tag == 'a') return; 

          results += markup[tag];

          if(tag == 'td' || tag == 'th') {
              if(this.is_rowspan) {          
                results +=  markup['row_span'] + ' | ';
                this.is_rowspan = false;             
             }
             if(this.td_align == 'center' || this.td_align == 'right') {
                 results += '  ';
             }
          }
          else if(tag == 'a' && this.attr) {
              results += this.attr + '|';
          }
          else if(tag == 'img') {      
               if(width && height) {
                  img_size +=width + 'x' + height;                  
               }
               else if(width) {
                  img_size +=width;                  
               }
               else {
                  img_size="";
               }
               if(img_align && img_align != 'left') {
                  results += '  ';
               }
               this.attr += img_size;
               if(img_align == 'center' || img_align == 'left') {          
                  this.attr += '  '; 
               }
           
               results += this.attr + '}}';
               this.attr = 'src';
          }
          else if(tag == 'plugin' || tag == 'pre' || tag == 'pre_td') {               
               if(this.downloadable_file) this.attr += ' ' +  this.downloadable_file; 
               if(!this.attr) this.attr = 'code';          
               results += this.attr + '>'; 
               this.downloadable_file = "";
               this.downloadable_code = false;
          }


        }   // if markup tag
    },

    end: function( tag ) {


    if(this.in_endnotes && tag == 'a') return;

    if(!markup[tag]) return; 
     if(tag == 'sup' && this.attr == 'dwfcknote') {         
         return;   
     }
     if(this.is_smiley) {
        this.is_smiley = false;
        return;
     }
     if(tag == 'span' && this.curid) {
             this.curid = false;
             return; 
     }
     if(tag == 'dl' && this.downloadable_code) {
         this.downloadable_code = false;
         return;
     }

     if(tag == 'a' && (this.export_code || this.code_snippet)) {
          this.export_code = false;
          this.code_snippet = false;
          return; 
      }
     var current_tag = tag;
     if(this.footnote) {
       tag = 'fn_end';
      this.footnote = false; 
     }
     else if(tag == 'a' && this.xcl_markup) {
         this.xcl_markup = false;
         return; 
     }
     else if(tag == 'table') {
        this.in_table = false;  
       }

     if(tag == 'p' && this.in_table) {              
              tag = 'p_insert';
              HTMLParser_TABLE=true;
     }
     if(this.geshi) {
        this.geshi = false;
        return; 
     }

     if(tag == 'code') {     // empty code markup corrupts results
           if(results.match(/''\s*$/m)) {                     
             results = results.replace(/''\s*$/, "\n");                
             return;
           }       
           
     }

    else if(tag == 'a' && this.attr == 'src') {
            // if local image without link content, as in <a . . .></a>, delete link markup 
          if(this.backup('\[\[', '\{')) return;  
    }
   
    if(tag == 'ol' || tag == 'ul') {  
            this.list_level--;    
            if(!this.list_level) this.format_in_list = false;
            if(this.prev_li.length) {
            markup['li']= this.prev_li.pop();
            }
            tag = "\n\n";
    }
    else if(tag == 'a' && this.external_mime) {
           tag = '}} ';
           this.external_mime = false;
    }
    else if(tag == 'pre') {
          tag = markup_end[tag];
          if(this.code_type) {        
           tag += this.code_type + ">"; 
          }
          else {
             var codeinx = results.lastIndexOf('code');
             var fileinx = results.lastIndexOf('file');
             if(fileinx > codeinx) {
               this.code_type = 'file'; 
            }
            else this.code_type = 'code';
            tag += this.code_type + ">"; 
          }       
         this.code_type = false;
        
    }
    else if(markup_end[tag]) {
            tag = markup_end[tag];
    }
    else if(this.attr == 'u' && tag == 'em' ) {
            tag = 'u';
    }  
    else if(tag == 'acronym') {
       tag = '_@ACRO_SPACE@_';
       this.is_acronym = true;
    }
    else {
           tag = markup[tag];

     }

    if(current_tag == 'tr') {
       if(this.last_col_pipes) {
            tag = "\n";
            this.last_col_pipes = "";
       }

     if(this.td_rowspan && this.rowspan_col == this.td_no+1) {
               this.is_rowspan = false;   
               this.last_column = this.td_no; 
               this.td_rowspan --;             
               tag  = '|' + markup['row_span'] + "|\n";
      }
    }
    else if(current_tag == 'td') {
       this.last_col_pipes = "";     
    }
    
   else if (current_tag.match(/h\d+/)) {
           this.in_header = false;
    }


    if(markup['li']) { 

         if(results.match(/\n$/)) {
                  tag = "";
        }
     // if(current_tag != 'li') markup['li'] = "";
     }

     if(this.in_link && format_chars[current_tag] && this.link_formats.length) {            
           return;
     }       

       results += tag;

      if(format_chars[current_tag]) {               
            if(this.list_level) {
                  this.format_in_list = true; 
                  HTMLFormatInList = true;
            }
            results += markup['format_space'];
            HTMLParser_FORMAT_SPACE =  markup['format_space'];            
       }
        this.last_tag = current_tag;

        if(this.td_colspan) {    
            if(this.td_align == 'center') results += ' ';    
            var colspan = "|";
            for(var i=1; i < this.td_colspan; i++) {
                colspan += '|'; 
            }
            this.last_col_pipes = colspan;
            results += colspan;
            this.td_colspan = ""; 
          }
          else if(this.td_align == 'center') {
                results += ' ';
               this.td_align = '';
          }

      if(current_tag == 'a' && this.link_formats.length) {  
            var end_str = results.substring(this.link_pos);        
            var start_str =  results.substring(0,this.link_pos);
            var start_format = "";
            var end_format = "";
            for(var i=0; i < this.link_formats.length; i++) {
                 var fmt = markup[this.link_formats[i]];
                 var endfmt = markup_end[this.link_formats[i]] ? markup_end[this.link_formats[i]]: fmt; 
                 start_format += markup[this.link_formats[i]];
                 end_format = endfmt + end_format;
            }
        
            start_str += start_format;
            end_str += end_format;           
            results = start_str + end_str; 
            this.link_formats = new Array();
            this.in_link = false;
         }
         else if(current_tag == 'a') {
            this.link_formats = new Array();
            this.in_link = false;

         }
         else if(current_tag == 'span' ) {
                  this.immutable_plugin = false;
         }

    },

    chars: function( text ) {

    if(!this.code_type) { 
        text = text.replace(/\x20{6,}/, "   "); 
        text = text.replace(/^(&nbsp;)+/, '');
        text = text.replace(/(&nbsp;)+/, ' ');   
        if(this.immutable_plugin) {
             text = this.immutable_plugin;
             text = text.replace(/\/\/<\/\//g,'<');
             this.immutable_plugin = false;
        }
        if(this.format_tag) {
          if(!this.list_started) text = text.replace(/^\s+/, '@@_SP_@@');  
        }
        else text = text.replace(/^\s+/, '');  

        if(text.match(/nowiki&gt;/)) {  
	       HTMLParser_NOWIKI=true;   
	   }

        if(this.is_acronym) {
          this.is_acronym = false;
          if(text.match(/^[\-,:;!_]/)) { 
             results = results.replace(/_@ACRO_SPACE@_\s*$/,"");  
          }
          else results = results.replace(/_@ACRO_SPACE@_\s*$/," "); 
        }
        if(this.format_in_list ) {  
           text = text.replace(/^[\n\s]+$/g, '');       
        }
    }
    else {
      text = text.replace(/&lt;\s/g, '<');   
      text = text.replace(/\s&gt;/g, '>');            
    }

    if(this.attr && this.attr == 'dwfcknote') {
         if(text.match(/fckgL\d+/)) {
             return;
         }
          if(text.match(/^[\-,:;!_]/)) {
            results +=  text;
          }
          else {  
            results += ' ' + text;
          }
          return;       
    }
    if(this.downloadable_code &&  (this.export_code || this.code_snippet)) {
          this.downloadable_file = text;          
          return;    
    }
 
   /* remove space between link end markup and following punctuation */
    if(this.last_tag == 'a' && text.match(/^[\.,;\:\!]/)) {      
        results=results.replace(/\s$/,"");
    }

    if(this.in_header) {
      text = text.replace(/---/g,'&mdash;');
      text = text.replace(/--/g,'&ndash;');     
    }

    if(!this.code_type) {   // keep special character literals outside of code block
                              // don't touch samba share or Windows path backslashes
        if(!results.match(/\[\[\\\\.*?\|$/) && !text.match(/\w:(\\(\w?))+/ ))
         {
             text = text.replace(/([\*\\])/g, '%%$1%%');            
         }
    }

    if(this.in_endnotes && HTMLParserTopNotes.length) {
     if(text.match(/\w/) && ! text.match(/\d\)/)) {
        var index = HTMLParserTopNotes.length-1; 
        if(HTMLParserBottomNotes[HTMLParserTopNotes[index]])
           HTMLParserBottomNotes[HTMLParserTopNotes[index]] += ' ' + text;
        else HTMLParserBottomNotes[HTMLParserTopNotes[index]] = text;
     }
     return;    
    }


    if(HTMLParser_PLUGIN) {
      HTMLParser_PLUGIN=false; 
      if(results.match(/>\s*<\/plugin>\s*$/)) {        
        results = results.replace(/\s*<\/plugin>\s*$/, text + '<\/plugin>');   
        return;  
      }   
   } 
   if(text && text.length) { 
      results += text;        
   }

  if(!HTMLParserOpenAngleBracket) {
       if(text.match(/&lt;/)) {
         HTMLParserOpenAngleBracket = true;
       }
  }
    },

    comment: function( text ) {
     // results += "<!--" + text + "-->";
    },

    dbg: function(text, heading) {
        <?php if($this->debug) { ?>
         if(text.replace) {
             text = text.replace(/^\s+/g,"");
             text = text.replace(/^\n$/g,"");
             if(!text) return;
         }
         if(heading) { 
            heading = '<b>'+heading+"</b>\n";
         }
         HTMLParser_DEBUG += heading + text + "\n__________\n";
       <?php } ?>
    }

    }
    );


    for(var i=0; i < fckgLPluginPatterns.length; i++) {
      fckgLPluginPatterns[i].pat = fckgLPluginPatterns[i].pat.replace(/\|/g,"\\|");
      var pattern = new RegExp(fckgLPluginPatterns[i].pat,"gm");     
      results = results.replace(pattern, fckgLPluginPatterns[i].orig);
}
    /*
      we allow escaping of troublesome characters in plugins by enclosing them withinback slashes, as in \*\
      the escapes are removed here together with any DW percent escapes
   */

     results = results.replace(/%*\\%*([^\\%]{1})%*\\%*/g, "$1"); 
     
 
    if(id == 'test') {
      if(!HTMLParser_test_result(results)) return;     
    }

    if(HTMLParser_FORMAT_SPACE) { 

        results = results.replace(/&quot;/g,'"');
        var regex = new RegExp(HTMLParser_FORMAT_SPACE + '([\\-]{2,})', "g");
        results = results.replace(regex," $1");
        var regex = new RegExp("(\\w|\\d)(\\*\\*|\\/\\/|\\'\\'|__|<\/del>)" + HTMLParser_FORMAT_SPACE + '(\\w|\\d)',"g");
        results = results.replace(regex,"$1$2$3");
        var regex = new RegExp(HTMLParser_FORMAT_SPACE + '@@_SP_@@',"g");
        results = results.replace(regex,' ');
        results = results.replace(/\n@@_SP_@@\n/g,'');
        results = results.replace(/@@_SP_@@\n/g,'');
        results = results.replace(/@@_SP_@@/g,'');
        var regex = new RegExp(HTMLParser_FORMAT_SPACE + '([^\\)\\]\\}\\{\\-\\.,;:\\!\?"\x94\x92\u201D\u2019' + "'" + '])',"g");
        results = results.replace(regex," $1");
        regex = new RegExp(HTMLParser_FORMAT_SPACE,"g");
        results = results.replace(regex,'');

         if(HTMLFormatInList) {   
             /* removes extra newlines from lists */      
             results =  results.replace(/(\s+[\-\*_]\s*)([\*\/_\']{2})(.*?)(\2)([^\n]*)\n+/gm, 
                        function(match,list_type,format,text, list_type_close, rest) {
                           return(list_type+format+text+list_type_close+rest +"\n");
             }); 
         }
    }

    if(HTMLAcroInList) {
        results = results.replace(/_@ACRO_SPACE@_/g," ");
    }
    var line_break_final = "\\\\";

    if(HTMLParser_LBR) {
        results = results.replace(/(L_BR_K)+/g,line_break_final);
        results = results.replace(/L_BR_K/gm, line_break_final);
    }


    if(HTMLParser_PRE) {  
      results = results.replace(/\s+<\/(code|file)>/g, "\n</" + "$1" + ">");
      if(HTMLParser_Geshi) {
        results = results.replace(/\s+;/mg, ";");
      }
    }

    if(HTMLParser_TABLE) { 
     results += "\n" + line_break_final + "\n";
     var regex = new RegExp(HTMLParserParaInsert,"g");
     results = results.replace(regex, ' ' +line_break_final + ' ');
    }

    if(HTMLParserOpenAngleBracket) {
         results = results.replace(/\/\/&lt;\/\/\s*/g,'&lt;');
    }
   if(HTMLParserTopNotes.length) {
        results = results.replace(/\(\(+(\d+)\)\)+/,"(($1))");   
        for(var i in HTMLParserBottomNotes) {  // re-insert DW's bottom notes at text level
            var matches =  i.match(/_(\d+)/);    
            var pattern = new RegExp('(\<sup\>)*[\(]+' + matches[1] +  '[\)]+(<\/sup>)*');          
            results = results.replace(pattern,'((' + HTMLParserBottomNotes[i] +'))');
         }
       results = results.replace(/<sup><\/sup>/g, "");
    }

    results = results.replace(/(={3,}.*?)(\{\{.*?\}\})(.*?={3,})/g,"$1$3\n\n$2");
    // remove any empty footnote markup left after section re-edits
    results = results.replace(/(<sup>)*\s*\[\[\s*\]\]\s*(<\/sup>)*\n*/g,""); 
    
    if(HTMLParser_MULTI_LINE_PLUGIN) {
        results = results.replace(/<\s+/g, '<');
        results = results.replace(/&lt;\s+/g, '<');
    }

   if(HTMLParser_NOWIKI) {
      /* any characters escaped by DW %%<char>%% are replaced by NOWIKI_<char>
         <char> is restored in save.php
     */
      var nowiki_escapes = '%';  //this technique allows for added chars to attach to NOWIKI_$1_
      var regex = new RegExp('([' + nowiki_escapes + '])', "g");
                 
      results=results.replace(/(&lt;nowiki&gt;)(.*?)(&lt;\/nowiki&gt;)/mg,
             function(all,start,mid,close) {
                     mid = mid.replace(/%%(.)%%/mg,"NOWIKI_$1_");
                     return start + mid.replace(regex,"NOWIKI_$1_") + close; 
             });
    }
    
    results = results.replace(/\n{3,}/g,'\n\n');
   
    if(id == 'test') {
      if(HTMLParser_test_result(results)) {
         alert(results);
      }
      return; 
    }

    var dwform = $('dw__editform');
    dwform.elements.fck_wikitext.value = results;

   if(id == 'bakup') {
      //alert(results);
      return;
   }
    if(id) {
       var dom =  $(id);
      dom.click();
      return true;
    }
}

<?php if($this->debug) { ?>
   function HTMLParser_debug() {        
       HTMLParser_DEBUG = "";
       parse_wikitext("");
/*
      for(var i in oDokuWiki_FCKEditorInstance) {     
         HTMLParser_DEBUG += i + ' = ' + oDokuWiki_FCKEditorInstance[i] + "\n";;
       }
*/

       var w = window.open();       
       w.document.write('<pre>' + HTMLParser_DEBUG + '</pre>');
       w.document.close();
  }
<?php } ?>

<?php  
  
  $url = DOKU_URL . 'lib/plugins/fckg/scripts/script-cmpr.js';    
  echo "var script_url = '$url';";
//  $safe_url = DOKU_URL . 'lib/plugins/fckg/scripts/safeFN_cmpr.js';       
?>


try {
if(!HTMLParserInstalled){};
}
catch (ex) {  
   LoadScript(script_url); 
}


if(window.DWikifnEncode && window.DWikifnEncode == 'safe') {
   LoadScript(DOKU_BASE + 'lib/plugins/fckg/scripts/safeFN_cmpr.js' );
}


 //]]>

  </script>


         </div>
<?php } ?>

      <?php if($wr){ ?>
        <div class="summary">
           <label for="edit__summary" class="nowrap"><?php echo $lang['summary']?>:</label>
           <input type="text" class="edit" name="summary" id="edit__summary" size="50" value="<?php echo formText($SUM)?>" tabindex="2" />
           <label class="nowrap" for="minoredit"><input type="checkbox" id="minoredit" name="minor" value="1" tabindex="3" /> <span>Minor Changes</span></label>
        </div>
      <?php }?>
  </div>
  </form>
  
  <div id="draft__status"></div>
  
<?php
    }

    /**
     * Renders a list of instruction to minimal xhtml
     *@author Myron Turner <turnermm02@shaw.ca>
     */
    function _render_xhtml($text){
        $mode = 'fckg';

       global $Smilies;
       $smiley_as_text = @$this->getConf('smiley_as_text');
       if($smiley_as_text) {

           $Smilies = array('8-)'=>'aSMILEY_1', '8-O'=>'aSMILEY_2',  ':-('=>'aSMILEY_3',  ':-)'=>'aSMILEY_4',
             '=)' => 'aSMILEY_5',  ':-/' => 'aSMILEY_6', ':-\\' => 'aSMILEY_7', ':-?' => 'aSMILEY_8',
             ':-D'=>'aSMILEY_9',  ':-P'=>'bSMILEY_10',  ':-O'=>'bSMILEY_11',  ':-X'=>'bSMILEY_12',
             ':-|'=>'bSMILEY_13',  ';-)'=>'bSMILEY_14',  '^_^'=>'bSMILEY_15',  ':?:'=>'bSMILEY_16', 
             ':!:'=>'bSMILEY_17',  'LOL'=>'bSMILEY_18',  'FIXME'=>'bSMILEY_19',  'DELETEME'=>'bSMILEY_20');

          $s_values = array_values($Smilies);
          $s_values_regex = implode('|', $s_values);
            $s_keys = array_keys($Smilies);
            $s_keys = array_map  ( create_function(
               '$k',     
               'return "(" . preg_quote($k,"/") . ")";'
           ) ,
            $s_keys );



           $s_keys_regex = implode('|', $s_keys);
             global $haveDokuSmilies;
             $haveDokuSmilies = false;
             $text = preg_replace_callback(
                '/(' . $s_keys_regex . ')/ms', 
                 create_function(
                '$matches',
                'global $Smilies;   
                 global $haveDokuSmilies;
                 $haveDokuSmilies = true;
                 return $Smilies[$matches[1]];'
                 ), $text
             );

       }

        // try default renderer first:
        $file = DOKU_INC."inc/parser/$mode.php";

        if(@file_exists($file)){
	
            require_once $file;
            $rclass = "Doku_Renderer_$mode";

            if ( !class_exists($rclass) ) {
                trigger_error("Unable to resolve render class $rclass",E_USER_WARNING);
                msg("Renderer for $mode not valid",-1);
                return null;
            }
            $Renderer = & new $rclass();
        }
        else{
            // Maybe a plugin is available?
            $Renderer =& plugin_load('renderer',$mode);	    
            if(is_null($Renderer)){
                msg("No renderer for $mode found",-1);
                return null;
            }
        }
        
        // prevents utf8 conversions of quotation marks
         $text = str_replace('"',"_fckg_QUOT_",$text);        

         $text = preg_replace_callback('/(<code|file.*?>)(.*?)(<\/code>)/ms',
             create_function(
               '$matches',              
               '$quot =  str_replace("_fckg_QUOT_",\'"\',$matches[2]); 
                $quot = str_replace("\\\\ ","_fckg_NL",$quot); 
                return $matches[1] . $quot . $matches[3];' 
          ), $text); 

        global $fckgLPluginPatterns;
        $fckgLPluginPatterns = array();

        $instructions = p_get_instructions("=== header ==="); // loads DOKU_PLUGINS array --M.T. Dec 22 2009

        $installed_plugins = $this->get_plugins();
        $regexes = $installed_plugins['plugins'];

        $text = preg_replace_callback('/('. $regexes .')/', 
                create_function(
                '$matches', 
                'global $fckgLPluginPatterns;                                 
                 $retv =  preg_replace("/([\{\}:&~\?\!<>])/", "$1 ", $matches[0]);            
                 $fckgLPluginPatterns[] = array($retv, $matches[0]);
                 return $retv;' 
               ),
          $text);

        global $fckLImmutables;                                 
        $fckglImmutables=array();           

         foreach($installed_plugins['xcl'] as $xcl) {                           
               $text = preg_replace_callback('/'. $xcl . '/',
               create_function(
               '$matches',
              'global $fckLImmutables;
               if(preg_match("#//<//font#",$matches[0])) {
                   return str_replace("//<//", "<", $matches[0]);
               }
               $index = count($fckLImmutables);
               $fckLImmutables[] = $matches[0]; 
               return "<span id=\'imm_" . "$index\' title=\'imm_" . "$index\' >" . str_replace("//<//", "<", $matches[0]) . "</span>" ;' 
              
              ),
          $text);   

          }   

            global $multi_block;
            if(preg_match('/(?=MULTI_PLUGIN_OPEN)(.*?)(?<=MULTI_PLUGIN_CLOSE)/ms', $text, $matches)) {
             //file_put_contents('multi_text-2.txt',$matches[1]);             
             $multi_block = $matches[1];
           }

        
        
        $instructions = p_get_instructions($text);
        if(is_null($instructions)) return '';
              

        $Renderer->notoc();
        $Renderer->smileys = getSmileys();
        $Renderer->entities = getEntities();
        $Renderer->acronyms = getAcronyms();
        $Renderer->interwiki = getInterwiki();
        #$Renderer->badwords = getBadWords();

        // Loop through the instructions
        foreach ( $instructions as $instruction ) {
            // Execute the callback against the Renderer
            call_user_func_array(array(&$Renderer, $instruction[0]),$instruction[1]);
        }

        //set info array
        $info = $Renderer->info;

        // Post process and return the output
        $data = array($mode,& $Renderer->doc);
        trigger_event('RENDERER_CONTENT_POSTPROCESS',$data);
        $xhtml = $Renderer->doc;

        $pos = strpos($xhtml, 'MULTI_PLUGIN_OPEN');
        if($pos !== false) {
           $xhtml = preg_replace('/MULTI_PLUGIN_OPEN.*?MULTI_PLUGIN_CLOSE/ms', $multi_block, $xhtml);
           $xhtml = preg_replace_callback(
            '|MULTI_PLUGIN_OPEN.*?MULTI_PLUGIN_CLOSE|ms',
            create_function(
                '$matches',                          
                  '$matches[0] = str_replace("//<//", "< ",$matches[0]);
                  return preg_replace("/\n/ms","<br />",$matches[0]);'            
            ),
            $xhtml
          );
           
           $xhtml = preg_replace('/~\s*~\s*MULTI_PLUGIN_OPEN~\s*~/', "~ ~ MULTI_PLUGIN_OPEN~ ~\n\n<span class='multi_p_open'>\n\n</span>", $xhtml);
           $xhtml = preg_replace('/~\s*~\s*MULTI_PLUGIN_CLOSE~\s*~/', "<span class='multi_p_close'>\n\n</span>\n\n~ ~ MULTI_PLUGIN_CLOSE~ ~\n", $xhtml);


        }  

        $xhtml = str_replace('_fckg_NPBBR_', "<span class='np_break'>&nbsp;</span>", $xhtml);
        $xhtml = str_replace('_fckg_QUOT_', '&quot;', $xhtml);
        $xhtml = str_replace('_fckg_NL', "\n", $xhtml);
        $xhtml = str_replace('</pre>', "\n\n</pre>", $xhtml);

       if($smiley_as_text) {
           if($haveDokuSmilies) {
                 $s_values = array_values($Smilies);
                 $s_values_regex = (implode('|', $s_values));

                 $xhtml = preg_replace_callback(
                     '/(' . $s_values_regex . ')/ms', 
                     create_function(
                     '$matches',
                    'global $Smilies;     
                     return array_search($matches[1],$Smilies); '
                     ), $xhtml
                 );
            }
          }

        return $xhtml;
    }

  function write_debug($what) {
     $handle = fopen("edit_php.txt", "a");
     if(is_array($what)) $what = print_r($what,true);
     fwrite($handle,"$what\n");
     fclose($handle);
  }
 /**
  *  @author Myron Turner <turnermm02@shaw.ca>
  *  Converts FCK extended syntax to native DokuWiki syntax
 */
  function fck_convert_text(&$event) {
  }


 function big_file() {   
 }

/**
 * get regular expressions for installed plugins 
 * @author     Myron Turner <turnermm02@shaw.ca>
 * return string of regexes suitable for PCRE matching
*/
 function get_plugins() {
 global $DOKU_PLUGINS;

 $list = plugin_list('syntax');
 $data =  $DOKU_PLUGINS['syntax'][$list[0]]->Lexer->_regexes['base']->_labels; 
 $patterns = $DOKU_PLUGINS['syntax'][$list[0]]->Lexer->_regexes['base']->_patterns;
 $labels = array();
 $regex = '~~NOCACHE~~';
 

 $exclusions = $this->getConf('xcl_plugins');
 $exclusions = trim($exclusions, " ,");
 $exclusions = explode  (',', $exclusions);
 $exclusions[] = 'fckg_font';
 $list = array_diff($list,$exclusions);

 foreach($list as $plugin) {
   if(preg_match('/fckg_dwplugin/',$plugin)) continue;
   $plugin = 'plugin_' . $plugin;

   $indices = array_keys($data, $plugin);
   if(empty($indices)) {
       $plugin = '_' . $plugin;

      $indices = array_keys($data, $plugin);
   }

   if(!empty($indices)) {
       foreach($indices as $index) {
          $labels[] = "$index: " . $patterns[$index];         
          $pattern = $patterns[$index];       
          $regex .= "|$pattern";       
       }
    }
  
 }
 $regex = ltrim($regex, '|'); 

 $regex_xcl = array();
 foreach($exclusions as $plugin) {
   if(preg_match('/fckg_dwplugin/',$plugin)) continue;
   $plugin = 'plugin_' . $plugin;

   $indices = array_keys($data, $plugin);
   if(empty($indices)) {
       $plugin = '_' . $plugin;
      $indices = array_keys($data, $plugin);
   }

   if(!empty($indices)) {
       foreach($indices as $index) { 
            $pos = strpos($patterns[$index],'<');
            if($pos !== false) {
               $pattern = str_replace('<', '\/\/<\/\/', $patterns[$index]);
               $pattern = str_replace('?=',"",$pattern);
               $regex_xcl[] = $pattern; 
            }
          }
       }
    }

 //file_put_contents('exclusions.txt', print_r($regex_xcl,true));
 return array('plugins'=> $regex, 'xcl'=> $regex_xcl);
 return $regex; 


 }

} //end of action class


?>
