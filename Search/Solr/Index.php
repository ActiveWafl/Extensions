<?php
namespace Wafl\Extensions\Search\Solr;

class Index implements \DblEj\Data\IIndex
{
	private $_client;
	private $_indexname;
    private $_searchServlet;

	public function __construct($indexname, $searchServlet="select")
	{
		$this->_indexname = $indexname;
        $this->_searchServlet = $searchServlet;
	}

    /**
     * Connect to the index.
     *
     * @param string $serviceUri The URL to the index.  This can be a file path, a host name, an ip address, or any URI that identifies the index.
     * @param type $loginId The username/login id to use to authenticate to the index.
     * @param type $password The password to use to authenticate to the index.
     */
	public function Connect($serviceUrl, $loginId=null, $password=null)
	{
		$uriParts = parse_url($serviceUrl);
		if (!isset($uriParts["scheme"]))
		{
			throw new \DblEj\Communication\IncompleteUrlException("Invalid index service Url");
		}
		if (!isset($uriParts["host"]))
		{
			throw new \DblEj\Communication\Http\InvalidAbsoluteUrlException ($serviceUrl,"Invalid Solr Url ($serviceUrl).  Url must contain a valid Solr host when connecting to a Solr index");
		}
		
		$port = isset($uriParts["port"])?$uriParts["port"]:8983;
		$this->_client = new \SolrClient(array(
							'hostname'     => $uriParts["host"],
							'login'     => $loginId,
							'password' => $password,
							'port'	=>	$port,
							'path'	=> $uriParts["path"]."/".$this->_indexname));
        $this->_client->setServlet(\SolrClient::SEARCH_SERVLET_TYPE, $this->_searchServlet);
	}

    /**
     * Add the specified document to the solr index.
     *
     * @param \DblEj\Data\IIndexable $indexableItem
     * The document to index.
     *
	 * @return boolean <i>True</i> if the index exists, otherwise <i>false</i>.
	 */
	public function Index(\DblEj\Data\IIndexable $indexableItem)
	{
		$doc = new \SolrInputDocument();

        foreach ($indexableItem->GetIndexableData() as $dataName=>$data)
        {
            if (is_array($data))
            {
                foreach ($data as $dataElem)
                {
                    @$doc->addField($dataName,$dataElem);
                }
            } else {
                @$doc->addField($dataName,$data);
            }
            $lastError = error_get_last();
            if ($lastError)
            {
                throw new \Exception("Possibe error indexing the data named $dataName: ".$lastError["message"]." in " . $lastError["file"] ." at line " . $lastError["line"],E_WARNING);
            }
        }
		$updateResponse = $this->_client->addDocument($doc,true,10000);
		return $updateResponse->success()==1;
	}

	/**
	 * Search for documents in the index that match the search phrase.
	 * @param string|array $fieldToSearchOn The name of the field to search on, or an array of the names of the fields to search on.
	 * @param string|array $searchPhrase The name of the phrase to search or,
     * if <i>$fieldToSearchOn</i> is an array, an array of phrases to search for that correspond to those fields.
	 * @param int $startOffset If the search returns multiple documents, ignore the first <i>$startOffset</i> of them.
	 * @param int $count The maximum number of documents to return.
	 * @param string[] $returnFields An array of the names of the fields to be returned.
     * @param IndexSort[] $sorts An array of IndexSort objects instructing the index on how to sort the results.
     * @param mixed $args Can be used to pass a list of exclusive fields
	 */
	function Search($fieldToSearchOn, $searchPhrase, $startOffset=0, $count=null, array $returnFields=null, array $sorts=null, $args=null)
	{
		if ($returnFields == null)
		{
			$returnFields = array("*");
		}
		if ($fieldToSearchOn == null)
		{
			$fieldToSearchOn = "*";
		}
		$returnArray = array();
        $excProximity = null;
        $nonexcProximity = null;
        if ($args && is_array($args) && isset($args["ExclusiveProximity"]))
        {
            $excProximity = $args["ExclusiveProximity"];
        }
        if ($args && is_array($args) && isset($args["NonExclusiveProximity"]))
        {
            $nonexcProximity = $args["NonExclusiveProximity"];
        }

        $queryString = "";
		if (is_array($fieldToSearchOn))
        {
            if ($args && is_array($args) && isset($args["ExclusiveFields"]))
            {
                $exclusiveFields = $args["ExclusiveFields"];
                $queryStringExc = "";
                $queryStringNonExc = "";
                foreach ($fieldToSearchOn as $fieldIdx=>$fieldName)
                {
                    $searchString = $searchPhrase[$fieldIdx];
                    if (array_search($fieldName, $exclusiveFields) !== false)
                    {
                        $queryStringExc = $queryStringExc ? $queryStringExc." AND ":$queryStringExc;
                        if ($excProximity)
                        {
                            $queryStringExc .= "($fieldName: \"".  str_replace("\"", "\\\"", $searchString)."\"~$excProximity)";
                        } else {
                            $queryStringExc .= "($fieldName: ".(str_replace("\"", "\\\"", $searchString)).")";
                        }
                    } else {
                        $queryStringNonExc = $queryStringNonExc ? $queryStringNonExc." OR ":$queryStringNonExc;
                        if ($nonexcProximity)
                        {
                            $queryStringNonExc .= "($fieldName: \"".str_replace("\"", "\\\"", $searchString)."\"~$nonexcProximity)";
                        } else {
                            $queryStringNonExc .= "($fieldName: ".(str_replace("\"", "\\\"", $searchString)).")";
                        }
                    }
                }
                if ($queryStringExc && $queryStringNonExc)
                {
                    $queryString = "($queryStringNonExc) AND ($queryStringExc)";
                }
                elseif ($queryStringExc)
                {
                    $queryString = $queryStringExc;
                }
                elseif ($queryStringNonExc)
                {
                    $queryString = $queryStringNonExc;
                }
            } else {
                foreach ($fieldToSearchOn as $fieldIdx=>$fieldName)
                {
                    $searchString = $searchPhrase[$fieldIdx];
                    if ($nonexcProximity)
                    {
                        $queryString .= "($fieldName: \"".str_replace("\"", "\\\"", $searchString)."\"~$nonexcProximity)\r\n";
                    } else {
                        $queryString .= "($fieldName: ".(str_replace("\"", "\\\"", $searchString)).")\r\n";
                    }
                }
            }
        } else {
            if ($nonexcProximity)
            {
                $queryString .= "($fieldToSearchOn: \"".str_replace("\"", "\\\"", $searchPhrase)."\"~$nonexcProximity)\r\n";
            } else {
                $queryString .= "($fieldToSearchOn: ".(str_replace("\"", "\\\"", $searchPhrase)).")\r\n";
            }
        }
		try
		{
			$query = new \SolrQuery();
			$query->setQuery($queryString);
			$query->setStart($startOffset);
			if ($count)
			{
				$query->setRows($count);
			}
			if (is_array($returnFields))
			{
				foreach ($returnFields as $fieldName)
				{
					$query->addField($fieldName);
				}
			}
			if (is_array($sorts))
			{
				foreach ($sorts as $sort)
				{
					$query->addSortField($sort->Get_FieldName(),$sort->Get_Direction()==SORT_ASC?\SolrQuery::ORDER_ASC:\SolrQuery::ORDER_DESC);
				}
			}
			$response = $this->_client->query($query);
			$response->setParseMode(\SolrQueryResponse::PARSE_SOLR_OBJ);
            $results = $response->getResponse();
			if ($results->responseHeader->status == 0)
			{
				if ($results->response->docs)
				{
					foreach ($results->response->docs as $solrDoc)
					{
						$returnArray[]=$solrDoc;
					}
				}
			}
		} catch (\SolrClientException $ex) {
			throw new \Exception("Unable to search the index with the provided query.  <pre><code><xmp>".$ex->getMessage()."</xmp></code></pre>",\E_ERROR,$ex);
		}
		return $returnArray;
	}

    /**
     * Remove items from this index based on the specified query.
     *
     * @param string $indexerQuery The criteria for which items to unindex.
     */
	public function Unindex($indexerQuery)
	{
		$success=false;
		try
		{
			$solrResponse = $this->_client->deleteByQuery($indexerQuery);
			$success = $solrResponse->success();
		} catch (\Exception $ex) {
			$success = false;
		}
		return $success;
	}

    /**
     * Remove the item matching the specified unique id from the index.
     *
     * @param string $Uid The unique id of the item to unindex.
     */
	public function UnindexByUid($Uid)
	{
		$success=false;
		try
		{
			$solrResponse = $this->_client->deleteById($Uid);
			return $solrResponse->success();
		} catch (\Exception $ex) {
			$success = false;
		}
		return $success;
	}
	
    /**
     * Allows clients to check if this index is online.
     * The implementation should return a boolean indicating if the underlying service is online.
     *
     * @return boolean <i>True</i> if the service is online, otherwise <i>false</i>.
     */
	public function Ping()
	{
		$response = $this->_client->ping();
		return $response->success();
	}

}
?>