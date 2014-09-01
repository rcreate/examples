<?php
namespace cf\model\files;

use BadMethodCallException;
use Exception;
use tyDBNGResultSet;
use tyRuntime;

class UploadedFileResultSet extends tyDBNGResultSet {
    public function each() {
        $row = parent::each();
        if ($row !== false) return new UploadedFile($row);
        else return false;
    }
    public function get($i) {
        $row = parent::get($i);
        if ($row !== false) return new UploadedFile($row);
        else return false;
    }
}

class UploadedFileMgr {
	const TABLE_NAME = 'files';
	
	private $type = '';
	private $db = null;
	private $source = array(
		'table' => '',
		'id' => '',
		'column' => ''
	);
	private $resultSet = null;
	
	public function __construct() {
		$tyRuntime = tyRuntime::getInstance();
		$this->db = $tyRuntime->getDBDriver();
	}
	
	public function setType($type) {
		$this->type = $type;
	}
	
	public function setSource($recordTable, $recordId, $recordColumn) {
		$this->source = array(
			'table' => $recordTable,
			'id' => (int)$recordId,
			'column' => $recordColumn
		);
	}

	/**
	 * fetch files from DB
	 * @return cfUploadedFileResultSet
	 */
	public function getFiles() {
		if( ( $this->resultSet instanceof tyDBNGResultSet ) === false ) {
			if( $this->source['table'] != '' && $this->source['id'] > 0 && $this->source['column'] != '' ) {
				try {
					$sql = "
						SELECT ".self::TABLE_NAME.".*
						FROM ".self::TABLE_NAME."
						WHERE	".self::TABLE_NAME.".tableName = ".$this->db->qv($this->source['table'])."
							AND ".self::TABLE_NAME.".recordId = ".$this->source['id']."
							AND ".self::TABLE_NAME.".columnName = ".$this->db->qv($this->source['column'])."
						ORDER BY ".self::TABLE_NAME.".orderIndex ASC
					";
					$this->resultSet = $this->db->resultSet($sql, 'cf\model\files\UploadedFileResultSet');
				} catch (Exception $e) {
					trigger_error($e->getMessage());
				}
			} else {
				throw new BadMethodCallException;
			}
		}
		return $this->resultSet;
	}
}

class UploadedFile {
	protected $dbData = array();
	protected $fileDB = null;
	
	public function __construct(array &$data) {
		$this->dbData = $data;
	}
	
	public function getFileDB() {
		if( $this->fileDB !== null ) return $this->fileDB;
		
		include_once($GLOBALS['mytyInstallPath'].'/modules/filemanager/classes/tyFileDB.class.php');
		return $this->fileDB = new \tyFileDB( $this->dbData['fileID'] );
	}

	public function getFileId() {
		return (int)$this->dbData['fileID'];
	}
	
	public function getSrc($dim = '50x50', $resizeMode = 'fill') {
		$fileDB = $this->getFileDB();
		if( $fileDB->isAvailable() ) {
			if( $fileDB->isImage() ) {
				$includePath = $GLOBALS['documentRoot'].'/templates/';
				if( in_array(realpath($GLOBALS['mytyInstallPath'].'/modules/crowdfunding/includes/init.inc.php'), get_included_files()) ) {
					$includePath = $GLOBALS['mytyInstallPath'].'/modules/crowdfunding/';
				}
				include_once($includePath.'mvc/model/imageMgr.model.php');
				return \imageMgr::getSrcForImageId($fileDB->getID(), $dim, $resizeMode);
			} else {
				include_once($GLOBALS['mytyInstallPath'].'/modules/crowdfunding/mvc/lib/staticmediamgr.class.php');
				return \staticMediaMgr::getSrc($fileDB->getSrcPath());
			}
		}
		return '';
	}
}