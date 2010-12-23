/*
 * FCKeditor - The text editor for Internet - http://www.fckeditor.net
 * Copyright (C) 2003-2007 Frederico Caldeira Knabben
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
 */

// Register the related commands.

FCKCommands.RegisterCommand( 'geshi',
   new FCKDialogCommand( FCKLang['GeshiDlgTitle'], FCKLang['GeshiDlgTitle'],  
   FCKConfig.PluginsPath + 'geshi/geshi.html',  475, 400 ) ) ;


var oGeshiTool		= new FCKToolbarButton( 'geshi',  FCKLang['GeshiToolTip'] ) ;
oGeshiTool.IconPath	= FCKPlugins.Items['geshi'].Path  + 'images/geshi.gif' ;

FCKToolbarItems.RegisterItem( 'geshi', oGeshiTool ) ;	

// The object used for all Geshi operations.
var FCKGeshi = new Object() ;

FCKGeshi.Insert = function(code_type, lang, snip_fname, isSafari) {
    
  
    var pre_class = code_type + ' ' + lang;
	var hrefStartHtml	=  '<pre class="' + pre_class + '">';
	var hrefEndHtml		=  '</pre>';

    var reset = false;
    if(!FCKBrowserInfo.IsIE && !FCKBrowserInfo.IsGecko) isSafari = true;
	mySelection = ( FCKBrowserInfo.IsIE) ? FCKSelection.GetSelectedHTML(isSafari) : removeBR(FCKSelection.GetSelectedHTML(isSafari));

    mySelection = mySelection.replace(/^\s+/,"");
    mySelection = mySelection.replace(/\s+$/,"");
    if(!mySelection) mySelection = "<br /><br />";

    hrefHtml = hrefStartHtml+mySelection+hrefEndHtml;
    if(snip_fname) {
        hrefHtml=downloadable_snippet(hrefHtml, snip_fname) ;
    }

   

    hrefHtml += '<span class="np_break">&nbsp;</span>';
    if(isSafari) {
          hrefHtml = '<span class="np_break">&nbsp;</span>' + hrefHtml;
    }
	FCK.InsertHtml(hrefHtml);
}

FCKGeshi.InsertEdited = function(val) {

//	mySelection = ( FCKBrowserInfo.IsIE) ? FCKSelection.GetSelectedHTML() : removeBR(FCKSelection.GetSelectedHTML());

	hrefHtml = val;


	FCK.InsertHtml(hrefHtml);
}

FCKSelection.GetSelectedHTML = function(isSafari) {	

							// see http://www.quirksmode.org/js/selected.html for other browsers
	if( FCKBrowserInfo.IsIE) {												// IE
		var oRange = FCK.EditorDocument.selection.createRange() ;
		//if an object like a table is deleted, the call to GetType before getting again a range returns Control
		switch ( this.GetType() ) {
			case 'Control' :
			return oRange.item(0).outerHTML;

			case 'None' :
			return '' ;

			default :
			return oRange.htmlText ;
		}
	}
	else if ( FCKBrowserInfo.IsGecko || isSafari ) {									// Mozilla, Safari

									// Mozilla, Safari
		var oSelection = FCK.EditorWindow.getSelection();
		//Gecko doesn't provide a function to get the innerHTML of a selection,
		//so we must clone the selection to a temporary element and check that innerHTML
		var e = FCK.EditorDocument.createElement( 'DIV' );
		for ( var i = 0 ; i < oSelection.rangeCount ; i++ ) {
			e.appendChild( oSelection.getRangeAt(i).cloneContents() );
		}
		return e.innerHTML;
	}
}

function removeBR(input) {							/* Used with Gecko */
	var output = "";
	for (var i = 0; i < input.length; i++) {
		if ((input.charCodeAt(i) == 13) && (input.charCodeAt(i + 1) == 10)) {
			i++;
			output += " ";
		}
		else {
			output += input.charAt(i);
   		}
	}
	return output;
}

function downloadable_snippet(mySelection, fname) {


matches = fname.match(/\.(\w+)$/);
var ext = matches ? matches[1] : 'php';

var media_file = 'mediafile mf_' + ext;

var selection = '<dl class="file">' +
    '<dt><a href="doku.php?do=export_code&id=start&codeblock=0"' 
          + 'title="Download Snippet" class="' + media_file + '">' + fname +'</a></dt>'
    + '<dd>'
    + mySelection
   + ' </dd> </dl>';

 return selection;
}
