<?php
namespace Wafl\Extensions\Storage\UnixOdbc;


final class UnixOdbc implements \DblEj\Data\Integration\IDatabaseServerExtension
{
	private $_dsn;
	private $_dbUser;
	private $_dbPassword;
	private $_odbcDriver;

	/**
	 * Represents the connection to the database.
	 *
	 * @var string
	 */
	private $_connectionId;
	private $_isConnected = false;

	public function __construct($dsn = null, $notUsed = null, $dbUser = null, $dbPassword = null)
	{
		$this->_dsn			 = $dsn;
		$this->_dbUser		 = $dbUser;
		$this->_dbPassword	 = $dbPassword;
		$this->_connectionId = odbc_connect($dsn, "", "");
		if ($this->_connectionId)
		{
			$this->_isConnected = true;
		}
		else
		{
			$this->_isConnected = false;
		}
	}

	public function DirectExecute($sql)
	{
		return $this->Execute($sql);
	}

	public function DirectQuery($sql)
	{
		return $this->GetRowsAsArray($sql);
	}

	public function LimitFilterResultLength($filter, $length)
	{
		throw new Exception("The filter cannot be used to limit result length in MS Access");
	}

	public function DirectScriptExecute($filenameOrContents, $isContents = false)
	{
		$sql		 = file_get_contents($filename);
		$sqlArray	 = explode(";", $sql);
		foreach ($sqlArray as $sql)
		{
			$sql = trim($sql);
			if ($sql)
			{
				$this->Execute($sql);
			}
		}
	}

	public function IsConnected()
	{
		return $this->_isConnected;
	}

	public function GetLastError()
	{
		if ($this->db)
		{
			return odbc_errormsg($this->_connectionId);
		}
	}

	public function BeginTransaction()
	{
		throw new \Exception("The UnixOdbc Storage Engine does not currently support transactions");
	}

	public function CommitTransaction()
	{
		throw new \Exception("The UnixOdbc Storage Engine does not currently support transactions");
	}

	public function RollbackTransaction()
	{
		throw new \Exception("The UnixOdbc Storage Engine does not currently support transactions");
	}

	public function GetLock($lockname, $timeout)
	{
		throw new \Exception("The UnixOdbc Storage Engine does not currently provide db level locking");
	}

	public function IsLocked($lockname)
	{
		throw new \Exception("The UnixOdbc Storage Engine does not currently provide db level locking");
	}

	public function ReleaseLock($lockname)
	{
		throw new \Exception("The UnixOdbc Storage Engine does not currently provide db level locking");
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
		while ($row		 = $rowsPointer->fetch_assoc())
		{
			array_push($rows, $row);
		}
		return $rows;
	}

	/**
	 * Get a table
	 *
	 * @param mixed $sql
	 * @param string $dbq
	 * @return mysqli_result $resultSet
	 */
	public function GetRows($sql, $dbq = "")
	{
		/**
		 * @var $rows array
		 */
		if ($this->IsConnected())
		{
			if ($dbq)
			{
				throw new \Exception("Cannot change dbq once connected.  Please pass null for the \$dbq parameter");
			}


			if (is_array($sql))
			{
				$statement	 = array_pop($sql);
				$arguments	 = array_pop($sql);
				$resourceid	 = odbc_prepare($this->_connectionId, $sql);
				$result		 = odbc_execute($resourceid, $arguments);
			}
			else
			{
				$result = odbc_exec($this->_connectionId, $sql);
			}

			$rows = odbc_fetch_array($result);
		}
		else
		{
			throw new \Exception("There was an error executing the database query: Database not connected");
		}
		return $rows;
	}

	public function SetCatalog($dbCatalog)
	{
		throw new \Exception("Cannot change dbq once connected.  Please pass null for the \$dbq parameter");
	}

	/**
	 * Get the first row returned from a query
	 *
	 * @param string $sql
	 * @param string $dbCatalog
	 * @return array
	 */
	public function GetFirstRow($sql, $dbCatalog = "", $arrayType = \MYSQLI_BOTH)
	{
		/**
		 * @var array $returnRow;
		 */
		$returnRow = null;

		$rows = $this->GetRows($sql, $dbCatalog);
		if (count($rows) > 0)
		{
			$returnRow = array_pop($rows);
		}
		return $returnRow;
	}

	public function GetScalar($sql, $dbCatalog = "")
	{
		$row = $this->GetFirstRow($sql, $dbCatalog);
		if ($row && count($row) > 0)
		{
			return $row[0];
		}
		else
		{
			return null;
		}
	}

	public function GetScalarSp($procName, $argList = "", $dbCatalog = "")
	{
		$row = $this->GetFirstRowSp($procName, $argList, $dbCatalog);
		if ($row && count($row) > 0)
		{
			return $row[0];
		}
		else
		{
			return "";
		}
	}

	public function GetRowsSP($procName, $argList = "", $dbCatalog = "")
	{
		throw new \Exception("Not yet implemented in DblEj UnixOdbc driver");
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
			if (count($rows))
			{
				$returnRow = array_pop($rows);
			}
		}
		return $returnRow;
	}

	public function GetRowsSpAsArray($procName, $argList = "", $dbCatalog = "")
	{
		/**
		 * @var $dbResult mysqli_result
		 */
		return $this->GetRowsSP($procName, $argList, $dbCatalog);
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
		/**
		 * @var $rows array
		 */
		if ($this->IsConnected())
		{
			if ($dbq)
			{
				throw new \Exception("Cannot change dbq once connected.  Please pass null for the \$dbq parameter");
			}

			if (is_array($sql))
			{
				$statement	 = array_pop($sql);
				$arguments	 = array_pop($sql);
				$resourceid	 = odbc_prepare($this->_connectionId, $sql);
				$result		 = odbc_execute($resourceid, $arguments);
			}
			else
			{
				$result = odbc_exec($this->_connectionId, $sql);
			}
		}
		else
		{
			throw new \Exception("There was an error executing the database query: Database not connected");
		}
		return $rows;
	}

	public function ExecuteSP($procName, $args = "", $dbCatalog = "")
	{
		throw new \Exception("Not yet implemented in DblEj UnixOdbc driver");
	}

	public function GetLastInsertId()
	{
		throw new \Exception("Not yet implemented in DblEj UnixOdbc driver");
	}

	public function GetLastAffectedCount()
	{
		throw new \Exception("Not yet implemented in DblEj UnixOdbc driver");
	}

	public function TruncateTable($table)
	{
		$sql = "delete from $table";
		return $this->Execute($sql);
	}

	public function EscapeString($string)
	{
		throw new \Exception("Not yet implemented in DblEj UnixOdbc driver");
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
			throw new \Exception("UnixODBC insert failed with the following error: " . odbc_errormsg($this->_connectionId));
			return false;
		}
	}

	public function UpsertRow($table, $columnNames, $columnValues, $keyColumnName, $isAutoIncrementingKey)
	{
		$sql		 = "insert into $table (";
		$beenHere	 = 0;
		foreach ($columnNames as $colName)
		{
			if (!$isAutoIncrementingKey || $colName != $keyColumnName)
			{

			}
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
		foreach ($columnValues as $colVal)
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
		$sql .= ")";

		if ($isAutoIncrementingKey)
		{
			$sql .= " on duplicate key update
                        $keyColumnName = last_insert_id($keyColumnName)";
			foreach ($columnNames as $colName)
			{
				if ($colName != $keyColumnName)
				{
					$colVal = $columnValues[$colName];
					if ($colVal === null)
					{
						$sql .= ",$colName=null";
					}
					elseif ($colVal === true)
					{
						$sql .= ",$colName=1";
					}
					elseif ($colVal === false)
					{
						$sql .= ",$colName=0";
					}
					else
					{
						$sql .= ",$colName='$colVal'";
					}
				}
			}
		}
		else
		{
			$sql .= " on duplicate key update ";
			$beenHere = 0;
			foreach ($columnNames as $colName)
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
			throw new \DblEj\Data\DataException("UnixODBC upsert failed with the following error: " . $e->getMessage(), E_WARNING, $e);
			return false;
		}
	}

	public function DoesTableExist($tableName)
	{
		throw new \Exception("Not yet implemented in DblEj UnixOdbc driver");
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

	public function GetData($storageLocation, $keyFieldName, $keyValue)
	{
		$sql = "select * from $storageLocation where $keyFieldName = '" . $this->EscapeString($keyValue) . "'";
		return $this->GetFirstRow($sql, null, \MYSQLI_ASSOC);
	}

	public function GetScalarData($storageLocation, $returnFieldName, $keyFieldName, $keyValue)
	{
		$sql = "select $returnFieldName from $storageLocation where $keyFieldName = '" . $this->EscapeString($keyValue) . "'";
		return $this->GetScalar($sql);
	}

	public function GetDataGroupCount($storageLocation, $filter = null, $groupingField = null, $filterJoinLocations = null)
	{
		$sql = "select count(*) from $storageLocation";
		if ($filter)
		{
			$sql .= " where $filter";
		}
		return $this->GetScalar($sql);
	}

	public function GetDataGroupSum($storageLocation, $columnName, $filter = null, $groupingField = null, $filterJoinLocations = null)
	{
		if ($filterJoinLocations && is_array($filterJoinLocations))
		{
			$sql = "select sum($columnName) from $storageLocation";
			foreach ($filterJoinLocations as $filterJoinLocationName => $filterJoinLocationMatchColumn)
			{
				$sql .= ", $filterJoinLocationName";
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
						$sql .= " and $filterJoinLocationName." . $filterJoinLocationMatchColumn[0] . " =
                                      $storageLocation." . $filterJoinLocationMatchColumn[1] . "";
					}
					else
					{
						$sql .= " and $filterJoinLocationName.$filterJoinLocationMatchColumn =
                                      $storageLocation.$filterJoinLocationMatchColumn";
					}
				}
			}
		}
		else
		{
			$sql = "select sum($columnName) from $storageLocation";
			if ($filter)
			{
				$sql .= " where $filter";
			}
		}
		if ($groupingField)
		{
			$sql .= " group by $storageLocation.$groupingField";
		}
		return $this->GetScalar($sql);
	}

	public function GetDataGroupMin($storageLocation, $columnName, $filter = null, $groupingField = null, $filterJoinLocations = null)
	{
		throw new \Exception("GetDataGroupMin not implemented in UnixOdbc storage engine");
	}

	public function GetDataGroupMax($storageLocation, $columnName, $filter = null, $groupingField = null, $filterJoinLocations = null)
	{
		throw new \Exception("GetDataGroupMax not implemented in UnixOdbc storage engine");
	}

	public function GetDataGroupAvg($storageLocation, $columnName, $filter = null, $groupingField = null, $filterJoinLocations = null)
	{
		if ($filterJoinLocations && is_array($filterJoinLocations))
		{
			$sql = "select avg($columnName) from $storageLocation";
			foreach ($filterJoinLocations as $filterJoinLocationName => $filterJoinLocationMatchColumn)
			{
				$sql .= ", $filterJoinLocationName";
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
						$sql .= " and $filterJoinLocationName." . $filterJoinLocationMatchColumn[0] . " =
                                      $storageLocation." . $filterJoinLocationMatchColumn[1] . "";
					}
					else
					{
						$sql .= " and $filterJoinLocationName.$filterJoinLocationMatchColumn =
                                      $storageLocation.$filterJoinLocationMatchColumn";
					}
				}
			}
		}
		else
		{
			$sql = "select avg($columnName) from $storageLocation";
			if ($filter)
			{
				$sql .= " where $filter";
			}
		}
		if ($groupingField)
		{
			$sql .= " group by $storageLocation.$groupingField";
		}
		return $this->GetScalar($sql);
	}

	public function GetDataGroup($storageLocation, $filter = null, $orderByFieldName = null, $maxRecordCount = null,
	$groupingField = null, $filterJoinLocations = null, $startOffset = 0)
	{
		$sql = "select * from $storageLocation";
		if ($filter)
		{
			$sql .= " where $filter";
		}
		if ($orderByFieldName)
		{
			$sql .= " order by $orderByFieldName";
		}
		return $this->GetRowsAsArray($sql);
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
		$sql = "delete from $storageLocation where $keyFieldName = '" . $this->EscapeString($keyValue) . "'";
		$this->Execute($sql);
	}

	function IsReady()
	{
		return $this->IsConnected();
	}

	public function GetDataFields($storageLocation)
	{
		//we dont want the fully qualified name.  So lets strip it down to the tabel name
		if (strstr($storageLocation, "."))
		{
			$dotloc		 = strpos($storageLocation, ".");
			$schemaName	 = substr($storageLocation, 0, $dotloc);
			$tableName	 = substr($storageLocation, $dotloc + 1);
			$result		 = odbc_columns($this->_connectionId, "$schemaName", "%", "$tableName", "%");
		}
		else
		{
			$result = odbc_columns($this->_connectionId, "", "%", "$storageLocation", "%");
		}
		$data		 = odbc_fetch_array($result);
		$returnCols	 = array();
		foreach ($data as $row)
		{
			array_push($returnCols, $row["COLUMN_NAME"]);
		}
		return $returnCols;
	}

	/**
	 *
	 * @param array $filter for this driver you can supply two filters, in an array.  The first for the table name, and the secod for table types "VIEWS, TABLES"
	 * @return array
	 */
	public function GetStorageLocations($filter = "")
	{
		if (is_array($filter))
		{
			$namesFilter = $filter[0];
			$typesFilter = $filter[1];
		}
		$data		 = odbc_tables($this->_connectionId, null, null, $namesFilter, $typesFilter);
		$returnCols	 = array();
		foreach ($data as $row)
		{
			array_push($returnCols, $row["TABLE_NAME"]);
		}
		return $returnCols;
	}

	public function DoesLocationExist($storageLocation)
	{
		return $this->DoesTableExist($storageLocation);
	}

	public function Get_ConnectionName()
	{

	}

	public function Get_CreateScript()
	{

	}

	public function Get_ModelGroup()
	{

	}

	public function Get_RequiredStorageLocation()
	{

	}

	public function Get_UpdateScript()
	{

	}

	public function SetConnectionCharacterEncoding($encoding)
	{

	}

	public function Set_CreateScript($createScript)
	{

	}

	public function Set_ModelGroup($storageGroup)
	{

	}

	public function Set_RequiredStorageLocation($requiredLocation)
	{

	}

	public function Set_UpdateScript($updateScript)
	{

	}

	public function UpdateStorageLocations()
	{

	}

}
?>