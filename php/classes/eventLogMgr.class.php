<?php

class eventLogMgr {
	const LOGTABLE = 'event_log';
	const LOGDATATABLE = 'event_log_data';
	
	protected static $subClass = 'eventLog';
	protected static $periodFrom = null;
	protected static $periodTo = null;
	
	public static function setSubClass( $className ) {
		if( class_exists($className) ) {
			self::$subClass = $className;
			return true;
		}
		return false;
	}
	
	public function setPeriodFrom($from) {
		self::$periodFrom = $from;
	}
	public function setPeriodTo($to) {
		self::$periodTo = $to;
	}
	
	protected static function &getResult(&$resource) {
		$result = null;
		if( is_resource($resource) ) {
			$eventLogs = array();
			while ($row = tyDB::fetchAssoc($resource)) {
				if( !isset($eventLogs[ $row['id'] ]) ) {
					unset($row['event_id']);
					unset($row['data_key']);
					unset($row['data_value']);
					$eventLogs[ $row['id'] ]['prop'] = $row;
					tyDB::dataSeek($resource, 0);
					continue;
				}
				if( (int)$row['event_id'] > 0 ) {
					$eventLogs[ $row['id'] ]['data'][ $row['data_key'] ] = $row['data_value'];
				}
			}
			
			foreach ($eventLogs as $eventLog) {
				$entry = new self::$subClass($eventLog['prop']);
				$entry->setEventData($eventLog['data']);
				$result[] = $entry;
			}
		}
		
		return $result;
	}
	
	/**
	 * add an event log entry
	 *
	 * @param string $type type of event log
	 * @param string $key unique key to identify event log
	 * @param array $data additional information about this event log
	 * @return object (instance of self::$subClass)
	 */
	public static function &add($type, $key, &$logData = null) {
		$eventLogEntry = null;
		$type = (string)$type;
		$key = (string)$key;
		
		if( isset($type[0]) && isset($key[0]) ) {
			try {
				$data = array(
					'type'	=> $type,
					'key'	=> $key
				);
				$eventLogEntry = new self::$subClass($data);
				$logData = (array)$logData;
				if( count($logData) > 0 ) {
					$eventLogEntry->setEventData($logData);
				}
				
				if( !self::store($eventLogEntry) ) {
					return null;
				}
			} catch (Exception $e) {
				trigger_error($e->getMessage());
			}
		}
		
		return $eventLogEntry;
	}
	
	/**
	 * add an event log entry
	 *
	 * @param string $type type of event log
	 * @param string $key unique key to identify event log
	 * @param array $data additional information about this event log
	 * @return object (instance of self::$subClass)
	 */
	public static function delete($type, $key) {
		$type = (string)$type;
		$key = (string)$key;
		
		if( isset($type[0]) && isset($key[0]) ) {
			try {
				tyDB::exec("DELETE FROM ".self::LOGTABLE." WHERE `type` = '".tyDB::escapeValue($type)."' AND `key` = '".tyDB::escapeValue($key)."'");
				return true;
			} catch (Exception $e) {
				trigger_error($e->getMessage());
			}
		}
		
		return false;
	}
	
	public static function &getByType($type) {
		$result = null;
		$type = (string)$type;
		
		try {
			$statement = "
				SELECT ".self::LOGTABLE.".*, ".self::LOGDATATABLE.".event_id, ".self::LOGDATATABLE.".key AS data_key, ".self::LOGDATATABLE.".value AS data_value
				FROM ".self::LOGTABLE."
				LEFT JOIN ".self::LOGDATATABLE."
					ON ".self::LOGTABLE.".id = ".self::LOGDATATABLE.".event_id
				WHERE
					".self::LOGTABLE.".type = '".tyDB::escapeValue($type)."'
			";
			if( (int)self::$periodFrom > 0 ) {
				$statement .= " AND UNIX_TIMESTAMP(".self::LOGTABLE.".datetime) >= ".self::$periodFrom;
			}
			if( (int)self::$periodTo > 0 ) {
				$statement .= " AND UNIX_TIMESTAMP(".self::LOGTABLE.".datetime) <= ".self::$periodTo;
			}
			$res = tyDB::query($statement);
			$result = self::getResult($res);
		} catch (Exception $e) {
			trigger_error($e->getMessage());
		}
		
		return $result;
	}
	
	public static function &getByTypeAndKey($type, $key) {
		$result = null;
		$key = (string)$key;
		$type = (string)$type;
		
		try {
			$statement = "
				SELECT ".self::LOGTABLE.".*, ".self::LOGDATATABLE.".event_id, ".self::LOGDATATABLE.".key AS data_key, ".self::LOGDATATABLE.".value AS data_value
				FROM ".self::LOGTABLE."
				LEFT JOIN ".self::LOGDATATABLE."
					ON ".self::LOGTABLE.".id = ".self::LOGDATATABLE.".event_id
				WHERE
						".self::LOGTABLE.".type = '".tyDB::escapeValue($type)."'
					AND ".self::LOGTABLE.".key = '".tyDB::escapeValue($key)."'
			";
			if( (int)self::$periodFrom > 0 ) {
				$statement .= " AND UNIX_TIMESTAMP(".self::LOGTABLE.".datetime) >= ".self::$periodFrom;
			}
			if( (int)self::$periodTo > 0 ) {
				$statement .= " AND UNIX_TIMESTAMP(".self::LOGTABLE.".datetime) <= ".self::$periodTo;
			}
			$res = tyDB::query($statement);
			$result = self::getResult($res);
		} catch (Exception $e) {
			trigger_error($e->getMessage());
		}
		
		return $result;
	}
	
	public function store($logEntry) {
		if( $logEntry instanceof self::$subClass ) {
			try {
				tyDB::transaction();
				
				$queryFields = new tyDBQueryfield($logEntry->getData());
				if( (int)$logEntry->id === 0 ) $queryFields->add('datetime', 'NOW()', false);
				
				$id = tyDB::insertUpdate(self::LOGTABLE, $queryFields);
				if( (int)$id > 0 && $logEntry->id != $id ) {
					$logEntry->id = $id;
					
					$logData = (array)$logEntry->getEventData();
					if( count($logData) > 0 ) {
						foreach ($logData as $key => $value) {
							$qf = new tyDBQueryField(array(
								'event_id' => $id,
								'key' => $key,
								'value' => $value
							));
							tyDB::insertUpdate(self::LOGDATATABLE, $qf);
						}
					}
				}
				
				tyDB::commit();
				
				return true;
			} catch (Exception $e) {
				trigger_error($e->getMessage());
				tyDB::revert();
			}
		}
		return false;
	}
}

class eventLog {
	protected $dbData = null;
	protected $data = null;
	
	public function __construct(&$dbData) {
		$dbData = (array)$dbData;
		if( count($dbData) > 0 ) {
			$this->dbData = $dbData;
		}
	}
	
	public function &__get($key) {
		return $this->dbData[ $key ];
	}
	
	public function __set($key, $value) {
		$this->dbData[ $key ] = $value;
	}
	
	public function &getData() {
		return $this->dbData;
	}
	
	public function &getEventData($key = null) {
		if( $key === null ) return $this->data;
		else if( isset($this->data[ $key ]) ) {
			return $this->data[ $key ];
		}
		return null;
	}

	public function setEventData(&$data) {
		$data = (array)$data;
		if( count($data) > 0 ) {
			foreach ($data as $key => $value) {
				if( !is_array($value) && !is_object($value) ) {
					$this->data[ $key ] = $value;
				}
			}
		}
	}
}