<?php
include_once($GLOBALS['mytyInstallPath'].'/modules/crowdfunding/mvc/lib/3rdParty/smarty/Smarty.class.php');

class BasicTemplate extends Smarty {
	protected $tplFile = '';
	protected $snippet = false;
	
	public function __construct() {
		parent::__construct();
		
		// add crowdfunding plugins for smarty
		$this->addPluginsDir($GLOBALS['mytyInstallPath'].'/modules/crowdfunding/mvc/lib/plugins/smarty/');
		
		// add web specific plugins for smarty
		if( is_dir($GLOBALS['documentRoot'].'/templates/lib/plugins/smarty/') ) {
			$this->addPluginsDir($GLOBALS['documentRoot'].'/templates/lib/plugins/smarty/');
		}
	}
	
	public function setTplFile($file) {
		if( is_file($file) ) {
			$this->tplFile = $file;
			// also set template dir to cached template file
			$this->addTemplateDir( dirname($this->tplFile) );
		} else {
			throw new Exception('template is not a file ('.$file.')');
		}
	}
    
    public function renderTags() {
        /**/
    }
    
	protected function getCacheId() {
		return null;
	}

    public function loadOutputFilter($outputFilter) {
        if (is_array($outputFilter)) {
            foreach ($outputFilter as $key => $loadFilter) {
                if ((bool)$loadFilter === true) $this->loadFilter('output', $key);
            }
        }
    }

	/**
	 * load and assign basic data for all templates
	 */
	public function loadData() {
		$this->assign('myty', array(
			'documentRoot' => $GLOBALS['documentRoot'],
			'installPath' => $GLOBALS['mytyInstallPath'],
			'basePath' => $GLOBALS['mytyBasePath']
		));
		
		$this->assign('tyState', array(
			'editMode' => (bool)$GLOBALS['tyState']['editMode'],
			'previewMode' => (bool)$GLOBALS['tyState']['previewMode'],
			'caption' => tyGetCaptionByTopic($GLOBALS['tyState']['topic']),
			'topic' => $GLOBALS['tyState']['topic'],
			'language' => $_SESSION['language'],
			'clientId' => tyGetClientId(),
			'isAdmin' => userMgr::isAdmin(),
			'isPlatformManager' => userMgr::isPlatformManager()
		));
		
		$this->assign('nav', array(
			'home' => array(
				'url' => tyGetUrlByTopic(PROJECT_HOMEPAGE),
				'caption' => tyGetCaptionByTopic(PROJECT_HOMEPAGE)
			)
		));
		
		$this->assign('currency', array(
			'code' => 'EUR',
			'symbol' => '&euro;'
		));
		
		// set topic settings
		tyGetFlatEntryByTopic($navItem, $GLOBALS['tyState']['topic']);
		if (is_array($navItem["element"]["variables"])) {
			if (!empty($navItem["element"]["variables"][ $_SESSION['language'] ])) {
				$temp_var = $navItem["element"]["variables"][ $_SESSION['language'] ];
			} else {
				$temp_var = $navItem["element"]["variables"][ tyConfig::$data['modules']['lang']['defaultLang'] ];
			}
			parse_str( $temp_var, $variables );
		}else{
			parse_str( $flatEntry["element"]["variables"], $variables );
		}
		if( count($variables) > 0 ) {
			$this->assign('topicSettings', $variables);
		}

		// assign current login status and user
		$userSession = userMgr::getCurrent();
		if( $userSession instanceof User ) {
			$this->assign('userSession', $userSession);
			$this->assign('loggedIn', true);
		} else {
			$this->assign('loggedIn', false);
		}
	}
	
	public function display($template = null, $cache_id = null, $compile_id = null, $parent = null) {
		if( $template === null ) {
			$template = basename($this->tplFile);
		}
		$cache_id = $this->getCacheId();
		parent::display($template, $cache_id, $compile_id, $parent);
	}

	public function fetch($template = null, $cache_id = null, $compile_id = null, $parent = null, $display = false, $merge_tpl_vars = true, $no_output_filter = false) {
		if( $template === null ) {
			$template = basename($this->tplFile);
		}
		$cache_id = $this->getCacheId();
		parent::fetch($template, $cache_id, $compile_id, $parent, $display, $merge_tpl_vars, $no_output_filter);
	}
}