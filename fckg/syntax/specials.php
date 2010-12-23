<?php

/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
   *
   * class       plugin_fckg_specials
   * @author     Myron Turner <turnermm02@shaw.ca>
*/
        
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
define ('FCKG_IMAGES', DOKU_URL . 'lib/plugins/fckg/images/');
define ('FCK_IMG_PATH',DOKU_INC . 'lib/plugins/fckg/images/');
if(!defined('DOKU_LF')) define ('DOKU_LF',"\n");
if(!defined('DOKU_TAB')) define ('DOKU_TAB',"\t");

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_fckg_specials extends DokuWiki_Syntax_Plugin {

    /**
     * return some info
     */
    function getInfo(){
        return array(
            'author' => 'Myron Turner',
            'email'  => 'turnermm02@shaw.ca',
            'date'   => '8 Sep 2008',
            'name'   => 'fckg_specials',
            'desc'   => 'Ouputs html for misc syntax patterns',
            'url'    => 'http://mturner.org/fckgLite',
        );
    }

    /**
     * What kind of syntax are we?
     */
    function getType(){
        return 'substition';
    }
   
    /**
     * What about paragraphs?
     */

    function getPType(){
       //  return 'stack';
       return 'block';
    }

    /**
     * Where to sort in?
     */ 
    function getSort(){
        return 155;
    }


    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {        
                                                     
          $this->Lexer->addSpecialPattern('~~MULTI_PLUGIN_OPEN~~',$mode,'plugin_fckg_specials');
          $this->Lexer->addSpecialPattern('~~MULTI_PLUGIN_CLOSE~~',$mode,'plugin_fckg_specials');
    }


    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler){

        $class = "";  
        $xhtml = "";

        if(preg_match('/OPEN/', $match)) {
          
           return array($state, "<span class='multi_p_open'></span>" );
        }       
        elseif(preg_match('/CLOSE/', $match)) {
              return array($state, "<span class='multi_p_close'></span>" );
        }       
         return array($state, "" );
       
    }

    /**
     * Create output
     */
    function render($mode, &$renderer, $data) {
        if($mode == 'xhtml'){
            list($state, $xhtml) = $data;
            $renderer->doc .=  DOKU_LF . $xhtml . DOKU_LF;
            return true;
        }
        return false;
    }
    
  function write_debug($what) {
     $handle = fopen("blog_pats.txt", "a");
     fwrite($handle,"$what\n");
     fclose($handle);
  }
}


