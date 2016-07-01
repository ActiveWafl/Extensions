<?php
namespace Wafl\Extensions\Storage\SqlServer;


final class SqlServer implements \DblEj\Data\Integration\IDatabaseServerExtension
{
	private $_dbServer;
	private $_dbUser;
	private $_dbPassword;
	private $_dbCatalog;
	private $_db;
	private $_isConnected		 = false;
	private $_encoding			 = \SQLSRV_ENC_CHAR;
	private static $_tables		 = array();
	private $_lastExecuteResult	 = null;

	public function __construct($dbServer = null, $dbCatalog = null, $dbUser = null, $dbPassword = null)
	{
		$this->_dbServer	 = $dbServer;
		$this->_dbUser		 = $dbUser;
		$this->_dbPassword	 = $dbPassword;
		$this->_dbCatalog	 = $dbCatalog;
		$this->Connect();
	}

	private $_createScript;
	private $_updateScript;
	private $_requiredLocation;

	function Set_CreateScript($createScript)
	{
		$this->_createScript = $createScript;
	}

	function Set_UpdateScript($updateScript)
	{
		$this->_updateScript = $updateScript;
	}

	function Set_RequiredStorageLocation($requiredLocation)
	{
		$this->_requiredLocation = $requiredLocation;
	}

	function Get_CreateScript()
	{
		return $this->_createScript;
	}

	function Get_UpdateScript()
	{
		return $this->_updateScript;
	}

	function Get_RequiredStorageLocation()
	{
		return $this->_requiredLocation;
	}

	public function Connect()
	{
		if ($this->_dbUser)
		{
			$connectionInfo = array(
				"CharacterSet"	 => $this->_encoding,
				"UID"			 => $this->_dbUser,
				"PWD"			 => $this->_dbPassword,
				"Database"		 => $this->_dbCatalog);
		}
		else
		{
			$connectionInfo = array(
				"CharacterSet"	 => $this->_encoding,
				"Database"		 => $this->_dbCatalog);
		}
		$this->_db = \sqlsrv_connect($this->_dbServer, $connectionInfo);
		if ($this->_db)
		{
			$this->_isConnected = true;
			if (!isset(SqlServer::$_tables[$this->_dbCatalog]))
			{
				SqlServer::$_tables[$this->_dbCatalog] = $this->GetStorageLocations();
			}
		}
		else
		{
			throw new \Exception($this->GetLastError());
			$this->_isConnected = false;
		}
	}

	public function UpdateStorageLocations()
	{
		self::$_tables[$this->_dbCatalog] = $this->GetStorageLocations();
	}

	function SetConnectionCharacterEncoding($charSet)
	{
		$this->_encoding = $charSet;
	}

	public function DirectExecute($sql)
	{
		return $this->Execute($sql);
	}

	public function DirectQuery($sql)
	{
		return $this->GetRowsAsArray($sql);
	}

	public function DirectScriptExecute($filenameOrContents, $isContents = false)
	{
		if ($isContents)
		{
			$sql = $filenameOrContents;
		}
		else
		{
			$sql = file_get_contents($filenameOrContents);
		}
		$sqlArray = explode(";", $sql);
		foreach ($sqlArray as $sql)
		{
			$sql = trim($sql);
			if ($sql)
			{
				$this->Execute($sql);
			}
		}
	}

	public function LimitFilterResultLength($filter, $length)
	{
		throw new \Exception("Sql server driver doesnt support LimitFilterResultLength");
	}

	public function IsConnected()
	{
		return $this->_isConnected;
	}

	public function GetLastError()
	{
		$errors = \sqlsrv_errors();
		if (count($errors) > 0)
		{
			return var_export(reset($errors), true);
		}
		else
		{
			return null;
		}
	}

	public function BeginTransaction()
	{
		if ($this->IsConnected())
		{
			if (!sqlsrv_begin_transaction($this->_db))
			{
				throw new Exception("There was an error beginning the database transaction<p>: " . $this->GetLastError() . "</p>");
			}
		}
		else
		{
			throw new Exception("There was an error beginning the database transaction: Database not connected");
		}
		return true;
	}

	public function CommitTransaction()
	{
		if ($this->IsConnected())
		{
			if (!sqlsrv_commit($this->_db))
			{
				throw new Exception("There was an error committing the database transaction<p>: " . $this->GetLastError() . "</p>");
			}
		}
		else
		{
			throw new Exception("There was an error committing the database transaction: Database not connected");
		}
		return true;
	}

	public function RollbackTransaction()
	{
		if ($this->IsConnected())
		{
			if (!sqlsrv_rollback($this->_db))
			{
				throw new Exception("There was an error rolling back the database transaction<p>: " . $this->GetLastError() . "</p>");
			}
		}
		else
		{
			throw new Exception("There was an error rolling back the database transaction: Database not connected");
		}
		return true;
	}

	public function GetLock($lockname, $timeout)
	{
		throw new \Exception("Sql Server driver doesnt support locks");
	}

	public function IsLocked($lockname)
	{
		return false;
	}

	public function ReleaseLock($lockname)
	{
		throw new \Exception("Sql Server driver doesnt support locks");
	}

	/**
	 * Get a table
	 *
	 * @param string $sql
	 * @param string $dbCatalog
	 * @return array $resultSet
	 */
	public function GetRowsAsArray($sql, $dbCatalog = "")
	{
		/**
		 * @var $rows mysqli_result
		 */
		$rowsPointer = $this->GetRows($sql, $dbCatalog);
		$rows		 = array();
		while ($row		 = \sqlsrv_fetch_array($rowsPointer, \SQLSRV_FETCH_ASSOC))
		{
			array_push($rows, $row);
		}
		return $rows;
	}

	/**
	 * Get a table
	 *
	 * @param string $sql
	 * @param string $dbCatalog
	 * @return $resultSet
	 */
	public function GetRows($sql, $dbCatalog = "")
	{
		$stmnt = null;
		if ($this->IsConnected())
		{
			if ($dbCatalog)
			{
				throw new \Exception("Sql server driver does not support changing the catalog after connection");
			}
			if (($sql))
			{
				$params	 = array();
				$options = array(
					"Scrollable" => SQLSRV_CURSOR_KEYSET);
				$stmnt	 = sqlsrv_query($this->_db, $sql, $params, $options);
				if ($stmnt === false)
				{
					throw new \Exception("There was an error executing the database query<p>$sql</p><br /><p>: " . $this->GetLastError() . "</p>");
				}
			}
			else
			{
				throw new \Exception("There was an error executing the database query<p>$sql</p><br /><p>: " . $this->GetLastError() . "</p>");
			}
		}
		else
		{
			throw new \Exception("There was an error executing the database query: Database not connected");
		}
		return $stmnt;
	}

	public function SetCatalog($dbCatalog)
	{
		if ($this->IsConnected())
		{
			throw new \Exception("Sql server driver does not support changing the catalog after connection");
		}
		else
		{
			throw new Exception("There was an error executing the database query: Database not connected");
		}
	}

	/**
	 * Get the first row returned from a query
	 *
	 * @param string $sql
	 * @param string $dbCatalog
	 * @return array
	 */
	public function GetFirstRow($sql, $dbCatalog = "", $arrayType = \SQLSRV_FETCH_BOTH)
	{
		/**
		 * @var array $returnRow;
		 */
		$returnRow = null;

		$rows = $this->GetRows($sql, $dbCatalog);
		if (sqlsrv_num_rows($rows) > 0)
		{
			$returnRow = sqlsrv_fetch_array($rows, $arrayType);
		}
		return $returnRow;
	}

	public function GetScalar($sql, $dbCatalog = "")
	{
		$row = $this->GetFirstRow($sql, $dbCatalog);
		if ($row)
		{
			$firstColVal = sqlsrv_get_field($row, 0);
			return $firstColVal;
		}
		else
		{
			return null;
		}
	}

	public function GetScalarSp($procName, $argList = "", $dbCatalog = "")
	{
		$row = $this->GetFirstRowSp($procName, $argList, $dbCatalog);
		if ($row)
		{
			$firstColVal = sqlsrv_get_field($row, 0);
			return $firstColVal;
		}
		else
		{
			return null;
		}
	}

	public function GetRowsSP($procName, $argList = "", $dbCatalog = "")
	{
		if ($this->IsConnected())
		{
			if ($dbCatalog)
			{
				throw new \Exception("Sql server driver does not support changing the catalog after connection");
			}

			$this->_lastExecuteResult = \sqlsrv_query($this->_db, "exec $procName $argList");
		}
		else
		{
			$this->_lastExecuteResult = null;
		}
		return $this->_lastExecuteResult;
	}

	public function GetFirstRowSp($procName, $argList = "", $dbCatalog = "")
	{
		/**
		 * @var array $returnRow;
		 */
		$returnRow = null;

		$rows = $this->GetRowsSP($procName, $argList, $dbCatalog);
		if ($rows)
		{
			if ($rows->field_count > 0)
			{
				$returnRow = \sqlsrv_fetch_array($rows, \SQLSRV_FETCH_ASSOC);
			}
		}
		return $returnRow;
	}

	public function GetRowsSpAsArray($procName, $argList = "", $dbCatalog = "")
	{
		/**
		 * @var $dbResult mysqli_result
		 */
		$rows = array();
		if ($this->IsConnected())
		{
			if ($dbCatalog)
			{
				throw new \Exception("Sql server driver does not support changing the catalog after connection");
			}
			$this->_lastExecuteResult = \sqlsrv_query($this->_db, "exec $procName $argList");
			if ($this->_lastExecuteResult === false)
			{
				throw new \Exception("There was an error calling a stored procedure: $procName($argList)");
			}
			while ($row = \sqlsrv_fetch_array($this->_lastExecuteResult, \SQLSRV_FETCH_ASSOC))
			{
				$rows[] = $row;
			}
		}
		return $rows;
	}

	/**
	 * Execute the Sql non-query and return true or false for success
	 *
	 * @param string $sql
	 * @param string $dbCatalog
	 * @return boolean
	 */
	public function Execute($sql, $dbCatalog = "")
	{
		if ($this->IsConnected())
		{
			if ($dbCatalog)
			{
				throw new \Exception("Sql server driver does not support changing the catalog after connection");
			}
			$this->_lastExecuteResult = \sqlsrv_query($this->_db, $sql);
			if ($this->_lastExecuteResult === false)
			{
				throw new \DblEj\Data\DataException("Error executing sql query, " . $this->GetLastError() . ", Query: ($sql)", E_WARNING);
			}
			else
			{
				return true;
			}
		}
		else
		{
			throw new \DblEj\Data\StorageEngineNotReadyException("Cannot execute sql query because I am not connected to a database server",
														E_WARNING);
		}
	}

	public function ExecuteSP($procName, $args = "", $dbCatalog = "")
	{
		if ($this->IsConnected())
		{
			if ($dbCatalog)
			{
				throw new \Exception("Sql server driver does not support changing the catalog after connection");
			}
			if (!$this->_db->real_query("exec $procName($args)"))
			{
				throw new Exception($this->GetLastError() . ", Error executing stored procedure: $procName($args)");
			}
		}
		else
		{
			return false;
		}
	}

	public function GetLastInsertId()
	{
		if ($this->IsConnected() && $this->_lastExecuteResult)
		{
			\sqlsrv_next_result($this->_lastExecuteResult);
			\sqlsrv_fetch($this->_lastExecuteResult);
			$lastInsertId = \sqlsrv_get_field($this->_lastExecuteResult, 0);
			return $lastInsertId;
		}
		else
		{
			return null;
		}
	}

	public function GetLastAffectedCount()
	{
		if ($this->IsConnected())
		{
			return \sqlsrv_rows_affected($this->_lastExecuteResult);
		}
		else
		{
			return 0;
		}
	}

	public function TruncateTable($table)
	{
		$sql = "delete from $table";
		return $this->Execute($sql);
	}

	public function EscapeString($string)
	{
		return $string;
	}

	public function InsertRow($table, $colNames, $colVals)
	{
		$sql		 = "insert into $table (";
		$beenHere	 = 0;
		foreach ($colNames as $colName)
		{
			if ($beenHere)
			{
				$sql .= ",";
			}
			else
			{
				$beenHere = 1;
			}
			$sql .= "$colName";
		}
		$sql .= ") values (";
		$beenHere = 0;
		foreach ($colVals as $colVal)
		{
			if ($beenHere)
			{
				$sql .= ",";
			}
			else
			{
				$beenHere = 1;
			}
			$sql .= "$colVal";
		}
		$sql .= ")";

		if ($this->Execute($sql))
		{
			return true;
		}
		else
		{
			throw new \Exception("Sql server insert failed with the following error: " . mysql_error());
			return false;
		}
	}

	public function UpsertRow($table, $columnNames, $columnValues, $keyColumnName, $isAutoIncrementingKey)
	{

		$sql		 = "IF NOT EXISTS (SELECT * FROM $table WHERE $keyColumnName = " . ($columnValues[$keyColumnName] ? "'" . $columnValues[$keyColumnName] . "'" : "null") . ") ";
		$sql .= "BEGIN ";
		$sql .= "insert into $table (";
		$beenHere	 = 0;
		foreach ($columnNames as $colName)
		{
			if (!$isAutoIncrementingKey || $colName != $keyColumnName)
			{
				if ($beenHere)
				{
					$sql .= ",";
				}
				else
				{
					$beenHere = 1;
				}
				$sql .= "$colName";
			}
		}
		$sql .= ") values (";
		$beenHere = 0;
		foreach ($columnValues as $colName => $colVal)
		{
			if (!$isAutoIncrementingKey || $colName != $keyColumnName)
			{
				if ($beenHere)
				{
					$sql .= ",";
				}
				else
				{
					$beenHere = 1;
				}
				if ($colVal === null)
				{
					$sql .= "null";
				}
				elseif ($colVal === true)
				{
					$sql .= "1";
				}
				elseif ($colVal === false)
				{
					$sql .= "0";
				}
				else
				{
					$sql .= "'$colVal'";
				}
			}
		}
		$sql .= ");SELECT SCOPE_IDENTITY();END";

		$sql .= " ELSE BEGIN update $table set ";
		$beenHere = 0;
		foreach ($columnNames as $colName)
		{
			if ($colName != $keyColumnName)
			{
				if ($beenHere)
				{
					$sql .= ",";
				}
				else
				{
					$beenHere = 1;
				}
				$colVal = $columnValues[$colName];
				if ($colVal === null)
				{
					$sql .= "$colName=null";
				}
				elseif ($colVal === true)
				{
					$sql .= "$colName=1";
				}
				elseif ($colVal === false)
				{
					$sql .= "$colName=0";
				}
				else
				{
					$sql .= "$colName='$colVal'";
				}
			}
		}
		$sql .= " where $keyColumnName = " . ($columnValues["$keyColumnName"] ? "'" . $columnValues["$keyColumnName"] . "'" : "null");
		$sql .= ";SELECT " . ($columnValues["$keyColumnName"] ? "'" . $columnValues["$keyColumnName"] . "'" : "null") . ";END";
		try
		{
			if ($this->Execute($sql))
			{
				if ($isAutoIncrementingKey)
				{
					return $this->GetLastInsertId();
				}
				else
				{
					return true;
				}
			}
			else
			{
				return false;
			}
		}
		catch (\Exception $e)
		{
			throw new \DblEj\Data\DataException("Sql server upsert failed with the following error: " . $e->getMessage(), E_WARNING, $e);
			return false;
		}
	}

	public function DoesTableExist($tableName)
	{
		$rows = $this->GetRowsAsArray("SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_CATALOG = '$this->_dbCatalog' AND  TABLE_NAME = '$tableName'");
		return (count($rows) > 0);
	}

	public function StoreData($storageLocation, array $fieldNames, array $fieldValues, $keyFieldName,
	$keyValueIsAutoGenerated = false)
	{
		return $this->UpsertRow($storageLocation, $fieldNames, $fieldValues, $keyFieldName, $keyValueIsAutoGenerated);
	}

	public function GetLastCreatedDataKey()
	{
		return $this->GetLastInsertId();
	}

	/**
	 * Get one row that matches the criteria set in the passed arguments
	 * @param string $storageLocation
	 * @param string $keyFieldName
	 * @param mixed $keyValue
	 * @return array
	 */
	public function GetData($storageLocation, $keyFieldName, $keyValue)
	{
		$sql = "select * from $storageLocation where $keyFieldName = '" . $keyValue . "'";
		return $this->GetFirstRow($sql, null, \SQLSRV_FETCH_ASSOC);
	}

	public function GetScalarData($storageLocation, $returnFieldName, $keyFieldName, $keyValue)
	{
		if ($this->TableExists($storageLocation))
		{
			if (is_array($keyFieldName))
			{
				$sql		 = "select $returnFieldName from $storageLocation where ";
				$keyFieldIdx = 0;
				foreach ($keyFieldName as $keyFieldNameItem)
				{
					$sql .= "$keyFieldNameItem = '" . $keyValue[$keyFieldIdx] . "' ";
					if ($keyFieldIdx < count($keyFieldNameItem))
					{
						$sql .= " and ";
					}
					$keyFieldIdx++;
				}
			}
			else
			{
				$sql = "select $returnFieldName from $storageLocation where $keyFieldName = '" . $keyValue . "'";
			}
			$returnVal = $this->GetScalar($sql);
		}
		else
		{
			if (is_array($keyValue))
			{
				$argList	 = "";
				$keyFieldIdx = 0;
				foreach ($keyValue as $keyValueItem)
				{
					$argList .= "'$keyValueItem'";
					if ($keyFieldIdx < count($keyValue))
					{
						$argList .= ",";
					}
					$keyFieldIdx++;
				}
			}
			else
			{
				$argList = "'$keyValue'";
			}
			$returnVal = $this->GetScalarSp($storageLocation, $argList);
		}

		return $returnVal;
		;
	}

	public function GetDataGroupCount($storageLocation, $filter = null, $groupingField = null, $filterJoinLocations = null)
	{
		if ($filterJoinLocations && is_array($filterJoinLocations))
		{
			if ($groupingField)
			{
				if (strstr($groupingField, "."))
				{
					$sql = "select count($groupingField) as Count from [$storageLocation]";
				}
				else
				{
					$sql = "select count([$storageLocation].$groupingField) as Count from [$storageLocation]";
				}
			}
			else
			{
				$sql = "select count(*) from [$storageLocation]";
			}
			foreach ($filterJoinLocations as $filterJoinLocationName => $filterJoinLocationMatchColumn)
			{
				$sql .= ", [$filterJoinLocationName]";
			}
			if ($filter)
			{
				$sql .= " where $filter";
			}
			else
			{
				$sql .= " where 1=1 ";
			}
			foreach ($filterJoinLocations as $filterJoinLocationName => $filterJoinLocationMatchColumn)
			{
				if ($filterJoinLocationName && $filterJoinLocationMatchColumn)
				{
					if (is_array($filterJoinLocationMatchColumn))
					{
						$sql .= " and [$filterJoinLocationName].[" . $filterJoinLocationMatchColumn[0] . "] =
                                      [$storageLocation].[" . $filterJoinLocationMatchColumn[1] . "]";
					}
					else
					{
						$sql .= " and [$filterJoinLocationName].[$filterJoinLocationMatchColumn] =
                                      [$storageLocation].[$filterJoinLocationMatchColumn]";
					}
				}
			}
			if ($groupingField)
			{
				if (strstr($groupingField, "."))
				{
					$sql .= " group by $groupingField";
				}
				else
				{
					$sql .= " group by [$storageLocation].$groupingField";
				}
			}
		}
		else
		{
			$sql = "select count(*) as Count from [$storageLocation]";
			if ($filter)
			{
				$sql .= " where $filter";
			}
		}
		if ($groupingField)
		{
			$sql = "select count(*) from ($sql)origTbl where Count > 0";
		}
		return $this->GetScalar($sql);
	}

	public function GetDataGroupSum($storageLocation, $columnName, $filter = null, $groupingField = null, $filterJoinLocations = null)
	{
		if ($filterJoinLocations && is_array($filterJoinLocations))
		{
			$sql = "select sum($columnName) from [$storageLocation]";
			foreach ($filterJoinLocations as $filterJoinLocationName => $filterJoinLocationMatchColumn)
			{
				$sql .= ", [$filterJoinLocationName]";
			}
			if ($filter)
			{
				$sql .= " where $filter";
			}
			else
			{
				$sql .= " where 1=1 ";
			}
			foreach ($filterJoinLocations as $filterJoinLocationName => $filterJoinLocationMatchColumn)
			{
				if ($filterJoinLocationName && $filterJoinLocationMatchColumn)
				{
					if (is_array($filterJoinLocationMatchColumn))
					{
						$sql .= " and [$filterJoinLocationName].[" . $filterJoinLocationMatchColumn[0] . "] =
                                      [$storageLocation].[" . $filterJoinLocationMatchColumn[1] . "]";
					}
					else
					{
						$sql .= " and [$filterJoinLocationName].[$filterJoinLocationMatchColumn] =
                                      [$storageLocation].[$filterJoinLocationMatchColumn]";
					}
				}
			}
		}
		else
		{
			$sql = "select sum($columnName) from [$storageLocation]";
			if ($filter)
			{
				$sql .= " where $filter";
			}
		}
		if ($groupingField)
		{
			if (strstr($groupingField, "."))
			{
				$sql .= " group by $groupingField";
			}
			else
			{
				$sql .= " group by [$storageLocation].$groupingField";
			}
		}
		return $this->GetScalar($sql);
	}

	public function GetDataGroupAvg($storageLocation, $columnName, $filter = null, $groupingField = null, $filterJoinLocations = null)
	{
		if ($filterJoinLocations && is_array($filterJoinLocations))
		{
			$sql = "select avg($columnName) from [$storageLocation]";
			foreach ($filterJoinLocations as $filterJoinLocationName => $filterJoinLocationMatchColumn)
			{
				$sql .= ", [$filterJoinLocationName]";
			}
			if ($filter)
			{
				$sql .= " where $filter";
			}
			else
			{
				$sql .= " where 1=1 ";
			}
			foreach ($filterJoinLocations as $filterJoinLocationName => $filterJoinLocationMatchColumn)
			{
				if ($filterJoinLocationName && $filterJoinLocationMatchColumn)
				{
					if (is_array($filterJoinLocationMatchColumn))
					{
						$sql .= " and [$filterJoinLocationName].[" . $filterJoinLocationMatchColumn[0] . "] =
                                      [$storageLocation].[" . $filterJoinLocationMatchColumn[1] . "]";
					}
					else
					{
						$sql .= " and [$filterJoinLocationName].[$filterJoinLocationMatchColumn] =
                                      [$storageLocation].[$filterJoinLocationMatchColumn]";
					}
				}
			}
		}
		else
		{
			$sql = "select avg($columnName) from [$storageLocation]";
			if ($filter)
			{
				$sql .= " where $filter";
			}
		}
		if ($groupingField)
		{
			if (strstr($groupingField, "."))
			{
				$sql .= " group by $groupingField";
			}
			else
			{
				$sql .= " group by [$storageLocation].$groupingField";
			}
		}
		return $this->GetScalar($sql);
	}

	public function GetDataGroupMin($storageLocation, $columnName, $filter = null, $groupingField = null, $filterJoinLocations = null)
	{
		if ($filterJoinLocations && is_array($filterJoinLocations))
		{
			$sql = "select min($columnName) from [$storageLocation]";
			foreach ($filterJoinLocations as $filterJoinLocationName => $filterJoinLocationMatchColumn)
			{
				$sql .= ", [$filterJoinLocationName]";
			}
			if ($filter)
			{
				$sql .= " where $filter";
			}
			else
			{
				$sql .= " where 1=1 ";
			}
			foreach ($filterJoinLocations as $filterJoinLocationName => $filterJoinLocationMatchColumn)
			{
				if ($filterJoinLocationName && $filterJoinLocationMatchColumn)
				{
					if (is_array($filterJoinLocationMatchColumn))
					{
						$sql .= " and [$filterJoinLocationName].[" . $filterJoinLocationMatchColumn[0] . "] =
                                      [$storageLocation].[" . $filterJoinLocationMatchColumn[1] . "]";
					}
					else
					{
						$sql .= " and [$filterJoinLocationName].[$filterJoinLocationMatchColumn] =
                                      [$storageLocation].[$filterJoinLocationMatchColumn]";
					}
				}
			}
		}
		else
		{
			$sql = "select min($columnName) from [$storageLocation]";
			if ($filter)
			{
				$sql .= " where $filter";
			}
		}
		if ($groupingField)
		{
			if (strstr($groupingField, "."))
			{
				$sql .= " group by $groupingField";
			}
			else
			{
				$sql .= " group by [$storageLocation].$groupingField";
			}
		}
		return $this->GetScalar($sql);
	}

	public function GetDataGroupMax($storageLocation, $columnName, $filter = null, $groupingField = null, $filterJoinLocations = null)
	{
		if ($filterJoinLocations && is_array($filterJoinLocations))
		{
			$sql = "select max($columnName) from [$storageLocation]";
			foreach ($filterJoinLocations as $filterJoinLocationName => $filterJoinLocationMatchColumn)
			{
				$sql .= ", [$filterJoinLocationName]";
			}
			if ($filter)
			{
				$sql .= " where $filter";
			}
			else
			{
				$sql .= " where 1=1 ";
			}
			foreach ($filterJoinLocations as $filterJoinLocationName => $filterJoinLocationMatchColumn)
			{
				if ($filterJoinLocationName && $filterJoinLocationMatchColumn)
				{
					if (is_array($filterJoinLocationMatchColumn))
					{
						$sql .= " and [$filterJoinLocationName].[" . $filterJoinLocationMatchColumn[0] . "] =
                                      [$storageLocation].[" . $filterJoinLocationMatchColumn[1] . "]";
					}
					else
					{
						$sql .= " and [$filterJoinLocationName].[$filterJoinLocationMatchColumn] =
                                      [$storageLocation].[$filterJoinLocationMatchColumn]";
					}
				}
			}
		}
		else
		{
			$sql = "select max($columnName) from [$storageLocation]";
			if ($filter)
			{
				$sql .= " where $filter";
			}
		}
		if ($groupingField)
		{
			if (strstr($groupingField, "."))
			{
				$sql .= " group by $groupingField";
			}
			else
			{
				$sql .= " group by [$storageLocation].$groupingField";
			}
		}
		return $this->GetScalar($sql);
	}

	private function TableExists($tableName)
	{
		$isTableName = false;
		if (isset(self::$_tables[$this->_dbCatalog][$tableName]))
		{
			$isTableName = true;
		}
		else
		{
			$isTableName = false;
			foreach (self::$_tables[$this->_dbCatalog] as $testTableName => $tableValue)
			{
				if (strcasecmp($testTableName, $tableName) == 0)
				{
					$isTableName = true;
					break;
				}
			}
		}
		return $isTableName;
	}

	public function GetDataGroup($storageLocation, $filter = null, $orderByFieldName = null, $maxRecordCount = null,
	$groupingField = null, $filterJoinLocations = null, $startOffset = 0)
	{
		$isTableName = $this->TableExists($storageLocation);
		if ($maxRecordCount != null)
		{
			$recordCount = $maxRecordCount + $startOffset;
		}
		else
		{
			$recordCount = null;
		}
		if ($isTableName)
		{
			if ($filterJoinLocations && is_array($filterJoinLocations))
			{
				if ($recordCount)
				{
					$sql = "select top $recordCount [$storageLocation].* from [$storageLocation]";
				}
				else
				{
					$sql = "select [$storageLocation].* from [$storageLocation]";
				}

				foreach ($filterJoinLocations as $filterJoinLocationName => $filterJoinLocationMatchColumn)
				{
					@list($filterJoinLocationName, $alias) = (strstr($filterJoinLocationName, ' ')) ? explode(' ', $filterJoinLocationName) : array(
						$filterJoinLocationName,
						'');
					$sql .= ", [$filterJoinLocationName] $alias";
				}
				if ($filter)
				{
					$sql .= " where $filter";
				}
				else
				{
					$sql .= " where 1=1 ";
				}
				foreach ($filterJoinLocations as $filterJoinLocationName => $filterJoinLocationMatchColumn)
				{
					if ($filterJoinLocationName && $filterJoinLocationMatchColumn)
					{
						if (is_array($filterJoinLocationMatchColumn))
						{
							$sql .= " and [$filterJoinLocationName].[" . $filterJoinLocationMatchColumn[0] . "] =
                                          [$storageLocation].[" . $filterJoinLocationMatchColumn[1] . "]";
						}
						else
						{
							$sql .= " and [$filterJoinLocationName].[$filterJoinLocationMatchColumn] =
                                          [$storageLocation].[$filterJoinLocationMatchColumn]";
						}
					}
				}
				if ($groupingField)
				{
					if (strstr($groupingField, "."))
					{
						$sql .= " group by $groupingField";
					}
					else
					{
						$sql .= " group by [$storageLocation].$groupingField";
					}
				}
				if ($orderByFieldName)
				{
					if (strstr($orderByFieldName, ".") === false)
					{
						$sql .= " order by [$storageLocation].$orderByFieldName";
					}
					else
					{
						$sql .= " order by $orderByFieldName";
					}
				}
			}
			else
			{
				if ($recordCount)
				{
					$sql = "select top $recordCount * from [$storageLocation]";
				}
				else
				{
					$sql = "select * from [$storageLocation]";
				}
				if ($filter)
				{
					$sql .= " where $filter";
				}
				if ($groupingField)
				{
					$sql .= " group by [$storageLocation].$groupingField";
				}
				if ($orderByFieldName)
				{
					$sql .= " order by $orderByFieldName";
				}
			}
			$returnRows = $this->GetRowsAsArray($sql);
		}
		else
		{
			//its not the name of a table so lets try it as a stored proc
			$returnRows = $this->GetRowsSpAsArray($storageLocation, $filter);
		}
		if ($startOffset > 0)
		{
			$returnRows = array_slice($returnRows, $startOffset);
		}
		return $returnRows;
	}

	public function DeleteDataGroup($storageLocation, $filter = null)
	{
		$sql = "delete from $storageLocation";
		if ($filter)
		{
			$sql .= " where $filter";
		}
		return $this->Execute($sql);
	}

	public function DeleteData($storageLocation, $keyFieldName, $keyValue)
	{
		$sql = "delete from $storageLocation where $keyFieldName = '" . $keyValue . "'";
		return $this->Execute($sql);
	}

	function IsReady()
	{
		return $this->IsConnected();
	}

	public function GetDataFields($storageLocation)
	{
		//we dont want the fully qualified name.  So lets strip it down to the table name
		if (strstr($storageLocation, "."))
		{
			$dotloc		 = strpos($storageLocation, ".");
			$schemaName	 = substr($storageLocation, 0, $dotloc);
			$tableName	 = substr($storageLocation, $dotloc + 1);
		}
		else
		{
			$schemaName	 = $this->_dbCatalog;
			$tableName	 = $storageLocation;
		}
		$sql		 = "select top 1 * from [$tableName]";
		$data		 = $this->GetRows($sql);
		$numFields	 = sqlsrv_num_fields($data);
		$columnsInfo = sqlsrv_field_metadata($data);
		foreach ($columnsInfo as $colInfo)
		{
			$returnCols[] = $colInfo["Name"];
		}
		return $returnCols;
	}

	public function GetStorageLocations($filter = "")
	{
		$sql			 = "select * from " . $this->_dbCatalog . ".information_schema.tables";
		$data			 = $this->GetRowsAsArray($sql);
		$returnTables	 = array();
		foreach ($data as $row)
		{
			$returnTables[$row["TABLE_NAME"]] = array();
			if (strtoupper($row["TABLE_TYPE"]) == "VIEW")
			{
				$returnTables[$row["TABLE_NAME"]]["ReadOnly"] = true;
			}
			else
			{
				$returnTables[$row["TABLE_NAME"]]["ReadOnly"] = false;
			}

			$sql		 = "select o.name + '.' + c.name, o.name
							from " . $this->_dbCatalog . ".sys.columns c
							join " . $this->_dbCatalog . ".sys.objects o on c.object_id = o.object_id
							join " . $this->_dbCatalog . ".sys.schemas s on s.schema_id = o.schema_id
							where s.name = 'dbo'
							  and o.is_ms_shipped = 0 and o.type = 'U'
							  and c.is_identity = 1
							  and o.name = '" . $row["TABLE_NAME"] . "'
							order by o.name";
			$isIdentity	 = $this->GetRows($sql);
			if (sqlsrv_has_rows($isIdentity))
			{
				$isIdentity = true;
			}
			else
			{
				$isIdentity = false;
			}
			$returnTables[$row["TABLE_NAME"]]["KeyValueIsGeneratedByEngine"] = ($isIdentity ? true : false);
		}
		return $returnTables;
	}

	public function DoesLocationExist($storageLocation)
	{
		return $this->DoesTableExist($storageLocation);
	}

	public function GetParentReferences($storageLocation)
	{
		return array();
	}

	public function GetChildReferences($storageLocation)
	{
		return array();
	}

	public function Get_ConnectionName()
	{

	}

	public function Get_ModelGroup()
	{

	}

	public function Set_ModelGroup($storageGroup)
	{

	}

}
?>