<?php
namespace Wafl\Extensions\Search\Solr;

class Index implements \DblEj\Data\IIndex
{
	private $_client;
	private $_indexname;
    private $_searchServlet;
    private $_serviceUrl;
    private $_loginId;
    private $_password;

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
        $this->_serviceUrl = $serviceUrl;
        $this->_loginId = $loginId;
        $this->_password = $password;
	}

    private $_connected = false;

    private function _connectOnDemand()
    {
        if (!$this->_connected)
        {
            $this->_connected = true;
            $uriParts = parse_url($this->_serviceUrl);
            if (!isset($uriParts["scheme"]))
            {
                throw new \DblEj\Communication\IncompleteUrlException("Invalid index service Url");
            }
            if (!isset($uriParts["host"]))
            {
                throw new \DblEj\Communication\Http\InvalidAbsoluteUrlException ($this->_serviceUrl,"Invalid Solr Url ($this->_serviceUrl).  Url must contain a valid Solr host when connecting to a Solr index");
            }

            $port = isset($uriParts["port"])?$uriParts["port"]:8983;
            $this->_client = new \SolrClient(array(
                                'hostname'     => $uriParts["host"],
                                'login'     => $this->_loginId,
                                'password' => $this->_password,
                                'port'	=>	$port,
                                'path'	=> $uriParts["path"]."/".$this->_indexname));
            $this->_client->setServlet(\SolrClient::SEARCH_SERVLET_TYPE, $this->_searchServlet);
        }
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
        $this->_connectOnDemand();

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
	function Search($fieldToSearchOn, $searchPhrase, $startOffset=0, $count=null, array $returnFields=null, array $sorts=null, $args=null, &$totalNumFound = null)
	{
        $this->_connectOnDemand();
		if ($returnFields == null)
		{
			$returnFields = array("*");
		}
		if ($fieldToSearchOn == null)
		{
			$fieldToSearchOn = "*";
		}
//        print_r($fieldToSearchOn);die();
        $fieldSearchGroupings = isset($args["FieldSearchGroupings"])?$args["FieldSearchGroupings"]:[];
        $fieldSearchGroupingLimits = isset($args["FieldSearchGroupLimits"])?$args["FieldSearchGroupLimits"]:[];

        if (!$fieldSearchGroupings)
        {
            $fieldSearchGroupings = [];
        }
        if (!$fieldSearchGroupingLimits)
        {
            $fieldSearchGroupingLimits = [];
        }
		$returnArray = array();
        $excProximity = null;
        $nonexcProximity = null;
        $exclusiveFieldGroups = [];
        $filterFields = [];
        if ($args && is_array($args) && isset($args["ExclusiveProximity"]))
        {
            $excProximity = $args["ExclusiveProximity"];
        }
        if ($args && is_array($args) && isset($args["NonExclusiveProximity"]))
        {
            $nonexcProximity = $args["NonExclusiveProximity"];
        }
        if ($args && is_array($args) && isset($args["ExclusiveFieldGroups"]))
        {
            $exclusiveFieldGroups = $args["ExclusiveFieldGroups"];
        }
        if ($args && is_array($args) && isset($args["FilterFields"]))
        {
            $filterFields = $args["FilterFields"];
        }

        foreach ($fieldSearchGroupings as $fieldSearchGroupingBlockIdx=>$fieldSearchGroupingGroup)
        {
            foreach ($fieldSearchGroupingGroup as $groupedFieldIdx)
            {
                $groupedFieldSearchPhrase = $searchPhrase[$groupedFieldIdx];
                $groupedFieldName = $fieldToSearchOn[$groupedFieldIdx];

                if (isset($args["ValueSubtypeDelimiters"]) && isset($args["ValueSubtypeDelimiters"][$groupedFieldName]))
                {
                    $valueSubtypeDelimiter = $args["ValueSubtypeDelimiters"][$groupedFieldName];
                    $splitPhrase = explode($valueSubtypeDelimiter, $groupedFieldSearchPhrase);
                    $subTypeId = $splitPhrase[0];
                } else {
                    $subTypeId = 0;
                }

                if (isset($filterFields[$groupedFieldName]) && isset($filterFields[$groupedFieldName][$subTypeId]))
                {
                    unset ($filterFields[$groupedFieldName][$subTypeId]);
                    if (count($filterFields[$groupedFieldName]) == 0)
                    {
                        unset($filterFields[$groupedFieldName]);
                    }
                }
            }
        }
        $queryString = "";
        $filterQueries = [];
        $facetFilterTags = [];
        if ($args && is_array($args) && isset($args["FacetField"]))
        {
            $facetFields = is_array($args["FacetField"])?$args["FacetField"]:[$args["FacetField"]];
        } else {
            $facetFields = [];
        }
		if (is_array($fieldToSearchOn))
        {
            $indexesByFieldName = [];
            $queriesByFieldName = [];
            $queriesByIndexGroup = [];

            foreach ($fieldToSearchOn as $fieldIdx=>$fieldName)
            {
                if (isset($args["ValueSubtypeDelimiters"]) && isset($args["ValueSubtypeDelimiters"][$fieldName]))
                {
                    $valueSubtypeDelimiter = $args["ValueSubtypeDelimiters"][$fieldName];
                    $splitPhrase = explode($valueSubtypeDelimiter, $searchPhrase[$fieldIdx]);
                    $subTypeId = $splitPhrase[0];
                } else {
                    $subTypeId = 0;
                }
//                print_r($filterFields);
//                if (array_search($searchPhrase[$fieldIdx], $filterFields[$fieldName][$subTypeId]) === false)
//                {
//                    die("dijidjid");
//                }
//                print "sp: ".$searchPhrase[$fieldIdx];
                //die($searchPhrase[$fieldIdx]);
                if ((key_exists($fieldName, $filterFields) === false) || ($subTypeId !== null && (key_exists($subTypeId, $filterFields[$fieldName]) === false)) || ($subTypeId !== null && (array_search($searchPhrase[$fieldIdx], $filterFields[$fieldName][$subTypeId]) === false)))
                {
                    //die("asdfas");
                    if (!isset($indexesByFieldName[$fieldName]))
                    {
                        $indexesByFieldName[$fieldName] = [];
                        $queriesByFieldName[$fieldName] = "";
                    }
                    $indexesByFieldName[$fieldName][] = $fieldIdx;
                }
            }
            foreach ($indexesByFieldName as $fieldName => $fieldIndexes)
            {
                if (isset($exclusiveFieldGroups[$fieldName]))
                {
                    $groupQuery = "";
                    foreach ($exclusiveFieldGroups[$fieldName] as $groupedExclusiveFields)
                    {
                        $innerGroupQuery = "";
                        foreach ($groupedExclusiveFields as $groupSearchString)
                        {
                            if ($innerGroupQuery)
                            {
                                $innerGroupQuery .= " OR ";
                            }

                            if (stripos($groupSearchString, "[") === false)
                            {
                                if ($excProximity)
                                {
                                    $innerGroupQuery .= "$fieldName:(".  str_replace("\"", "\\\"", $groupSearchString).")~$excProximity\r\n";
                                } else {
                                    $innerGroupQuery .= "$fieldName:(".(str_replace("\"", "\\\"", $groupSearchString)).")\r\n";
                                }
                            } else {
                                if ($excProximity)
                                {
                                    $innerGroupQuery .= "$fieldName:(".  str_replace("\"", "\\\"", $groupSearchString)."\")~$excProximity\r\n";
                                } else {
                                    $innerGroupQuery .= "$fieldName:(".(str_replace("\"", "\\\"", $groupSearchString)).")\r\n";
                                }
                            }
                        }
                        if ($innerGroupQuery)
                        {
                            if ($groupQuery)
                            {
                                $groupQuery .= " AND ";
                            }
                            $groupQuery .= "($innerGroupQuery)";
                        }
                    }
                    if ($groupQuery)
                    {
                        $queriesByFieldName[$fieldName] .= "($groupQuery)";
                    }
                } else {
                    foreach ($fieldIndexes as $fieldIdx)
                    {
                        $indexIsGrouped = false;
                        foreach ($fieldSearchGroupings as $groupIndexes)
                        {
                            if (array_search($fieldIdx, $groupIndexes) !== false)
                            {
                                $indexIsGrouped = true;
                                break;
                            }
                        }
                        if (!$indexIsGrouped)
                        {
                            $searchString = $searchPhrase[$fieldIdx];

                            $queriesByFieldName[$fieldName] = $queriesByFieldName[$fieldName] ? ($queriesByFieldName[$fieldName]." OR "):$queriesByFieldName[$fieldName];

                            if (stripos($searchString, "[") === false)
                            {
                                if ($excProximity)
                                {
                                    $queriesByFieldName[$fieldName] .= "$fieldName:(".  str_replace("\"", "\\\"", $searchString).")~$excProximity\r\n";
                                } else {
                                    $queriesByFieldName[$fieldName] .= "$fieldName:(".(str_replace("\"", "\\\"", $searchString)).")\r\n";
                                }
                            } else {
                                if ($excProximity)
                                {
                                    $queriesByFieldName[$fieldName] .= "$fieldName:(".  str_replace("\"", "\\\"", $searchString).")~$excProximity\r\n";
                                } else {
                                    $queriesByFieldName[$fieldName] .= "$fieldName:(".(str_replace("\"", "\\\"", $searchString)).")\r\n";
                                }
                            }
                        }
                    }
                }
            }

            foreach ($fieldSearchGroupings as $fieldSearchGroupIdx => $fieldSearchFieldIndexGroup)
            {
                $queriesByIndexGroup[$fieldSearchGroupIdx] = "";
                foreach ($fieldSearchFieldIndexGroup as $fieldIdx)
                {
                    $fieldName = $fieldToSearchOn[$fieldIdx];
                    $searchString = $searchPhrase[$fieldIdx];
                    if ($queriesByIndexGroup[$fieldSearchGroupIdx])
                    {
                        $queriesByIndexGroup[$fieldSearchGroupIdx] .= " AND ";
                    }
                    $queriesByIndexGroup[$fieldSearchGroupIdx] .= "$fieldName:(".(str_replace("\"", "\\\"", $searchString)).")\r\n";

                }

            }

            if ($args && is_array($args) && isset($args["ExclusiveFields"]))
            {
                $exclusiveFields = $args["ExclusiveFields"];
                $queryStringExc = "";
                $queryStringNonExc = "";

                foreach ($queriesByFieldName as $fieldName=>$query)
                {
                    if ($query)
                    {
                        if (array_search($fieldName, $exclusiveFields) !== false)
                        {
                            $queryStringExc = $queryStringExc ? $queryStringExc." AND ":$queryStringExc;
                            $queryStringExc .= "($query)";
                        } else {
                            $queryStringNonExc = $queryStringNonExc ? $queryStringNonExc." OR ":$queryStringNonExc;
                            $queryStringNonExc .= "($query)";
                        }
                    }
                }
                foreach ($queriesByIndexGroup as $queryIdx=>$query)
                {
                    if (isset($fieldSearchGroupingLimits[$queryIdx]))
                    {
                        $qtyLimitForGroup = $fieldSearchGroupingLimits[$queryIdx];
                    } else {
                        $qtyLimitForGroup = null;
                    }
                    //currently qty limit not implemented
                    $queryStringNonExc = $queryStringNonExc ? $queryStringNonExc." OR ":$queryStringNonExc;
                    $queryStringNonExc .= "($query)";
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
                foreach ($queriesByFieldName as $fieldName=>$query)
                {
                    if ($query)
                    {
                        if ($queryString)
                        {
                            $queryString .= " OR ";
                        }
                        if ($nonexcProximity)
                        {
                            $queryString .= "($query)\r\n";
                        } else {
                            $queryString .= "($query)\r\n";
                        }
                    }
                }

                foreach ($queriesByIndexGroup as $query)
                {
                    if ($query)
                    {
                        if ($queryString)
                        {
                            $queryString .= " AND ";
                        }
                        if ($nonexcProximity)
                        {
                            $queryString .= "($query)\r\n";
                        } else {
                            $queryString .= "($query)\r\n";
                        }
                    }
                }
            }
            if ($filterFields)
            {
//                print_r($fieldSearchGroupings);
//                print_r($indexesByFieldName);
//                print_r($filterFields);die();
//                unset($filterFields["AttributeValuePairs"]);

                foreach ($filterFields as $filterFieldName => $filterValues)
                {
                    foreach ($filterValues as $filterValueId => $filterSubValues)
                    {
                        if ($filterSubValues)
                        {
                            $filterTag = "f-$filterFieldName-$filterValueId";
                            if (array_search($filterFieldName, $facetFields) !== false)
                            {
                                $facetFilterTags[] = $filterTag;
                            }

                            $attSearch = "{!tag=$filterTag}$filterFieldName:(";
                            foreach ($filterSubValues as $filterValue)
                            {
                                $attSearch .= str_replace("\"", "\\\"", $filterValue) . " ";
                            }
                            $attSearch = trim($attSearch).")";
                            $filterQueries[] = $attSearch;
                        }
                    }
                }
            }
        } else {
            if (stripos($searchPhrase, "[") === false)
            {
                if ($nonexcProximity)
                {
                    $queryString = "($fieldToSearchOn:(".str_replace("\"", "\\\"", $searchPhrase).")~$nonexcProximity)\r\n";
                } else {
                    $queryString = "($fieldToSearchOn:(".(str_replace("\"", "\\\"", $searchPhrase))."))\r\n";
                }
            } else {
                if ($nonexcProximity)
                {
                    $queryString = "($fieldToSearchOn:(".str_replace("\"", "\\\"", $searchPhrase).")~$nonexcProximity)\r\n";
                } else {
                    $queryString = "($fieldToSearchOn:(".(str_replace("\"", "\\\"", $searchPhrase))."))\r\n";
                }
            }
        }

		try
		{
			$query = new \SolrDisMaxQuery();
			$query->setQuery($queryString);
            foreach ($filterQueries as $filterQuery)
            {
                $query->addFilterQuery($filterQuery);
            }
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

            if ($facetFields)
            {
                $allFilterTagString = implode(",", $facetFilterTags);
                foreach ($filterFields as $filterFieldName => $filterValues)
                {
                    if (array_search($filterFieldName, $facetFields) !== false)
                    {
                        foreach ($filterValues as $filterValueId => $filterValue)
                        {
                            $query->addFacetField("{!key=$filterFieldName-$filterValueId ex=f-$filterFieldName-$filterValueId}$filterFieldName");
                        }
                    }
                    $query->addFacetField("{!key=$filterFieldName-base ex=$allFilterTagString}$filterFieldName");
                }

                foreach ($facetFields as $facetField)
                {
                    if (!isset($filterFields[$facetField]))
                    {
                        $query->addFacetField("{!key=$facetField-base ex=$allFilterTagString}$facetField");
                    }
                    $query->addFacetField($facetField);
                }
                $query->setFacet(true);
            }
            if (isset($args["MinMatch"]))
            {
               $query->setMinimumMatch($args["MinMatch"]);
            }
            $returnRaw = false;
            if ($args && is_array($args) && isset($args["ReturnRaw"]))
            {
                $returnRaw = $args["ReturnRaw"];
            }
			$response = $this->_client->query($query);
            $response->setParseMode(\SolrQueryResponse::PARSE_SOLR_OBJ);
            $results = $response->getResponse();
            $totalNumFound = $results->response->numFound;
            if ($returnRaw)
            {
                 $returnArray = $results;
            } else {
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
            }
		} catch (\SolrClientException $ex) {
		} catch (\SolrServerException $ex) {
			throw new \Exception("Unable to search the index with the provided query ($query).  <pre><code><xmp>".$ex->getMessage()."</xmp></code></pre>",\E_ERROR,$ex);
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
        $this->_connectOnDemand();
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
        $this->_connectOnDemand();
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
        $this->_connectOnDemand();
		$response = $this->_client->ping();
		return $response->success();
	}

}
?>