<?php
namespace Wafl\Extensions\Storage\MySql;

use DblEj\Data\DatabaseUpdateException,
    DblEj\Data\DataException,
    DblEj\Data\Field,
    DblEj\Data\FieldCollection,
    DblEj\Data\ForeignKey,
    DblEj\Data\IDatabaseEngine,
    DblEj\Data\InvalidDataException,
    DblEj\Data\ScriptExecutedEvent,
    DblEj\Data\StorageEngineNotReadyException,
    DblEj\EventHandling\EventRaiser,
    DblEj\EventHandling\EventTypeCollection,
    Exception,
    mysqli,
    mysqli_result;

/**
 * A connection to a MySql server.
 */
class Connection
extends EventRaiser
implements \DblEj\Data\IDatabaseConnection
{
	private $_dbServer;
	private $_dbUser;
	private $_dbPassword;
	private $_dbCatalog;
	private $_lastQuery;
    private $_dbPort = 3306;

	private static $_designateViewsByStringPrefix = "";

	/**
	 * Represents the connection to the database.
	 *
	 * @var mysqli
	 */
	private $_db;
	private $_isConnected	 = false;
	private static $_tables	 = array();
	private $_createScript;
	private $_updateScript;
	private $_requiredLocation;
	private $_modelGroup	 = "Default";
    private $_charEncoding   = "utf8";

    /**
     * A script that will create an application-defined data structure on this storage engine.
     *
     * It is typical for an application to have only a single instance of a storage engine,
     * or to have one for each module of an application.  This property offers a convenient way to
     * associate the create script that can create the needed data structure for the related module.
     *
     * @return string The name of the create script.
     */
	function Get_CreateScript()
	{
		return $this->_createScript;
	}

    /**
     * Specify the name of the create script.
     *
     * @param string $createScript
     */
	function Set_CreateScript($createScript)
	{
		$this->_createScript = $createScript;
	}

    /**
     * A script that will update an application-defined data structure to the correct version.
     *
     * It is typical for an application to have only a single instance of a storage engine,
     * or to have one for each module of an application.  This property offers a convenient way to
     * associate the next update script that can update the data structure as needed
     * for the related module.
     *
     * @return string The name of the update script.
     */
	function Get_UpdateScript()
	{
		return $this->_updateScript;
	}

    /**
     * Set the name of the update script.
     *
     * @param string $updateScript
     */
	function Set_UpdateScript($updateScript)
	{
		$this->_updateScript = $updateScript;
	}

    /**
     * The name of a table that an application expects to exist
     * on the mySql server.  This let's an application detect if any databases
     * that it depends on have not been created.
     *
     * @return string The name of a required table.
     */
	function Get_RequiredStorageLocation()
	{
		return $this->_requiredLocation;
	}

    /**
     * Set the required table name.
     *
     * @param string $requiredLocation The name of the required table.
     */
	function Set_RequiredStorageLocation($requiredLocation)
	{
		$this->_requiredLocation = $requiredLocation;
	}

    public static function Set_DesignateViewsByStringPrefix($strViewStringPrefix)
    {
        static::$_designateViewsByStringPrefix = $strViewStringPrefix;
    }
	public function Connect()
	{
		$this->_db = mysqli_init();
        if (defined("MYSQLI_OPT_INT_AND_FLOAT_NATIVE"))
        {
            mysqli_options($this->_db, MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);
        }

		if (mysqli_real_connect($this->_db, $this->_dbServer, $this->_dbUser, $this->_dbPassword, $this->_dbCatalog, $this->_dbPort))
		{
			try
			{
				if ($this->_db->get_server_info())
				{
					$this->_isConnected = true;
                    if ($this->_charEncoding)
                    {
                        $this->_db->set_charset($this->_charEncoding);
                    }
				}
			}
			catch (\Exception $e)
			{
				$this->_isConnected = false;
			}
		}
	}

    /**
     * A name identifying this instance's session/connection to the underlying data source.
     *
     * @return string The name of the connection.
     */
	public function Get_ConnectionName()
	{
		return $this->_dbCatalog;
	}

    /**
     * Connect to a MySql server.
     *
     * @param string $dbServer The host name or IP address of the MySql server.
     * @param string $dbCatalog The initial catalog to connect to.
     * @param string $dbUser The user to login with.
     * @param string $dbPassword The password to login with.
     */
	public function __construct($dbServer = null, $dbCatalog = null, $dbUser = null, $dbPassword = null, $dbPort = 3306)
	{
        parent::__construct();
            $this->_dbServer	 = $dbServer;
            $this->_dbUser		 = $dbUser;
            $this->_dbPassword	 = $dbPassword;
            $this->_dbCatalog	 = $dbCatalog;
            $this->_dbPort       = $dbPort;
	}

    /**
     * Query the mySql server for a list of tables in the current catalog,
     * and update the internal list of tables.
     *
     * @return void
     */
	public function UpdateStorageLocations()
	{
		self::$_tables[$this->_dbCatalog] = $this->GetStorageLocations();
	}

    /**
     * Set the character encoding to be used when transmitting data to/from the mySql server.
     * @param string $charSet A string identifying the character encoding to use such as "utf8".
     */
	function Set_CharacterEncoding($charSet)
	{
        $this->_charEncoding = $charSet;
	}

	/**
	 * Send a SQL statement directly to the mySql server.
	 * @param string $sql The SQL statement to execute.
     * @return boolean <i>True</i> on success, otherwise <i>false</i>.
	 */
	public function DirectExecute($sql)
	{
		$this->_lastQuery = $sql;
		return $this->Execute($sql);
	}

	/**
	 * Send a SQL query directly to the mySql server.
	 * @param string $sql The query to send.
	 * @return array The rows returned by the specified query.
	 */
	public function DirectQuery($sql)
	{
		$this->_lastQuery = $sql;
		return $this->GetRowsAsArray($sql);
	}

    /**
     * Execute the specified SQL script.
     *
     * @param string $filenameOrContents The name of the file that contains the script, or the actual contents of the script.
     * @param boolean $isContents If <i>true</i> then <i>$filenameOrContents</i> contains the script contents,
     * otherwise <i>$filenameOrContents</i> contains the name of a file.
     *
     * @return boolean <i>True</i> on success, otherwise <i>false</i>.
     */
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
        $sql = str_replace("\r\n", "\n", $sql);
		$sqlArray	 = array_filter(explode(";\n", $sql));
		$total		 = count($sqlArray);
		$current	 = 0;
		foreach ($sqlArray as $sql)
		{
			$current++;
			$sql = trim($sql);
			if ($sql)
			{
				$this->Execute($sql);
				$this->raiseEvent(new ScriptExecutedEvent("MySql Driver", $current, $total));
			}
		}
        return true;
	}

    /**
     * Whether or not there is an active connection to this mySql server.
     *
     * @return boolean <i>True</i> if there is an active connection to this mySql server, otherwise <i>false</i>.
     */
    public function IsConnected()
	{
		return $this->_isConnected;
	}

    /**
     * A string representing the last error that occurred in this connection.
     *
     * @return string the last error that occurred.
     */
	public function GetLastError()
	{
		if ($this->_db)
		{
			return $this->_db->error;
		}
	}

    /**
     * Start a transaction.
     *
     * @return boolean <i>True</i> on success, otherwise <i>false</i>.
     */
    public function BeginTransaction()
	{
		if ($this->IsConnected())
		{
			if (!$this->_db->real_query("START TRANSACTION"))
			{
				throw new Exception("There was an error beginning the database transaction<p>: " . $this->GetLastError() . "</p>");
			}
		}
		else
		{
			throw new Exception("There was an error beginning the database query: Database not connected");
		}
		return true;
	}

    /**
     * Commit a transaction.
     *
     * @return boolean <i>True</i> on success, otherwise <i>false</i>.
     */
    public function CommitTransaction()
	{
		if ($this->IsConnected())
		{
			if (!$this->_db->real_query("COMMIT"))
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

    /**
     * Roll back a transaction.
     *
     * @return boolean <i>True</i> on success, otherwise <i>false</i>.
     */
    public function RollbackTransaction()
	{
		if ($this->IsConnected())
		{
			if (!$this->_db->real_query("ROLLBACK"))
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

    /**
     * Lock the specified name on the engine or catalog.
     *
     * @param string $lockname The name of the lock to get.
     * @param string $timeout The amount of time, in seconds, to wait for the lock before timing out.
     * This can help reduce/prevent waiting too long for a lock and potential dead-locks and
     * other race-conditions.
     */
	public function GetLock($lockname, $timeout = null)
	{
        if (!$timeout)
        {
            $timeout = 10;
        }
		$sql	 = "select Get_Lock('$lockname',$timeout)";
		$lockok	 = $this->GetScalar($sql);
		return $lockok;
	}

    /**
     * Return a boolean indicating if the engine or catalog
     * is currently locked by the specified </i>$lockname</i>.
     *
     * @param string $lockname The name of the lock to check for.
     * @return boolean Whether or not the lock exists and is currently active.
     */
    public function IsLocked($lockname)
	{
		$sql		 = "select Is_Used_Lock('$lockname')";
		$islocked	 = $this->GetScalar($sql);
		$islocked	 = ($islocked != null);
		return $islocked;
	}

    /**
     * Release the engine or catalog-level lock of the specified name.
     *
     * @param string $lockname The name of the lock to release.
     */
	public function ReleaseLock($lockname)
	{
		$sql	 = "select Release_Lock('$lockname')";
		$lockok	 = $this->GetScalar($sql);
		return $lockok;
	}

    /**
     * Returns a modified version of the specified $filter which includes a limit to the number of allowed rows in a result.
     * @param string $filter The implementation specific filter string to modify.
     * @param int $length The maximum number of rows that the filter should return.
     * @return string The modified filter.
     */
	public function LimitFilterResultLength($filter, $length)
	{
		return $filter . " limit 0,$length";
	}

    /**
     * Get an array of data rows (represented by an associative array) from the
     * mySql server based on the passed <i>$query</i>.
     * This method returns the entire result of the query and so memory should be
     * considered when using this method on large query's.  For larger queries, you
     * may want to use GetRows() instead and then fetch a single row at a time using <i>GetNextRow()</i>.
     *
     * @param string $sql
     * A string that specifies the query that will return the desired rows.
     * For most common relational database engines, this is a SQL string.
     *
     * @param type $dbCatalog
     * The name of the catalog to run the query against.
     *
     * @return array
     * An array of associative sub-arrays where each sub-array represents a row of data.
     * Each element in the sub array is keyed by the column name and the value represents the value
     * for that column in that row.
     *
     * For tables with a primary key, the outer array will be keyed by the value in the primary key column.
     * For tables without a primary key, the outer array will be numerically indexed in sequence.
     */
	public function GetRowsAsArray($sql, $dbCatalog = null, $args=null)
	{
		/**
		 * @var $rows mysqli_result
		 */
		$rowsPointer = $this->GetRows($sql, $dbCatalog);
		$rows		 = array();
		if (!is_object($rowsPointer))
		{
			throw new Exception("Invalid data returned from MySql server for query: $sql");
		}
		while ($row = $rowsPointer->fetch_assoc())
		{
			array_push($rows, $row);
		}
		return $rows;
	}

    /**
     * Ask the engine for rows of data based on the query specified by <i>$sql</i>.
     * This will prepare the result of the query, but will not actually return the result.
     * This can be useful for keeping memory consumption down on larger queries.
     *
     * To get the result data, you can use <i>GetNextRow</i> on the object returned by <i>GetRows</i>
     * to fetch a single row at a time.
     *
     * @param string $sql
     * A string that specifies the query that will return the desired rows.
     *
     * @param type $dbCatalog
     * The name of the catalog to run the query against.
     *
     * @return mixed
     * An object that identifies the result of the query.
     * This object can subsequently be passed to GetNextRow() to fetch the next row of data.
     */
	public function GetRows($sql, $dbCatalog = null, $args = null)
	{
		/**
		 * @var $rows mysqli_result
		 */
		if ($this->IsConnected())
		{
			if ($dbCatalog)
			{
				$this->_dbCatalog = $dbCatalog;
				$this->_db->select_db($this->_dbCatalog);
			}
			$this->_lastQuery = $sql;
			if ($this->_db->real_query($sql))
			{
				$rows = $this->_db->store_result();
			}
			else
			{
				if (isset($_SERVER["HTTP_HOST"]))
				{
					throw new Exception("There was an error executing the database query<br><code style='background-color: rgb(255,255,240);border: dotted 1px #a9a9a9; color: #000000;'>$sql</code><br>" . $this->GetLastError());
				} else {
					throw new Exception("There was an error executing the database query:\n $sql. " . $this->GetLastError());
				}
			}
		}
		else
		{
			throw new Exception("There was an error executing the database query: Database not connected");
		}
		return $rows;
	}

    /**
     * Get the next row available in a result prepared by <i>GetRows</i> or <i>GetRowsSp</i>.
     *
     * @param mixed $rows A result from calling the <i>GetRows</i> method or the <i>GetRowsSp</i> method.
     *
     * @return array An associative array representing the row of data where each array element's
     * key corresponds to the column name of the data in that element.
     */
    public function GetNextRow($rows)
    {
        return $rows->fetch_assoc();
    }

    /**
     * Instruct the engine to switch to the specified catalog.
     *
     * @param string $dbCatalog The name of the catalog.
     */
    public function SetCatalog($dbCatalog)
	{
		if ($this->IsConnected())
		{
			if ($dbCatalog)
			{
				$this->_dbCatalog = $dbCatalog;
				$this->_db->select_db($this->_dbCatalog);
			}
		}
		else
		{
			throw new Exception("There was an error executing the database query: Database not connected");
		}
	}
    public function Get_Catalog()
    {
        return $this->_dbCatalog;
    }
    public function Get_Port()
    {
        return $this->_dbPort;
    }

    /**
     * Get an array of data representing the first row returned by the specified <i>$query</i>.
     *
     * @param string $query The query string used to specify which data to return.
     * @param string $dbCatalog The name of the database or catalog to run the query against.
     * If blank then the current catalog (the one last sent to <i>SetCatalog</i>) will be used.
     *
     * @return array An array of data representing the first row returned by the specified <i>$query</i>.
     */
	public function GetFirstRow($sql, $dbCatalog = "", $arrayType = \MYSQLI_BOTH)
	{
		/**
		 * @var array $returnRow;
		 */
		$returnRow = null;

		$rows = $this->GetRows($sql, $dbCatalog);
		if ($rows->field_count > 0)
		{
			$returnRow = $rows->fetch_array($arrayType);
		}
		return $returnRow;
	}

    /**
     * Run a query that returns a scalar response, and return that value.
     *
     * @param string $sql The query to run whose result will be a scalar value.
     * @param string $dbCatalog The name of the catalog to run the query against.
     * If blank then the current catalog (the one last sent to <i>SetCatalog</i>) will be used.
     *
     * @return mixed A single value as a string or a number.
     */
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

    /**
     * Execute a stored procedure that returns a scalar response, and return that value.
     *
     * @param string $procName The name of the stored procedure to run.
     *
     * @param string $argList
     * a comma delimited string of argument values to be sent to the stored procedure.
     *
     * @param string $dbCatalog The name of the catalog that contains the specified stored procedure.
     * If blank then the current catalog (the one last sent to <i>SetCatalog</i>) will be used.
     *
     * @return mixed A single value as a string or a number.
     */
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

    /**
     * Call and prepare the result set for the specified stored procedure.
     *
     * To get the result data, you can use <i>GetNextRow</i> on the object
     * returned by this method to get a single row at a time.
     *
     * @param string $procName
     * The name of the stored procedure to run.
     *
     * @param string $argList
     * A string argument to be sent to the stored procedure.
     * This is usually a comma delimited string of argument values.
     *
     * @param string $dbCatalog
     * The name of the catalog to run the procedure against.
     *
     * @return mixed
     * An object that identifies the last result set returned by the specified stored procedure.
     * This object can subsequently be passed to GetNextRow() to get the next row of data.
     */
	public function GetRowsSP($procName, $argList = "", $dbCatalog = "")
	{
		/**
		 * @var $rows mysqli_result
		 */
		if ($this->IsConnected())
		{
			if ($dbCatalog)
			{
				$this->_dbCatalog = $dbCatalog;
				$this->_db->select_db($this->_dbCatalog);
			}
			$querySuccess = $this->_db->multi_query("call $procName($argList)");

			if ($querySuccess)
			{
				$rows = $this->_db->store_result();


				while ($this->_db->more_results())
				{
					if ($this->_db->next_result())
					{
						$result = $this->_db->use_result();
						if ($result instanceof mysqli_result)
						{
							$result->free();
						}
					}
				}
			}
			else
			{
				$rows = null;
			}
		}
		else
		{
			$rows = null;
		}
		return $rows;
	}

    /**
     * Get an array of data representing the first row returned by the specified stored procedure.
     *
     * @param string $procName
     * The name of the stored procedure to run.
     *
     * @param string $argList
     * A comma delimited string of argument values to be sent to the stored procedure.
     *
     * @param string $dbCatalog
     * The name of the catalog to run the procedure against.
     *
     * @return array An array of data representing the first row returned by the specified stored procedure.
     */
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
				$returnRow = $rows->fetch_array(MYSQLI_BOTH);
			}
		}
		return $returnRow;
	}


    /**
     * Get an array of data rows (represented by an associative array) from the
     * mySql engine based on the passed stored procedure.
     * This method returns the entire result of the procedure and so memory should be
     * considered when using this method on procedures that return large data sets.
     * For larger data sets, you may want to use GetRowsSp() instead and then fetch
     * a single row at a time using <i>GetNextRow()</i>.
     *
     * @param string $procName
     * The name of the stored procedure to run.
     *
     * @param string $argList
     * A comma delimited string of argument values to be sent to the stored procedure.
     *
     * @param string $dbCatalog
     * The name of the catalog to run the procedure against.
     *
     * @return array
     * An array of associative sub-arrays where each sub-array represents a row of data.
     * Each element in the sub array is keyed by the column name and the value represents the value
     * for that column in that row.
     *
     * For tables with a primary key, the outer array will be keyed by the value in the primary key column.
     * For tables without a primary key, the outer array will be numerically indexed in sequence.
     */
	public function GetRowsSpAsArray($procName, $argList = "", $dbCatalog = "")
	{
		/**
		 * @var $dbResult mysqli_result
		 */
		if ($this->IsConnected())
		{
			if ($dbCatalog)
			{
				$this->_dbCatalog = $dbCatalog;
				$this->_db->select_db($this->_dbCatalog);
			}
			$querySuccess = $this->_db->multi_query("call $procName($argList)");


			if ($querySuccess)
			{
				$dbResult = $this->_db->store_result();

				while ($this->_db->more_results())
				{
					if ($this->_db->next_result())
					{
						$result = $this->_db->use_result();
						if ($result instanceof mysqli_result)
						{
							$result->free();
						}
					}
				}
			}
			else
			{
				throw new Exception("There was an error calling a stored procedure: $procName($argList)");
			}
		}
		else
		{
			$dbResult = null;
		}
		$rows = array();
		if ($dbResult)
		{
			while ($row = $dbResult->fetch_assoc())
			{
				array_push($rows, $row);
			}
		}
		return $rows;
	}

    /**
     * Execute the specified query.
     *
     * @param string $sql
     * The command to execute.
     * This can be a SQL statment or whatever the database engine understands.
     *
     * @param string $dbCatalog
     * The name of the catalog to run the command against.
     *
     * @return boolean The implementing class should return <i>true</i> on success and </i>false</i> on failure.
     */
    public function Execute($sql = null, $dbCatalog = null, $parameters=null)
	{
		if ($this->IsConnected())
		{
			if ($dbCatalog)
			{
				$this->_dbCatalog = $dbCatalog;
				$this->_db->select_db($this->_dbCatalog);
			}
			$this->_lastQuery = $sql;

            if ($parameters)
            {
                $prepareResult = $this->_db->prepare($sql);
                if ($prepareResult === false)
                {
                    throw new \Exception($this->GetLastError());
                }

                $typeString="";
                foreach ($parameters as $parameter)
                {
                    if (is_int($parameter))
                    {
                        $typeString.="i";
                    }
                    elseif (is_double($parameter))
                    {
                        $typeString.="d";
                    }
                    else
                    {
                        $typeString.="s";
                    }
                }
                $this->_bindParams($prepareResult, $typeString, $parameters);
                $executeResult = $prepareResult->execute();
                if (!$executeResult)
                {
                    $paramPrint = print_r($parameters, true);
                    throw new DataException("Error executing sql query, " . $prepareResult->error . ", Query: ($sql), Params: ($paramPrint)", E_WARNING);
                }
                $prepareResult->close();
            } else {
                $executeResult = $this->_db->real_query($sql);
            }
			if (!$executeResult)
			{
				throw new DataException("Error executing sql query, " . $this->GetLastError() . ", Query: ($sql)", E_WARNING);
			}
			else
			{
				return true;
			}
		}
		else
		{
			throw new StorageEngineNotReadyException("Cannot execute sql query because I am not connected to a database server",
														E_WARNING);
		}
	}
    private function _bindParams(\mysqli_stmt $statement, $typesString, $valueArray)
    {
        $refArray=[];
        $refArray[0]=$typesString;
        foreach ($valueArray as $valIdx=>$value)
        {
            $refArray[$valIdx+1] = &$valueArray[$valIdx];
        }
        \call_user_func_array([$statement,"bind_param"], $refArray);
        return $refArray;
    }
    /**
     * Execute the specified stored procedure.
     *
     * @param string $procName
     * The name of the stored procedure to run.
     *
     * @param string $args
     * A comma delimited string of argument values argument to be sent to the stored procedure.
     *
     * @param string $dbCatalog
     * The name of the catalog that contains the procedure.
     *
     * @return boolean <i>True</i> on success and </i>false</i> on failure.
     */
	public function ExecuteSP($procName, $args = "", $dbCatalog = "")
	{
		if ($this->IsConnected())
		{
			if ($dbCatalog)
			{
				$this->_dbCatalog = $dbCatalog;
				$this->_db->select_db($this->_dbCatalog);
			}
			$sql = "call $procName($args)";
			$this->_lastQuery = $sql;

			if (!$this->_db->real_query($sql))
			{
				throw new DataException($this->GetLastError() . ", Error executing stored procedure: $procName($args)");
			}
            return true;
		}
		else
		{
			return false;
		}
	}

    /**
     * Get the primary key of the row that was last inserted into a table that
     * has a primary key defined.
     *
     * @return  mixed The id of the row that was last inserted during this connection.
     */
	public function GetLastInsertId()
	{
		if ($this->IsConnected())
		{
			return $this->_db->insert_id;
		}
		else
		{
			return null;
		}
	}

    /**
     * Get the number of rows affected by the last executed command in the current connection.
     *
     * @return  int The number of rows affected by the last executed command.
     */
	public function GetLastAffectedCount()
	{
		if ($this->IsConnected())
		{
			return $this->_db->affected_rows;
		}
		else
		{
			return 0;
		}
	}

    /**
     * Delete all rows from a table.
     * @param string $table The name of the table to truncate.
     *
     * @return boolean <i>True</i> on success and </i>false</i> on failure.
     */
	public function TruncateTable($table)
	{
		$sql = "delete from $table";
		return $this->Execute($sql);
	}

    /**
     * Escapes any characters in a string that are illegal as a value in
     * an insert statement, update statement, or a (where, join, group by) clause.
     *
     * @param string $string The string to be escaped.
     */
	public function EscapeString($string)
	{

		if ($this->_isConnected)
		{
			return $this->_db->real_escape_string($string);
		}
		else
		{
			return $string;
		}
	}

    /**
     * Insert a row into the specified table.
     *
     * @param string $table The table to insert the row into.
     * @param string[] $columnNames The names of the columns to populate when inserting the row.
     * @param string[] $columnValues The values to populate the columns specified by <i>$columnNames</i> with.
     */
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
			throw new Exception("Mysql insert failed with the following error: " . mysql_error());
			return false;
		}
	}

    /**
     * Insert a new row into the specified table if the value for <i>$keyColumnName</i> is unique,
     * or update an existing row if there is already a row
     * that has the same value for the <i>$keyColumnName</i> column as the value
     * specified in <i>$columnValues[$keyColumnName]</i>.
     *
     * @param string $table The table to upsert the row into.
     * @param string[] $columnNames The names of the columns to populate when inserting the row.
     * @param string[] $columnValues The values to populate the columns specified by <i>$columnNames</i> with.
     * @param string $keyColumnName The name of the column to be used as a key when identifying
     * if a row already exists or not.
     * @param boolean $isAutoIncrementingKey
     * <i>True</i> if the key in the destination table is auto-generated by the engine,
     * otherwise <i>false</i>.
     * Some engines need this information to detect when an insert is a duplicate and should be an update instead.
     *
     * @return $boolean <i>True</i> on success, otherwise <i>false</i>.
     */
	public function UpsertRow($table, $columnNames, $columnValues, $keyColumnName, $isAutoIncrementingKey)
	{
        $preparedColumns=[];

		$sql		 = "insert into $table (";
		$beenHere	 = 0;
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
			$sql .= "`$colName`";
		}
		$sql .= ") values (";
		$beenHere = 0;
		foreach ($columnValues as $colName => $colVal)
		{
            if (!is_scalar($colVal) && !is_null($colVal))
            {
                throw new InvalidDataException("MySql Storage Engine: Data field values must be scalar (or null).  A value of type <i>".gettype($colVal)."</i> was passed for the \"$colName\" column.");
            }
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
				$sql .= "?";
                $preparedColumns[count($preparedColumns)]=null;
			}
			elseif ($colVal === true)
			{
				$sql .= "?";
                $preparedColumns[count($preparedColumns)]=1;
			}
			elseif ($colVal === false)
			{
				$sql .= "?";
                $preparedColumns[count($preparedColumns)]=0;
			}
			else
			{
				$sql .= "?";
                $preparedColumns[count($preparedColumns)]=$colVal;
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
						$sql .= ",`$colName`=?";
                        $preparedColumns[count($preparedColumns)]=null;
					}
					elseif ($colVal === true)
					{
						$sql .= ",`$colName`=?";
                        $preparedColumns[count($preparedColumns)]=1;
					}
					elseif ($colVal === false)
					{
						$sql .= ",`$colName`=?";
                        $preparedColumns[count($preparedColumns)]=0;
					}
					else
					{
						$sql .= ",`$colName`=?";
                        $preparedColumns[count($preparedColumns)]=$colVal;
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
					$sql .= "`$colName`=?";
                    $preparedColumns[count($preparedColumns)]=null;
				}
				elseif ($colVal === true)
				{
					$sql .= "`$colName`=?";
                    $preparedColumns[count($preparedColumns)]=1;
				}
				elseif ($colVal === false)
				{
					$sql .= "`$colName`=?";
                    $preparedColumns[count($preparedColumns)]=0;
				}
				else
				{
					$sql .= "`$colName`=?";
                    $preparedColumns[count($preparedColumns)]=$colVal;
				}
			}
		}
		try
		{
			if ($this->Execute($sql,"",$preparedColumns))
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
			throw new DatabaseUpdateException("Mysql upsert failed with the following error: " . $e->getMessage(), E_WARNING, $e);
			return false;
		}
	}

    private $_tablesCache = [];

    /**
     * Check if the specified table exists.
     *
     * @param string $tableName The name of the table to inquire about.
     * @return boolean <i>True</i> if the table exists, otherwise <i>false</i>.
     */
	public function DoesTableExist($tableName)
	{
        if (!isset($this->_tablesCache[$tableName]))
        {
            $rows = $this->GetRowsAsArray("show tables like '$tableName'");
            $this->_tablesCache[$tableName] = (count($rows) > 0);
        }
		return $this->_tablesCache[$tableName] ;
	}

    /**
     * Save a row of data to the specified database table using an insert or an update statement.
     *
     * @param string $storageLocation The name of the table to store the data in.
     * @param string[] $fieldNames The names of the columns to populate when inserting/updating the row.
     * @param string[] $fieldValues The values to populate the columns specified by <i>$columnNames</i> with.
     * @param string $keyFieldName The name of the column to be used as a key when identifying
     * if a row already exists or not.
     * @param boolean $keyValueIsAutoGenerated
     * <i>True</i> if the key in the destination table is auto-generated by the engine,
     * otherwise <i>false</i>.
     * Some engines need this information to detect when an insert is a duplicate and should be an update instead.
     *
     * @return $boolean <i>True</i> on success, otherwise <i>false</i>.
     */
	public function StoreData($storageLocation, array $fieldNames, array $fieldValues, $keyFieldName, $keyValueIsAutoGenerated = false)
	{
		return $this->UpsertRow($storageLocation, $fieldNames, $fieldValues, $keyFieldName, $keyValueIsAutoGenerated);
	}

    /**
     * Get the key of the last inserted row.
     *
     * @return mixed
     */
	public function GetLastCreatedDataKey()
	{
		return $this->GetLastInsertId();
	}

	/**
	 * Get the first row that matches the specified criteria.
     *
	 * @param string $storageLocation The name of the table to get the data from.
     * @param string $keyFieldName The name of the field used to lookup the query value.
	 * @param mixed $keyValue The value to look for in the <i>$keyFieldName</i> column.
	 * @return array
	 */
	public function GetData($storageLocation, $keyFieldName, $keyValue)
	{
		$sql = "select * from $storageLocation where $keyFieldName = '" . $this->EscapeString($keyValue) . "'";
		$this->_lastQuery = $sql;
		return $this->GetFirstRow($sql, null, \MYSQLI_ASSOC);
	}

    /**
     * Get the value of the column in the row with the specified key field and value.
     *
     * @param string $storageLocation The name of the table to get the data from.
     * @param string $returnFieldName The name of the column to get the data from.
     * @param string $keyFieldName The name of the field used to lookup the query value.
     * @param string $keyValue The value that the <i>$keyFieldName</i> column must be for this row to match.
     *
     * If more than one rows match, only the value of the first one will be returned.
     *
     * @return string|int|float The value of the <i>$returnFieldName</i> column in the matching row.
     */
	public function GetScalarData($storageLocation, $returnFieldName, $keyFieldName, $keyValue)
	{
		if ((!static::$_designateViewsByStringPrefix && $this->TableExists($storageLocation)) || (static::$_designateViewsByStringPrefix && (substr($storageLocation, 0, strlen(static::$_designateViewsByStringPrefix)) != static::$_designateViewsByStringPrefix)))
		{
			if (is_array($keyFieldName))
			{
				$sql		 = "select $returnFieldName from $storageLocation where ";
				$keyFieldIdx = 0;
				foreach ($keyFieldName as $keyFieldNameItem)
				{
					$sql .= "$keyFieldNameItem = '" . $this->EscapeString($keyValue[$keyFieldIdx]) . "' ";
					if ($keyFieldIdx < count($keyFieldNameItem))
					{
						$sql .= " and ";
					}
					$keyFieldIdx++;
				}
			}
			else
			{
				$sql = "select $returnFieldName from $storageLocation where $keyFieldName = '" . $this->EscapeString($keyValue) . "'";
			}
			$this->_lastQuery = $sql;
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


    /**
     * Get the number of rows there are based on a where clause and other optional arguments.
     *
     * @param string $storageLocation
     * The name of the table to return rows from.
     *
     * @param string $filter
     * The where clause used to find the rows.
     *
     * @param string $groupingField
     * The column to group on.
     *
     * @param array $filterJoinLocations
     * An array of columns to inner-join on the table as an added filter constraint.
     * This will not return the data from the joined tables.
     * The array should be associative where the key is the name of the table to join on
     * and the value is the name of a column that is <b>mutual</b> between the two tables
     * and that will be the column that is joined on.
     * If there is not a mutual column between the tables, then the value should be null.
     * In that case, you will need to add an equality condition to the
     * <i>$filter</i> for the columns you wish to join.
     *
     * @return int The nuumber of rows that meet the specified criteria.
     */
    public function GetDataGroupCount($storageLocation, $filter = null, $groupingField = null, $filterJoinLocations = null)
	{
		if ($filterJoinLocations && is_array($filterJoinLocations))
		{
			if ($groupingField)
			{
				if (strstr($groupingField, "."))
				{
					$sql = "select count($groupingField) as Count from `$storageLocation`";
				}
				else
				{
					$sql = "select count(`$storageLocation`.$groupingField) as Count from `$storageLocation`";
				}
			}
			else
			{
				$sql = "select count(*) from `$storageLocation`";
			}
			foreach ($filterJoinLocations as $filterJoinLocationName => $filterJoinLocationMatchColumn)
			{
                $filterJoinLocationNameParts = explode(" ", $filterJoinLocationName);
                if (count($filterJoinLocationNameParts) > 1)
                {
                    $sql .= ", `".$filterJoinLocationNameParts[0]."` ".$filterJoinLocationNameParts[1];
                } else {
                    $sql .= ", `".$filterJoinLocationNameParts[0]."`";
                }
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
                    $filterJoinLocationNameParts = explode(" ", $filterJoinLocationName);
                    if (count($filterJoinLocationNameParts) > 1)
                    {
                        $joinAlias = $filterJoinLocationNameParts[1];
                    } else {
                        $joinAlias = $filterJoinLocationNameParts[0];
                    }

					if (is_array($filterJoinLocationMatchColumn))
					{
						$sql .= " and `$joinAlias`.`" . $filterJoinLocationMatchColumn[0] . "` =
                                      `$storageLocation`.`" . $filterJoinLocationMatchColumn[1] . "`";
					}
					else
					{
						$sql .= " and `$joinAlias`.`$filterJoinLocationMatchColumn` =
                                      `$storageLocation`.`$filterJoinLocationMatchColumn`";
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
					$sql .= " group by `$storageLocation`.$groupingField";
				}
			}
		}
		else
		{
			$sql = "select count(*) as Count from `$storageLocation`";
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

    /**
     * Of a set of rows based on a where clause and other optional arguments,
     * get the sum of all values for the specified column.
     *
     * @param string $storageLocation
     * The name of the table to return rows from.
     *
     * @param string $columnName
     * The name of the column to get the sum of all values for.
     *
     * @param string $filter
     * The where clause used to find the rows.
     *
     * @param string $groupingField
     * The column to group on.
     *
     * @param array $filterJoinLocations
     * An array of columns to inner-join on the table as an added filter constraint.
     * This will not return the data from the joined tables.
     * The array should be associative where the key is the name of the table to join on
     * and the value is the name of a column that is <b>mutual</b> between the two tables
     * and that will be the column that is joined on.
     * If there is not a mutual column between the tables, then the value should be null.
     * In that case, you will need to add an equality condition to the
     * <i>$filter</i> for the columns you wish to join.
     *
     * @return int|float The sum of all values for the specified column.
     */
    public function GetDataGroupSum($storageLocation, $columnName, $filter = null, $groupingField = null, $filterJoinLocations = null)
	{
		if ($filterJoinLocations && is_array($filterJoinLocations))
		{
			$sql = "select sum($columnName) from `$storageLocation`";
			foreach ($filterJoinLocations as $filterJoinLocationName => $filterJoinLocationMatchColumn)
			{
                $filterJoinLocationNameParts = explode(" ", $filterJoinLocationName);
                if (count($filterJoinLocationNameParts) > 1)
                {
                    $sql .= ", `".$filterJoinLocationNameParts[0]."` ".$filterJoinLocationNameParts[1];
                } else {
                    $sql .= ", `".$filterJoinLocationNameParts[0]."`";
                }
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
                    $filterJoinLocationNameParts = explode(" ", $filterJoinLocationName);
                    if (count($filterJoinLocationNameParts) > 1)
                    {
                        $joinAlias = $filterJoinLocationNameParts[1];
                    } else {
                        $joinAlias = $filterJoinLocationNameParts[0];
                    }

					if (is_array($filterJoinLocationMatchColumn))
					{
						$sql .= " and `$joinAlias`.`" . $filterJoinLocationMatchColumn[0] . "` =
                                      `$storageLocation`.`" . $filterJoinLocationMatchColumn[1] . "`";
					}
					else
					{
						$sql .= " and `$joinAlias`.`$filterJoinLocationMatchColumn` =
                                      `$storageLocation`.`$filterJoinLocationMatchColumn`";
					}
				}
			}
		}
		else
		{
			$sql = "select sum($columnName) from `$storageLocation`";
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
				$sql .= " group by `$storageLocation`.$groupingField";
			}
		}
		$resultVal = $this->GetScalar($sql);
        if (!$resultVal)
        {
            $resultVal=0;
        }
        return $resultVal;
	}

    /**
     * Of a set of rows based on a where clause and other optional arguments,
     * get the average value for the specified column.
     *
     * @param string $storageLocation
     * The name of the table to return rows from.
     *
     * @param string $columnName
     * The name of the column to get the average value for.
     *
     * @param string $filter
     * The where clause used to find the rows.
     *
     * @param string $groupingField
     * The column to group on.
     *
     * @param array $filterJoinLocations
     * An array of columns to inner-join on the table as an added filter constraint.
     * This will not return the data from the joined tables.
     * The array should be associative where the key is the name of the table to join on
     * and the value is the name of a column that is <b>mutual</b> between the two tables
     * and that will be the column that is joined on.
     * If there is not a mutual column between the tables, then the value should be null.
     * In that case, you will need to add an equality condition to the
     * <i>$filter</i> for the columns you wish to join.
     *
     * @return int|float The average value for the specified column.
     */
	public function GetDataGroupAvg($storageLocation, $columnName, $filter = null, $groupingField = null, $filterJoinLocations = null)
	{
		if ($filterJoinLocations && is_array($filterJoinLocations))
		{
			$sql = "select avg($columnName) from `$storageLocation`";
			foreach ($filterJoinLocations as $filterJoinLocationName => $filterJoinLocationMatchColumn)
			{
                $filterJoinLocationNameParts = explode(" ", $filterJoinLocationName);
                if (count($filterJoinLocationNameParts) > 1)
                {
                    $sql .= ", `".$filterJoinLocationNameParts[0]."` ".$filterJoinLocationNameParts[1];
                } else {
                    $sql .= ", `".$filterJoinLocationNameParts[0]."`";
                }
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
                    $filterJoinLocationNameParts = explode(" ", $filterJoinLocationName);
                    if (count($filterJoinLocationNameParts) > 1)
                    {
                        $joinAlias = $filterJoinLocationNameParts[1];
                    } else {
                        $joinAlias = $filterJoinLocationNameParts[0];
                    }
					if (is_array($filterJoinLocationMatchColumn))
					{
						$sql .= " and `$joinAlias`.`" . $filterJoinLocationMatchColumn[0] . "` =
                                      `$storageLocation`.`" . $filterJoinLocationMatchColumn[1] . "`";
					}
					else
					{
						$sql .= " and `$joinAlias`.`$filterJoinLocationMatchColumn` =
                                      `$storageLocation`.`$filterJoinLocationMatchColumn`";
					}
				}
			}
		}
		else
		{
			$sql = "select avg($columnName) from `$storageLocation`";
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
				$sql .= " group by `$storageLocation`.$groupingField";
			}
		}
		return $this->GetScalar($sql);
	}

    /**
     * Of a set of rows based on a where clause and other optional arguments,
     * get the minimum value for the specified column.
     *
     * @param string $storageLocation
     * The name of the table to return rows from.
     *
     * @param string $columnName
     * The name of the column to get the minimum value for.
     *
     * @param string $filter
     * The where clause used to find the rows.
     *
     * @param string $groupingField
     * The column to group on.
     *
     * @param array $filterJoinLocations
     * An array of columns to inner-join on the table as an added filter constraint.
     * This will not return the data from the joined tables.
     * The array should be associative where the key is the name of the table to join on
     * and the value is the name of a column that is <b>mutual</b> between the two tables
     * and that will be the column that is joined on.
     * If there is not a mutual column between the tables, then the value should be null.
     * In that case, you will need to add an equality condition to the
     * <i>$filter</i> for the columns you wish to join.
     *
     * @return int|float The minimum value for the specified column.
     */
	public function GetDataGroupMin($storageLocation, $columnName, $filter = null, $groupingField = null, $filterJoinLocations = null)
	{
		if ($filterJoinLocations && is_array($filterJoinLocations))
		{
			$sql = "select min($columnName) from `$storageLocation`";
			foreach ($filterJoinLocations as $filterJoinLocationName => $filterJoinLocationMatchColumn)
			{
                $filterJoinLocationNameParts = explode(" ", $filterJoinLocationName);
                if (count($filterJoinLocationNameParts) > 1)
                {
                    $sql .= ", `".$filterJoinLocationNameParts[0]."` ".$filterJoinLocationNameParts[1];
                } else {
                    $sql .= ", `".$filterJoinLocationNameParts[0]."`";
                }
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
                    $filterJoinLocationNameParts = explode(" ", $filterJoinLocationName);
                    if (count($filterJoinLocationNameParts) > 1)
                    {
                        $joinAlias = $filterJoinLocationNameParts[1];
                    } else {
                        $joinAlias = $filterJoinLocationNameParts[0];
                    }
					if (is_array($filterJoinLocationMatchColumn))
					{
						$sql .= " and `$joinAlias`.`" . $filterJoinLocationMatchColumn[0] . "` =
                                      `$storageLocation`.`" . $filterJoinLocationMatchColumn[1] . "`";
					}
					else
					{
						$sql .= " and `$joinAlias`.`$filterJoinLocationMatchColumn` =
                                      `$storageLocation`.`$filterJoinLocationMatchColumn`";
					}
				}
			}
		}
		else
		{
			$sql = "select min($columnName) from `$storageLocation`";
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
				$sql .= " group by `$storageLocation`.$groupingField";
			}
		}
		return $this->GetScalar($sql);
	}

    /**
     * Of a set of rows based on a where clause and other optional arguments,
     * get the maximum value for the specified column.
     *
     * @param string $storageLocation
     * The name of the table to return rows from.
     *
     * @param string $columnName
     * The name of the column to get the maximum value for.
     *
     * @param string $filter
     * The where clause used to find the rows.
     *
     * @param string $groupingField
     * The column to group on.
     *
     * @param array $filterJoinLocations
     * An array of columns to inner-join on the table as an added filter constraint.
     * This will not return the data from the joined tables.
     * The array should be associative where the key is the name of the table to join on
     * and the value is the name of a column that is <b>mutual</b> between the two tables
     * and that will be the column that is joined on.
     * If there is not a mutual column between the tables, then the value should be null.
     * In that case, you will need to add an equality condition to the
     * <i>$filter</i> for the columns you wish to join.
     *
     * @return int|float The maximum value for the specified column.
     */
	public function GetDataGroupMax($storageLocation, $columnName, $filter = null, $groupingField = null, $filterJoinLocations = null)
	{
		if ($filterJoinLocations && is_array($filterJoinLocations))
		{
			$sql = "select max($columnName) from `$storageLocation`";
			foreach ($filterJoinLocations as $filterJoinLocationName => $filterJoinLocationMatchColumn)
			{
                $filterJoinLocationNameParts = explode(" ", $filterJoinLocationName);
                if (count($filterJoinLocationNameParts) > 1)
                {
                    $sql .= ", `".$filterJoinLocationNameParts[0]."` ".$filterJoinLocationNameParts[1];
                } else {
                    $sql .= ", `".$filterJoinLocationNameParts[0]."`";
                }
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
                    $filterJoinLocationNameParts = explode(" ", $filterJoinLocationName);
                    if (count($filterJoinLocationNameParts) > 1)
                    {
                        $joinAlias = $filterJoinLocationNameParts[1];
                    } else {
                        $joinAlias = $filterJoinLocationNameParts[0];
                    }
					if (is_array($filterJoinLocationMatchColumn))
					{
						$sql .= " and `$joinAlias`.`" . $filterJoinLocationMatchColumn[0] . "` =
                                      `$storageLocation`.`" . $filterJoinLocationMatchColumn[1] . "`";
					}
					else
					{
						$sql .= " and `$joinAlias`.`$filterJoinLocationMatchColumn` =
                                      `$storageLocation`.`$filterJoinLocationMatchColumn`";
					}
				}
			}
		}
		else
		{
			$sql = "select max($columnName) from `$storageLocation`";
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
				$sql .= " group by `$storageLocation`.$groupingField";
			}
		}
		return $this->GetScalar($sql);
	}

    /**
     * Check if the specified table exists in the current catalog.
     *
     * @param string $tableName The table to check for.
     * @return boolean <i>True</i> if the table is found, otherwise false.
     */
	private function TableExists($tableName)
	{
		$isTableName = false;
        if (!self::$_tables || !count(self::$_tables) || !isset(self::$_tables[$this->_dbCatalog]))
        {
            $this->UpdateStorageLocations();
        }
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

    /**
     * Get one or more rows from a table based on a where clause and other optional arguments.
     *
     * @param string $storageLocation
     * The name of the table to return rows from.
     *
     * @param string $filter
     * The where clause used to find the rows.
     *
     * @param string $orderByFieldName
     * An <i>order by</i> clause (do not include the words 'order by').
     *
     * @param int $maxRecordCount
     * The maximum number of rows to return.
     *
     * @param string $groupingField
     * The column to group on.
     *
     * @param array $filterJoinLocations
     * An array of columns to inner-join on the table as an added filter constraint.
     * This will not return the data from the joined tables.
     * The array should be associative where the key is the name of the table to join on
     * and the value is the name of a column that is <b>mutual</b> between the two tables
     * and that will be the column that is joined on.
     * If there is not a mutual column between the tables, then the value should be null.
     * In that case, you will need to add an equality condition to the
     * <i>$filter</i> for the columns you wish to join.
     *
     * @param int $startOffset
     * Of the rows returned, the first <i>$startOffset</i> of them will be ignored.
     *
     * @return array
     * An array of associative arrays containing the row values.
     */
	public function GetDataGroup($storageLocation, $filter = null, $orderByFieldName = null, $maxRecordCount = null,
	$groupingField = null, $filterJoinLocations = null, $startOffset = 0)
	{
		$isTableName = ((!static::$_designateViewsByStringPrefix && $this->TableExists($storageLocation)) || (static::$_designateViewsByStringPrefix && (substr($storageLocation, 0, strlen(static::$_designateViewsByStringPrefix)) != static::$_designateViewsByStringPrefix)));
		if ($isTableName)
		{
			if ($filterJoinLocations && is_array($filterJoinLocations))
			{
				$sql = "select `$storageLocation`.* from `$storageLocation`";

				foreach ($filterJoinLocations as $filterJoinLocationName => $filterJoinLocationMatchColumn)
				{
                    $filterJoinLocationNameParts = explode(" ", $filterJoinLocationName);
                    if (count($filterJoinLocationNameParts) > 1)
                    {
                        $sql .= ", `".$filterJoinLocationNameParts[0]."` ".$filterJoinLocationNameParts[1];
                    } else {
                        $sql .= ", `".$filterJoinLocationNameParts[0]."`";
                    }

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
                        $filterJoinLocationNameParts = explode(" ", $filterJoinLocationName);
                        if (count($filterJoinLocationNameParts) > 1)
                        {
                            $joinAlias = $filterJoinLocationNameParts[1];
                        } else {
                            $joinAlias = $filterJoinLocationNameParts[0];
                        }
						if (is_array($filterJoinLocationMatchColumn))
						{
							$sql .= " and `$joinAlias`.`" . $filterJoinLocationMatchColumn[0] . "` =
                                          `$storageLocation`.`" . $filterJoinLocationMatchColumn[1] . "`";
						}
						else
						{
							$sql .= " and `$joinAlias`.`$filterJoinLocationMatchColumn` =
                                          `$storageLocation`.`$filterJoinLocationMatchColumn`";
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
						$sql .= " group by `$storageLocation`.$groupingField";
					}
				}
				if ($orderByFieldName)
				{
					if (strstr($orderByFieldName, ".") === false && ($orderByFieldName != "rand()"))
					{
						$sql .= " order by `$storageLocation`.$orderByFieldName";
					}
					else
					{
						$sql .= " order by $orderByFieldName";
					}
				}
			}
			else
			{
				$sql = "select * from `$storageLocation`";
				if ($filter)
				{
					$sql .= " where $filter";
				}
				if ($groupingField)
				{
					$sql .= " group by `$storageLocation`.$groupingField";
				}
				if ($orderByFieldName)
				{
					$sql .= " order by $orderByFieldName";
				}
			}
			if ($maxRecordCount)
			{
				$sql.= " limit $startOffset,$maxRecordCount";
			}
			$this->_lastQuery = $sql;
			return $this->GetRowsAsArray($sql);
		}
		else
		{
			//its not the name of a table so lets try it as a stored proc
			return $this->GetRowsSpAsArray($storageLocation, $filter);
		}
	}

    /**
     * Delete one or more rows from a table based on a where clause.
     *
     * @param string $storageLocation The table to delete rows from.
     * @param string $filter The <i>where</i> clause (do not include the word 'where').
     * @return boolean <i>True</i> on success, otherwise <i>false</i>.
     */
	public function DeleteDataGroup($storageLocation, $filter = null)
	{
		$sql = "delete from $storageLocation";
		if ($filter)
		{
			$sql .= " where $filter";
		}
		return $this->Execute($sql);
	}

	/**
     * Delete data from the specified storage location.
     *
	 * @param string $storageLocation The name of the storage location to delete data from.
	 * @param string $keyFieldName The name of the field that will be evaluated to see if a row should be deleted.
	 * @param string $keyValue The value that the key field must match in order for the row to be deleted.
     *
     * @return boolean <i>True</i> on success, otherwise <i>false</i>.
	 */
	public function DeleteData($storageLocation, $keyFieldName, $keyValue)
	{
		$sql = "delete from $storageLocation where $keyFieldName = '" . $this->EscapeString($keyValue) . "'";
		return $this->Execute($sql);
	}

	/**
     * Determines if the connection to th mySql server is ready to handle queries and/or commands.
     *
	 * @return boolean <i>True</i> if the connection is ready, otherwise </i>false</i>.
	 */
	function IsReady()
	{
		return $this->IsConnected();
	}

    private $_dataFieldCache = [];

	/**
     * Get the columns available on the specified table.
     *
	 * @param string $storageLocation The table to get the columns for.
     *
	 * @return \DblEj\Data\Field[] The columns on the specified storage location.
	 */
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
        if (!isset($this->_dataFieldCache[$schemaName]))
        {
            $this->_dataFieldCache[$schemaName] = [];
        }
        if (!isset($this->_dataFieldCache[$schemaName][$tableName]))
        {
            $sql		 = "select COLUMN_TYPE, DATA_TYPE, NUMERIC_PRECISION, NUMERIC_SCALE, COLUMN_NAME, IS_NULLABLE, COLUMN_KEY, COLUMN_COMMENT, COLUMN_DEFAULT from information_schema.columns where table_schema = '$schemaName' and table_name='$tableName' order by ORDINAL_POSITION";
            $data		 = $this->GetRowsAsArray($sql);
            $returnCols	 = new FieldCollection();
            foreach ($data as $row)
            {
                $returnCols->AddField(self::_createFieldFromInformationSchemaRow($row));
            }
            $this->_dataFieldCache[$schemaName][$tableName] = $returnCols;
        }
		return $this->_dataFieldCache[$schemaName][$tableName];
	}

    /**
     * Get relationships where the specifed table has a column that is constrained by a column in a foreign table.
     *
     * @param string $storageLocation The table that has columns that are a constrained by columns in foreign tables.
     */
	public function GetParentReferences($storageLocation)
	{
		$returnData = array();
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
		$sql	 = "select b.TABLE_NAME, b.COLUMN_NAME, b.REFERENCED_TABLE_NAME, b.REFERENCED_COLUMN_NAME from
		information_schema.TABLE_CONSTRAINTS a,
		information_schema.KEY_COLUMN_USAGE b
		where
		a.table_schema = '$schemaName' and a.table_name='$tableName'
		and
		b.table_schema = '$schemaName' and b.table_name='$tableName'
		and a.CONSTRAINT_NAME = b.CONSTRAINT_NAME
		and a.CONSTRAINT_TYPE = 'FOREIGN KEY'";
		$data	 = $this->GetRowsAsArray($sql);
		foreach ($data as $row)
		{
			$returnData[] = new ForeignKey($tableName, $row["COLUMN_NAME"], $row["REFERENCED_TABLE_NAME"],
											  $row["REFERENCED_COLUMN_NAME"]);
		}
		return $returnData;
	}

    /**
     * Get relationships where the specifed table has a column that acts as a foreign constraint
     * on another table's column.
     *
     * @param string $storageLocation The table that has columns that are a foreign constraint for columns in other tables.
     */
	public function GetChildReferences($storageLocation)
	{
		$returnData = array();
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
		$sql	 = "select b.TABLE_NAME, b.COLUMN_NAME, b.REFERENCED_TABLE_NAME, b.REFERENCED_COLUMN_NAME from
		information_schema.TABLE_CONSTRAINTS a,
		information_schema.KEY_COLUMN_USAGE b
		where
		a.TABLE_SCHEMA = '$schemaName'
		and
		b.TABLE_SCHEMA = '$schemaName' and b.referenced_table_name='$tableName'
		and a.CONSTRAINT_NAME = b.CONSTRAINT_NAME
		and a.CONSTRAINT_TYPE = 'FOREIGN KEY'";
		$rows	 = $this->GetRowsAsArray($sql);

		foreach ($rows as $row)
		{
			$returnData[] = new ForeignKey($row["TABLE_NAME"], $row["COLUMN_NAME"], $tableName, $row["REFERENCED_COLUMN_NAME"]);
		}
		return $returnData;
	}

    /**
     * Get relationships where the specifed table acts as a cross-reference
     * table between two other tables.
     *
     * @param string $tablename The tablethat is a cross-reference table between two other tables.
     * @return ForeignKey[] An array of ForeignKey objects that define the relationships between the tables.
     */
	public function GetCrossReferences($tablename,$tableToClassMappings=null)
	{
		$parents	 = $this->GetParentReferences($tablename,$tableToClassMappings);
		$children	 = $this->GetChildReferences($tablename,$tableToClassMappings);
		$crossRef	 = array_intersect($parents, $children);
		return $crossRef;
	}

    private $_storageLocationCache = [];

	/**
     * Get meta information about all of the tables in the current catalog.
     *
	 * @param string $filter This parameter is ignored.
     *
	 * @return array An array of information about the storage locations.
	 */
	public function GetStorageLocations($filter = "")
	{
        if (!isset($this->_storageLocationCache[$this->_dbCatalog]))
        {
            $this->_storageLocationCache[$this->_dbCatalog] = [];
            $sql		 = "select table_name,table_type,auto_increment from information_schema.tables where TABLE_SCHEMA='$this->_dbCatalog'";
            $data		 = $this->GetRowsAsArray($sql);
            $returnCols	 = array();
            foreach ($data as $row)
            {
                $returnCols[$row["table_name"]] = array();
                if (strtoupper($row["table_type"]) == "VIEW")
                {
                    $returnCols[$row["table_name"]]["ReadOnly"] = true;
                }
                else
                {
                    $returnCols[$row["table_name"]]["ReadOnly"] = false;
                }
                $returnCols[$row["table_name"]]["KeyValueIsGeneratedByEngine"] = ($row["auto_increment"] !== null ? true : false);
            }
            $this->_storageLocationCache[$this->_dbCatalog] = $returnCols;
        }
		return $this->_storageLocationCache[$this->_dbCatalog];
	}

    /**
     * Check if the specified table exists in the current catalog.
     *
     * @param string $storageLocation The name of the table to check for.
     */
	public function DoesLocationExist($storageLocation)
	{
		return $this->DoesTableExist($storageLocation);
	}

    /**
     * Get the types of events that this EventRaiser raises.
     *
     * @return \DblEj\EventHandling\EventTypeCollection
     */
	public function GetRaisedEventTypes()
	{
		return new EventTypeCollection(array(
			ScriptExecutedEvent::DATA_SCRIPT_EXECUTED => ScriptExecutedEvent::DATA_SCRIPT_EXECUTED));
	}

    /**
     * The name of a logical grouping that can be used by data models or other entities dependant
     * on storage locations in certain storage engines to identify the correct storage engine to use
     * for persistence or other functionality it is dependant on.
     *
     * @return string $storageGroup The name of the group.
     */
	public function Get_ModelGroup()
	{
		return $this->_modelGroup;
	}

    /**
     * The name of a logical grouping that can be used by data models or other entities dependant
     * on storage locations in certain storage engines to identify the correct storage engine to use
     * for persistence or other functionality it is dependant on.
     *
     * @param string $storageGroup The name of the group.
     */
	public function Set_ModelGroup($storageGroup)
	{
		$this->_modelGroup = $storageGroup;
	}

	private static function _createFieldFromInformationSchemaRow($informationSchemaRow)
	{
		$dataType	 = Field::DATA_TYPE_STRING;
		$decimalSize = 0;
		$dataSize	 = 0;

		$columnDeclaration = $informationSchemaRow["COLUMN_TYPE"];
		if (strpos("(", $columnDeclaration) !== false)
		{
			$columnSizeDeclaration		 = substr($columnDeclaration, strpos("(", $columnDeclaration),
																strrpos(")", $columnDeclaration) - strpos("(", $columnDeclaration));
			$columnSizeDeclarationArray	 = explode(",", $columnSizeDeclaration);
			$dataSize					 = $columnSizeDeclarationArray[0];
			if (count($columnSizeDeclarationArray) > 1)
			{
				$decimalSize = $columnSizeDeclarationArray[1];
			}
		}
		$isUnsigned = false;
		switch ($informationSchemaRow["DATA_TYPE"])
		{
			case "serial":
			case "int":
			case "integer":
			case "smallint":
			case "mediumint":
			case "bigint":
                $decimalSize = 0;
				if (!$dataSize)
				{
					$dataSize = $informationSchemaRow["NUMERIC_PRECISION"];
				}
				if (stripos($columnDeclaration,"tinyint(1)") > -1)
				{
					$dataType = Field::DATA_TYPE_BOOL;
                    $dataSize	 = 1;
				}
				else
				{
					$dataType = Field::DATA_TYPE_INT;
					$isUnsigned = stripos($columnDeclaration,"unsigned") > -1;
				}
				break;
			case "bit":
                $dataType       = Field::DATA_TYPE_BOOL;
                $dataSize       = 1;
                $isUnsigned     = true;
                break;
			case "tinyint":
                $decimalSize = 0;
				if (!$dataSize)
				{
					$dataSize = $informationSchemaRow["NUMERIC_PRECISION"];
				}
				if (stripos($columnDeclaration,"tinyint(1)") > -1)
				{
					$dataType = Field::DATA_TYPE_BOOL;
                    $dataSize	 = 1;
				}
				else
				{
					$dataType = Field::DATA_TYPE_INT;
					$isUnsigned = stripos($columnDeclaration,"unsigned") > -1;
				}
				break;
			case "year":
				$dataType	 = Field::DATA_TYPE_INT;
				break;
			case "float":
			case "double":
			case "decimal":
				$dataType	 = Field::DATA_TYPE_DECIMAL;
				if (!$dataSize)
				{
					$dataSize = $informationSchemaRow["NUMERIC_PRECISION"];
				}
				if (!$decimalSize)
				{
					$dataSize = $informationSchemaRow["NUMERIC_SCALE"];
				}
				break;
			case "bool":
			case "boolean":
				$dataType	 = Field::DATA_TYPE_BOOL;
				$dataSize	 = 1;
				$decimalSize = 0;
				break;
			case "date":
			case "timestamp":
				$dataType	 = Field::DATA_TYPE_DATE;
				break;
			case "time":
				$dataType	 = Field::DATA_TYPE_TIME;
				break;
			case "datetime":
				$dataType	 = Field::DATA_TYPE_DATETIME;
				break;
			case "binary":
			case "blob":
			case "longblob":
			case "mediumblob":
			case "varbinary":
			case "char byte":
				$dataType	 = Field::DATA_TYPE_BINARY;
				break;
			case "char":
			case "nchar":
			case "national char":
			case "varchar":
			case "nvarchar":
			case "national varchar":
			case "text":
				$dataType	 = Field::DATA_TYPE_STRING;
				break;
		}
		return new Field($informationSchemaRow["COLUMN_NAME"], $dataType, $dataSize,
            $informationSchemaRow["IS_NULLABLE"] == "YES", $informationSchemaRow["COLUMN_DEFAULT"],
            $informationSchemaRow["COLUMN_KEY"] == "PRI", $decimalSize, $isUnsigned, $informationSchemaRow["COLUMN_COMMENT"],
            $informationSchemaRow["COLUMN_KEY"] != "", $informationSchemaRow["COLUMN_KEY"] == "UNI" || $informationSchemaRow["COLUMN_KEY"] == "PRI");
	}

    /**
     * The last query sent to this storage engine.
     *
     * @return string  The query.
     */
    public function GetLastQuery()
	{
		return $this->_lastQuery;
	}

    public function Read($dataLookup, $dataLookupArguments = null, $dataLocation = null)
    {
        return $this->GetData($dataLocation, $dataLookup, $dataLookupArguments);
    }
    public function Write($destination, $dataName, $dataValue)
    {
        return $this->InsertRow($destination, $dataName, $dataValue);
    }
    public function GetLastWriteId()
    {
        return $this->GetLastInsertId();
    }
    public function __toString()
    {
        return "MySql Database Connection ($this->_dbCatalog)";
    }
}
?>