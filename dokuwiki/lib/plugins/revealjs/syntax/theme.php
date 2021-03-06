<?php


if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_revealjs_theme extends DokuWiki_Syntax_Plugin {

    public function getType() { return 'substition'; }
    public function getSort() { return 32; }


    /**
     * Connect lookup pattern to lexer.
     *
     * @param $aMode String The desired rendermode.
     * @return none
     * @public
     * @see render()
     */
    function connectTo($mode) {
         $this->Lexer->addSpecialPattern('~~REVEAL[^~]*~~',$mode,'plugin_revealjs_theme');
    }



    /**
     * Handler to prepare matched data for the rendering process.
     *
     * @param $aMatch String The text matched by the patterns.
     * @param $aState Integer The lexer state for the match.
     * @param $aPos Integer The character position of the matched text.
     * @param $aHandler Object Reference to the Doku_Handler object.
     * @return Integer The current lexer state for the match.
     * @public
     * @see render()
     * @static
     */
    public function handle($match, $state, $pos, Doku_Handler $handler) {
        if ($match != '~~REVEAL~~') {
            $options = trim(substr($match,8,-2));
            // ensure that only whitespaces do not result in "theme="
            if ($options != '') {
                // parse multiple options (example: theme=moon&controls=1&build_all_lists=1)
                if (strpos($options, '=') !== false) {
                    parse_str($options, $data);
                }
                // if only one option this must be the theme (backward compatibility)
                else {
                    $data = array('theme' => $options);
                }
                return $data;
            }
        }
        return array();
    }

    /**
     * Handle the actual output creation.
     *
     * @param $aFormat String The output format to generate.
     * @param $aRenderer Object A reference to the renderer object.
     * @param $aData Array The data created by the <tt>handle()</tt>
     * method.
     * @return Boolean <tt>TRUE</tt> if rendered successfully, or
     * <tt>FALSE</tt> otherwise.
     * @public
     * @see handle()
     */
    public function render($mode, Doku_Renderer $renderer, $data) {
        global $ID;

        if($mode == 'xhtml'){
            if (is_a($renderer, 'renderer_plugin_revealjs')){
                // pass
            }
            else {
                // create button to start the presentation
                if (array_key_exists('open_in_new_window', $data)){
                    $target = $data[open_in_new_window] ? '_blank' : '_self';
                }
                else {
                    $target = $this->getConf('open_in_new_window') ? '_blank' : '_self';
                }
                unset($data['open_in_new_window']); // hide open_in_new_window for the url params
                $renderer->doc .= '<a target="'.$target.'" href="'.exportlink($ID,'revealjs',count($data)?$data:null).'" title="'.$this->getLang('view').'">';
                $renderer->doc .= '<img src="'.DOKU_BASE.'lib/plugins/revealjs/start_button.png" align="right" alt="'.$this->getLang('view').'"/>';
                $renderer->doc .= '</a>';
            }
            return true;
        }
        return false;
    }
}
