<?php
/**
 * Plugin Include Form: include external, approved PHP forms
 * Updated April 19, 2007 by Kite <Kite@puzzlers.org>
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Kite <Kite@puzzlers.org>
 * @based_on   "externallink" plugin by Otto Vainio <plugins@valjakko.net>
 */
 
if(!defined('DOKU_INC')) {
	define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
}
if(!defined('DOKU_PLUGIN')) {
	define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
}
 
require_once(DOKU_PLUGIN.'syntax.php');
 
 
function eval_form_php($arr) {
	global $INFO;
 
	if(0 and ($INFO['perm'] == AUTH_ADMIN)) { // Use debug output??
		ob_start();
		$content = "<!-- Content: "; 
		print_r( $arr ); 
		$content .= ob_get_contents();
		ob_end_clean();
		$content .= " -->\n";
		echo $content;  // can't return this
	}
	ob_start();
	if($INFO['perm'] == AUTH_ADMIN) {
		eval("?>$arr");              //  not $arr -- now allows parsing all PHP blocks 
	} else {
		@eval("?>$arr");             //  not $arr -- now allows parsing all PHP blocks 
	}	
	$content = ob_get_contents();
	ob_end_clean();
	return $content;
}
 
/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_inclform extends DokuWiki_Syntax_Plugin {
 
    /**
     * What kind of syntax are we?
     */
    function getType(){
        return 'substition';
    }
 
    // Just before build in links
    function getSort(){ return 299; }
 
    /**
     * What about paragraphs?
     */
    function getPType(){
        return 'block';
    }
 
    function connectTo($mode) {
       $this->Lexer->addSpecialPattern('~~INCLFORM[^~]*~~',$mode,'plugin_inclform');
    }
 
 
    /**
     * Handle the match
     */
    function handle($match, $state, $pos, Doku_Handler $handler){
	    // Remove the tag itself, and then separate the form name
	    // from the parameter set.
	    $match = preg_replace("%~~INCLFORM(=(.*))?~~%u", "\\2", $match);
	    //echo "\n\t<!-- syntax_plugin_INCLFORM.handle() found >> $match << -->\n";
        return $match;
    }
 
    /**
     * Create output
     */
    function render($mode, Doku_Renderer $renderer, $data) {
        if($mode == 'xhtml'){
	    $FORM_PARAMS = explode(';', $data);
            $text=$this->_inc_form($FORM_PARAMS[0], $FORM_PARAMS);
            $renderer->doc .= $text;
            return true;
        }
        return false;
    }
 
 
	//Translate an ID into a form file name
	//path is relative to the DokuWiki root (I use data/forms)
	function formFN($id,$rev=''){
	  global $conf;
	  if(empty($rev)){
	    $id = cleanID($id);
	    $id = str_replace(':','/',$id);
	    $fn = $this->getConf('formdir').'/'.utf8_encodeFN($id).'.php';
	  }
	  return $fn;
	} // formFN()
 
    function _inc_form($form, $PARAMS) {
	global $FORM_PARAMS;
	global $ID;
        if(strlen($form) < 1) {
	    $form = $ID;   // default to the current page ID
	}
        // Break the form parameters (if any) into name/values
	$path =  $this->formFN($form);  
	foreach($PARAMS as $item) {
	    // split the pair
	    $parts = explode('=', $item);
	    // Skip the $ID parameter
	    if(strlen($parts[0])>0) {
		$FORM_PARAMS[$parts[0]] = $parts[1];  
	    }
	}
        if(file_exists(DOKU_INC . $path)) {
	    //echo "<!-- form was found -->\n";
	    $text = io_readFile($path); 
	    $pattern = "/(<\?php)(.*?)(\?>)/is";			
	    $text = eval_form_php($text);
	} else {
	    $text = "\n\t<!-- No form found for '$form'-->\n";
	}
	return $text;
    } // _inc_form()
} // syntax_plugin_INCLFORM
?>
