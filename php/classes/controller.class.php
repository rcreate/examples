<?php
abstract class Controller {
	const CACHE_DIR = '/media/cache/tags';
	
	protected $editMode = false;
	protected $cacheMode = true;
	protected $templatePath = '';
	protected $cacheSubdirectory = '';
    protected $outputFilters = array(
        'initjs' => true,
        'facebookConnect' => true,
        'tyAdditionals' => true,
        'hitcounter' => true,
        'toolbar' => false
    );
	
	public $userSession = null;
	public $model = null;
	public $modelValid = false;
	public $topic = null;
	public $topicValid = false;
	public $loggedIn = false;
	public $loginRequired = false;
	
	abstract public function checkModel();
	abstract public function getTopic( $action = '', $subPages = '' );
	abstract protected function getTemplate( $templateFile );
	
	public function __construct($linkCaption) {
		if( $this->model instanceof Model && $this->model->loaded ) {
			$this->modelValid = true;

			// init user
			$this->userSession = userMgr::getCurrent();
			if( $this->userSession instanceof User ) {
				$this->loggedIn = true;
			}

			// init render mode
			$this->initRenderMode();
			
			// enable toolbar filter
			if( $this->loggedIn === true || userMgr::isPlatformManager() ) {
				$this->enableOutputFilter('toolbar');
			}
		} else {
			trigger_error('no model loaded', E_USER_WARNING);
		}
	}
	
	/**
	 * init render mode for normal users or guests
	 */
	protected function initRenderMode() {
		if( userMgr::isPlatformManager() === false && $GLOBALS['gatekeeper'] instanceof RBAC_GateKeeper && $GLOBALS['gatekeeper']->checkAccess('tycon','tycon/backend','show') ) {
			if( EDITMODE_ENABLED ) {
				$this->editMode = true;
				$this->cacheMode = false;
			} else {
				$GLOBALS['hideToolbar'] = 1;
			}
			
			$GLOBALS['tyState']['editMode'] = &$this->editMode;
			$GLOBALS['tyState']['previewMode'] = false;
		} else if( userMgr::isPlatformManager() === true ) {
			$this->cacheMode = false;
			$this->editMode = (bool)$GLOBALS['tyState']['editMode'];
		}
		
		// deactivate caching when form was submitted
		if( count($_POST) || $_COOKIE['noCache'] == true ) {
			$this->cacheMode = false;
		}
	}
	
	/**
	 * check the current URL
	 * - is current host the correct one (default domain)
	 *
	 */
	public function checkDomain() {
		// Domainverwaltung fr jedes Topics ist angeschalten, richtige Domain soll erzwungen werden, es ist ein Topic vorhanden und der aktuelle Nutzer ist kein Backendnutzer -> Prfen ob die Seite ber die richtige Domain aufgerufen wird, ansonsten weiterleiten
		if( tyConfig::$data['modules']['nav']['domains']['forcedcorrectdomain'] == 'true' && $_SERVER['HTTP_HOST'] != tyConfig::$data['modules']['nav']['domains']['defaultdomain'] && !$GLOBALS["gatekeeper"]->checkAccess('tycon','tycon/backend','show') ) {
			$domain = tyGetDomainByTopic(tyConfig::$data['modules']['nav']['defaultTopic'][ tyGetClientId() ], $GLOBALS['tyState']['lang']);
			if ($domain){
				// Permanente Weiterleitung, damit nur die aktuelle Seite bei den Suchmaschinen bernommen wird
				header("HTTP/1.1 301 Moved Permanently"); 
				header("Location: ".$domain.$_SERVER['REQUEST_URI']);
				exit();
			}
		}
	}
	
	public function setTopic($topic) {
		if( tyIsValidTopic($topic) ) {
			$this->topic = $topic;
			// collect all variables for current nav item
			tyGetFlatEntryByTopic($navItem, $this->topic);
			if (is_array($navItem["element"]["variables"])) {
				$variables = '';
				if (!empty($navItem["element"]["variables"][$_SESSION['language']])) $variables = $navItem["element"]["variables"][$_SESSION['language']];
				else $variables = $navItem["element"]["variables"][tyConfig::$data['modules']['lang']['defaultLang']];
				parse_str( $variables, $parsedVariables );
			} else {
				parse_str( $navItem["element"]["variables"], $parsedVariables );
			}
			foreach ($parsedVariables as $key => $value) {
				$GLOBALS[ $key ] = $value;
			}
		}
	}
	
	/**
	 * check topic and the privileges of current user to show or edit this topic
	 * -1 => topic is invalid
	 * 1 => login required
	 * 2 => not accessable for current user
	 * 0 => topic valid
	 * 
	 * @return integer
	 */
	public function &checkTopic() {
		$result = -1;
		if( $this->topic != '' && tyIsValidTopic($this->topic) ) {
			// redirect to the correct domain and protocol
			if( tyConfig::$data['modules']['nav']['domains']['forcedcorrectdomain'] == 'true' && !$GLOBALS["gatekeeper"]->checkAccess('tycon','tycon/backend','show') ) {
				$domain = tyGetDomainByTopic($this->topic, $_SESSION['language']);
				if( $domain ){
					// Permanente Weiterleitung, damit nur die aktuelle Seite bei den Suchmaschinen uebernommen wird
					header("HTTP/1.1 301 Moved Permanently"); 
					header("Location: ".$domain.$_SERVER['REQUEST_URI']);
					exit();
				}
			}
			
			// check if ssl should be enabled
			$desiredProtocol = tyGetProtocollByTopic( $this->topic );
			if( getCurrentProtocoll() != $desiredProtocol ) {
				// Permanente Weiterleitung, damit nur die aktuelle Seite bei den Suchmaschinen uebernommen wird
				header("HTTP/1.1 301 Moved Permanently"); 
				header("Location: ".$desiredProtocol.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
				exit();
			}
			
			// topic is accessable
			$result = 0;		
			
			// except following conditions
			if( tyCheckAccessTopic($this->topic, 'backend', 'show') === false ) {
				// check if topic can be seen or if current user is owner (or team-member) of current model
				$subjectLoader = new RBAC_Subject_LOADER(false, false, false);
				$guestUser = $subjectLoader->fetchSubjectByMailAndPassword('gast', 'gast');
				if( tyCheckAccessTopic($this->topic, 'frontend', 'show', $guestUser) === false && $this->model->isMember() === false ) {
					if( $this->loggedIn === true ) {
						// current user is not able to see topic
						$result = 2;
					} else {
						// guest is not able to see topic
						$result = 1;
					}
				}
			}
			
			// check topic settings (e.g. for cache)
			$topicSetting = tyNavSettings::getSettingForTopicRecurse( $this->topic );
			if( !isset($topicSetting['cache']) || ( $topicSetting['cache'] != 'static' && $topicSetting['cache'] != 1 ) ) {
				$this->cacheMode = false;
			}
		}
		return $result;
	}
	
	/**
	 * load template file for current topic or use passed path
	 * @param string $path absolute path to template if it should be overwritten
	 * @throws Exception if file was not found
	 * @return boolean load was successfull or not
	 */
	protected function loadTemplatePath($path) {
		// set template path to load contents from
		if( !empty($path) ) {
			$this->templatePath = $path;
		} else {
			// load template file for current topic
			$this->templatePath = $GLOBALS['documentRoot'].'/'.tyGetTemplateByTopic($this->topic);
			$this->templatePath = str_replace('.php', '.tpl', $this->templatePath);
		}
		if( !is_file($this->templatePath) ) {
			throw new Exception('template path not found');
			return false;
		}
		return true;
	}

	public function &render($templatePath = null) {
		// is current topic editable
		$topicEditable = tyCheckAccessTopic($this->topic, 'backend', 'edit');
		// load template path from controller class
		$success = $this->loadTemplatePath($templatePath);
		if( !$success ) return null;
		
		// directory to store in (/media/cache/tags/[modelType]/)
		$cacheFile = $GLOBALS['documentRoot'].self::CACHE_DIR.'/'.( $this->cacheSubdirectory != '' ? $this->cacheSubdirectory.'/' : '' );
		if( !is_dir($cacheFile) ) mkdir($cacheFile, 0777, true);
		// filename to write cache to (txxx-de.tpl)
		$cacheFile .= str_replace(NAVIGATION_TOPIC_ID_PREFIX.'_', 't', $this->topic).'-'.$_SESSION['language'].( $topicEditable ? '-edit' : '' ).'.tpl';

		// clear cache if file is older than one hour
		$cache = false;
		if( $this->cacheModeEnabled() ) {
			$cache = true;
			if( $topicEditable === false && is_file($cacheFile) ) {
				$cacheFileModificationTime = filemtime($cacheFile);
				if( $cacheFileModificationTime < ( time()-360 ) ) $cache = false;
				$cacheFileSize = filesize($cacheFile);
				if( $cacheFileSize == 0 ) $cache = false;
			} else if( $topicEditable === true ) {
				$cache = false;
			}
		}

		// clear cache due to several conditions
		if( ( $_SESSION['clear_cache'] === true || $cache === false ) && is_file($cacheFile) ) {
			unlink($cacheFile);
		}
		
		// create new cached file
		if( !is_file($cacheFile) ) {
			// render myty tags and cache them
			$source = $this->prerender(file_get_contents($this->templatePath));
			file_put_contents($cacheFile, $source);
		}
		if( !is_file($cacheFile) ) {
			throw new Exception('could not load cached template');
		}

		// create smarty template from parsed myty code
		$template = $this->getTemplate($cacheFile);
		if( ( $template instanceof Smarty ) === false ) {
			throw new Exception('could not load smarty template');
		}
		return $template;
	}
	
	protected function &prerender(&$source) {
		$source = tyconAppend($source);
		return $source;
	}

	public function cacheModeEnabled() {
		return $this->cacheMode;
	}
    
    public function disableCacheMode() {
		$this->cacheMode = false;
	}
    
    protected function setOutputFilter($key, $value) {
        $this->outputFilters[$key] = $value;
    }
    
    public function enableOutputFilter($key) {
        $this->setOutputFilter($key, true);
    }
    
    public function enableOutputFilters() {
        foreach ($this->outputFilters as $key => $loadFilter) {
            $this->enableOutputFilter($key);
        }
    }
    
    public function disableOutputFilter($key) {
        $this->setOutputFilter($key, false);
    }
    
    public function disableOutputFilters() {
        foreach ($this->outputFilters as $key => $loadFilter) {
            $this->disableOutputFilter($key);
        }
    }
}