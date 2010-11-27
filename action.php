<?php
/**
 * DokuWiki Action Plugin LoadSkin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Michael Klier <chi@chimeric.de>
 * @author     Anika Henke <anika@selfthinker.org>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC.'lib/plugins/');
if(!defined('DOKU_LF')) define('DOKU_LF', "\n");

require_once(DOKU_PLUGIN.'action.php');

/**
 * All DokuWiki plugins to interfere with the event system
 * need to inherit from this class
 */
class action_plugin_loadskin extends DokuWiki_Action_Plugin {

    function getInfo() {
        return array(
                'author' => 'Michael Klier',
                'email'  => 'chi@chimeric.de',
                'date'   => @file_get_contents(DOKU_PLUGIN.'loadskin/VERSION'),
                'name'   => 'loadskin',
                'desc'   => 'Allows to change the template',
                'url'    => 'http://dokuwiki.org/plugin:loadskin'
            );
    }

    // register hook
    function register(&$controller) {
        $controller->register_hook('TPL_CONTENT_DISPLAY', 'BEFORE', $this, '_handleContent', array());
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'handleMeta');
        $controller->register_hook('DOKUWIKI_STARTED', 'BEFORE', $this, 'handleConf');
    }

    /**
     * Overwrites the $conf['template'] setting
     *
     * @author Michael Klier <chi@chimeric.de>
     * @author Anika Henke <anika@selfthinker.org>
     */
    function handleConf(&$event, $param) {
        global $conf;

        $tpl = $this->getTpl();

        if($tpl && $_REQUEST['do'] != 'admin') {
            $conf['template'] = $tpl;
        }
    }

    /**
     * Replaces the style headers with a different skin if specified in the
     * configuration
     *
     * @author Michael Klier <chi@chimeric.de>
     * @author Anika Henke <anika@selfthinker.org>
     */
    function handleMeta(&$event, $param) {
        $tpl = $this->getTpl();

        if($tpl && $_REQUEST['do'] != 'admin') {
            $head =& $event->data;
            for($i=0; $i<=count($head['link']); $i++) {
                if($head['link'][$i]['rel'] == 'stylesheet') {
                    $head['link'][$i]['href'] = preg_replace('/t=([\w]+$)/', "t=$tpl", $head['link'][$i]['href']);
                }
            }
        }
    }

    /**
     * Output the template switcher if 'automaticOutput' is on
     *
     * @author Anika Henke <anika@selfthinker.org>
     */
    function _handleContent(&$event, $param){
        if ($this->getConf('automaticOutput')) {
            $helper = $this->loadHelper('loadskin', true);
            $event->data = $helper->showTemplateSwitcher().$event->data;
        }
    }

    /**
     * Checks if a given page should use a different template then the default
     *
     * @author Michael Klier <chi@chimeric.de>
     * @author Anika Henke <anika@selfthinker.org>
     */
    function getTpl() {
        // get template from session
        if ($_REQUEST['tpl'])
            $_SESSION[DOKU_COOKIE]['loadskinTpl'] = $_REQUEST['tpl'];
        if ($_SESSION[DOKU_COOKIE]['loadskinTpl'])
            return $_SESSION[DOKU_COOKIE]['loadskinTpl'];

        // get template from namespace/page and config
        global $ID;
        $config = DOKU_INC.'conf/loadskin.conf';

        if(@file_exists($config)) {
            $data = unserialize(io_readFile($config, false));
            $id   = $ID;

            if($data[$id]) return $data[$id];

            $path  = explode(':', $id);
            $found = false;

            while(count($path) > 0) {
                $id = implode(':', $path);
                if($data[$id]) return $data[$id];
                array_pop($path);
            }
        }
        return false;
    }
}

// vim:ts=4:sw=4:enc=utf-8:
