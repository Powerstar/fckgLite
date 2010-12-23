<?php
/**
 * Renderer for XHTML output
 *
 * @author Pierre Spring <pierre.spring@liip.ch>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

// we inherit from the XHTML renderer instead directly of the base renderer
require_once DOKU_INC.'inc/parser/xhtml.php';

/**
 * The Renderer
 */
class renderer_plugin_fckg extends Doku_Renderer_xhtml 
{

    /**
     * return some info
     */
    function getInfo()
    {
        return confToHash(dirname(__FILE__).'/info.txt');
    }

    /**
     * the format we produce
     */
    function getFormat()
    {
        // this should be 'fckg' usally, but we inherit from the xhtml renderer
        // and produce XHTML as well, so we can gain magically compatibility
        // by saying we're the 'xhtml' renderer here.
        return 'xhtml';
    }



    /*
     * The standard xhtml renderer adds anchors we do not need.
     */
    function header($text, $level, $pos) {
        // write the header
        $this->doc .= DOKU_LF.'<h'.$level.'>';
        $this->doc .= $this->_xmlEntities($text);
        $this->doc .= "</h$level>".DOKU_LF;
    }

    /*
     * The FCKEditor prefers <b> over <strong>
     */
    function strong_open()
    {   

        $this->doc .= '<b>';
    }
    function strong_close()
    {
        $this->doc .= '</b>';
    }

    /*
     * The FCKEditor prefers <strike> over <del>
     */
    function deleted_open()
    {
        $this->doc .= '<strike>';
    }
    function deleted_close()
    {
        $this->doc .= '</strike>';
    }
    
    function table_close()
    {
        $this->doc .= "</table>\n<span class='np_break'>&nbsp;</span>\n</div>";
    }
    /* 
     * Dokuwiki displays __underlines__ as follows
     *     <em class="u">underlines</em>
     * in the fck editor this conflicts with 
     * the //italic// style that is displayed as
     *     <em>italic</em>
     * which makes the rathe obvious
     */
    function underline_open()
    {
        $this->doc .= '<u>';
    }
    function underline_close()
    {
        $this->doc .= '</u>';
    }

    function listcontent_open()
    {
    }

    function listcontent_close()
    {
    }
}
