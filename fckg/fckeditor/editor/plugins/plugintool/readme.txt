Abbr-plugin  for FCKeditor
----------------------------------------------------------------------
By kwillems (kwillems-at-zonnet.nl)

[Based on the Acronym-plugin from SNOOPY-0815 and the InfoPopUp-Plugin from Thomas Goerldt]

What's this?
---------------
Insert, change or delete Abbr-Title-TAG .
The pop-up info text will be displayed while the mouse moves over the text range (by the way: abbr is not recognized by IE < 7).

If you select text before calling this plugin, the abbr-tag will be enclose the selected text.
Optional you can add a title attribute.
If you don't select text, you'll get a warning to select some text first.

To change the abbr-title, put the curser anywhere in the text-range and start the plugin.
To delete the abbr-tag put the curser anywhere in the text-range and start the plugin and hit the checkbox to remove it.


INSTALLATION:
------------------------------
- unzip the files into your plugin folder
- add the following to your fckconfig.js
   FCKConfig.Plugins.Add( 'Abbr','en') ;       //or 'nl'  for Dutch
- add 'Abbr' to your fckconfig.js
    FCKConfig.ToolbarSets["Default"] = [['...','...','Abbr','...']];

- Optional: add the following to your cascading-style-sheet files
  (...FCKeditor\editor\css\fck_editorarea.css and your privat css-file included in every .html file)
      abbr {
      border-bottom: 1px dotted rgb(102, 102, 102);
      cursor: help;
      }
- ready...  