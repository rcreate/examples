<?php
class cacheMgr {
	const CACHE_TTL_SHORT = 60;
	const CACHE_TTL_MEDIUM = 300;
	const CACHE_TTL_LONG = 3600;
	const CACHE_TTL_DAILY = 86400;
	const CACHE_TTL_UNLTD = 0;
	const CACHE_TTL_NOCACHE = -1;
	
	/**
	 * gibt einen gecacheten Wert zurück wenn er existiert; andernfalls wird null zurückgegeben
	 *
	 * @param string $groupId string, der die Cache-Einträge identifiziert und zu einer Gruppe zusammenfasst (z.B. __METHOD__ -> alle Aufrufe einer Methode)
	 * @param array $params zusätzliche Parameter, die den Wert des Cache-Eintrags bedingen
	 * @return mixed null, wenn kein Eintrag gefunden wurde
	 */
	public static function &getCached($groupId, array $params = array()) {
		if(!defined('WEB_ID')) include_once( $GLOBALS['mytyInstallPath'] . '/modules/crowdfunding/includes/config.inc.php' );
		$key = WEB_ID.':'.$groupId.':'.(count($params)>0 ? md5(join('|',$params)) : '');
		$key = str_replace(':','/',$key);
		$key = str_replace('//','/',$key);
		$data = apc_fetch($key, $exists);
		if( $exists === true ) return $data;
		return null;
	}
	
	/**
	 * speichert einen Wert in den Cache
	 *
	 * @param string $groupId string, der die Cache-Einträge identifiziert und zu einer Gruppe zusammenfasst (z.B. __METHOD__ -> alle Aufrufe einer Methode)
	 * @param array $params zusätzliche Parameter, die den Wert des Cache-Eintrags bedingen
	 * @param mixed $data Daten, die gespeichert werden sollen
	 * @param int $ttl zeit in sekunden, die der Eintrag im cache vorgehalten wird (Benutzung der Konstanten cacheMgr::CACHE_TTL_... empfohlen); standard ist cacheMgr::CACHE_TTL_MEDIUM
	 * @return boolean
	 */
	public static function &setCached($groupId, array $params, &$data, $ttl = null) {
		if(!defined('WEB_ID')) include_once( $GLOBALS['mytyInstallPath'] . '/modules/crowdfunding/includes/config.inc.php' );
		if( $ttl !== self::CACHE_TTL_NOCACHE ) {
			if( $ttl === null ) $ttl = self::CACHE_TTL_MEDIUM;
			$key = WEB_ID.':'.$groupId.':'.(count($params)>0 ? md5(join('|',$params)) : '');
			$key = str_replace(':','/',$key);
			$key = str_replace('//','/',$key);
			return apc_store($key, $data, $ttl);
		}
		return true;
	}
	
	/**
	 * löscht einen oder mehrere (regexp) Einträge aus dem Cache; Sollen mehrere Einträge gelöscht werden werden diese über die groupId als regulären Ausdruck gelöscht
	 *
	 * @param string $groupId string, der die Cache-Einträge identifiziert und zu einer Gruppe zusammenfasst (z.B. __METHOD__ -> alle Aufrufe einer Methode)
	 * @param boolean $exact true, wenn nur der genau passende Eintrag gelöscht werden soll
	 * @return boolean
	 */
	public static function &clearCached($groupId, $exact = false, array $params = array()) {
		if(!defined('WEB_ID')) include_once( $GLOBALS['mytyInstallPath'] . '/modules/crowdfunding/includes/config.inc.php' );
		$key = WEB_ID.':'.$groupId.':';
		if( count($params)>0 ) {
			$key .= md5(join('|',$params));
			$exact = true;
		}
		$key = str_replace(':','/',$key);
		$key = str_replace('//','/',$key);
		if( $exact === true ) $result = apc_delete($key);
		else $result = apc_delete(new APCIterator('user', '/^'.preg_quote($key,'/').'/', APC_ITER_VALUE));
		return $result;
	}
}