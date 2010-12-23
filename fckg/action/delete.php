<?php
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');

/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Pierre Spring <pierre.spring@liip.ch>
 */

class action_plugin_fckg_delete extends DokuWiki_Action_Plugin {
    //store the namespaces for sorting
    var $sortEdit = array();
    var $helper = false;
    var $commit = false;

    /**
     * Constructor
     */
    function action_plugin_fckg_delete(){
    }

    function getInfo() {
        return array(
            'author' => 'Pierre Spring',
            'email'  => 'pierre.spring@liip.ch',
            'date'   => '2007-Mai-08',
            'name'   => 'fckg_delete',
            'desc'   => 'Delete Plugin for the Dokuwiki FCKW Plugin',
            'url'    => 'https://fosswiki.liip.ch/display/FCKW/Home');
    }

    function register(&$controller) {
        $controller->register_hook('DOKUWIKI_STARTED', 'BEFORE', $this, 'fckg_delete_preprocess');
    }

    function fckg_delete_preprocess(&$event){
        global $ACT;
        if (! is_array($ACT) || !(isset($ACT['delete']))) return;
        global $TEXT;
        $TEXT = NULL;
        unset($ACT['delete']);
        $ACT['save'] = "Speichern";
    }


} //end of action class
?>
