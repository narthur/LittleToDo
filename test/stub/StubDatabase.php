<?php

namespace LittleToDo;

class StubDatabase extends Database
{
	private $calls = [];
	private $returnValues = [];
	private $consecutiveReturnValues = [];
	private $conferenceRow;
	
	private $currentQuery = [];
	private $executedQueries = [];
	private $abandonedQueries = [];
	
	private $queryReturnValues = [];
	private $consecutiveQueryReturnValues = [];
	
	public function __construct()
	{
	}
	
	// New Style API
	
	public function __call($attribute, $arguments)
	{
		$this->recordMethodCall($attribute, $arguments);
		
		$value = $arguments[0] ?? "NULL";
		$operator = $arguments[1] ?? "";
		$this->currentQuery[$attribute] = $operator ? "$operator $value" : $value;
		
		return $this;
	}
	
	public function type($type)
	{
		$this->__call(__FUNCTION__, func_get_args());
		
		return $this;
	}
	
	public function order($type)
	{
		$this->__call(__FUNCTION__, func_get_args());
		
		return $this;
	}
	
	public function selectTask()
	{
		$this->recordMethodCall(__FUNCTION__, func_get_args());
		$this->abandonQuery();
		
		return $this;
	}
	
	public function selectTasks()
	{
		$this->recordMethodCall(__FUNCTION__, func_get_args());
		$this->abandonQuery();
		
		return $this;
	}
	
	public function find()
	{
		$this->recordMethodCall(__FUNCTION__, func_get_args());
		
		$returnValue = $this->getConferenceReturnValue() ??
			$this->getQueryReturnValue() ??
			$this->getNextReturnValue(__FUNCTION__);
		
		$this->saveExecutedQuery();
		
		return $returnValue;
	}
	
	private function getConferenceReturnValue()
	{
		$conference = $this->conferenceRow ?? $this->buildOrgRow(["org_type_id" => 4]);
		$orgCode = $this->currentQuery["org_code"] ?? "";
		$isRequestingConference = substr($orgCode, -2) === "11";
		
		return $isRequestingConference ? $conference : null;
	}
	
	public function setPropsForQuery($requirements, $props)
	{
		$this->setReturnValueForQuery(
			$requirements,
			$this->buildOrgRow($props)
		);
	}
	
	public function setPropSetsForQuery($requirements, ...$propSets)
	{
		$returnValue = array_map(function ($props) {
			return $this->buildOrgRow($props);
		}, $propSets);
		
		$this->setReturnValueForQuery($requirements, $returnValue);
	}
	
	public function setConsecutivePropsForQuery($requirements, ...$propSets)
	{
		$returnValue = array_map(function ($propSet) {
			return $this->buildOrgRow($propSet);
		}, $propSets);
		
		$key = $this->deriveQueryKey($requirements);
		$this->consecutiveQueryReturnValues[$key] = $returnValue;
	}
	
	/**
	 * @param $requirements
	 * @param $returnValue
	 */
	private function setReturnValueForQuery($requirements, $returnValue)
	{
		$key = $this->deriveQueryKey($requirements);
		$this->queryReturnValues[$key] = $returnValue;
	}
	
	public function setPropSetsForGetAllParkedDomains(...$propSets)
	{
		$this->setPropSetsForQuery( ["parked_domain" => "!= NULL"], ...$propSets );
		
		array_map(function($propSet) {
			$this->setPropsForQuery( ["org_code" => $propSet["org_code"]], $propSet );
		}, $propSets);
	}
	
	public function setPropSetsForGetJsonCongregationsWithParkableDomains(...$propSets)
	{
		$this->setPropSetsForQuery( ["type" => "Congregation", "handle" => "!= NULL"], ...$propSets );
	}
	
	public function setPropsForGetRandomCongregation($props = [])
	{
		$this->setPropsForQuery(
			[
				"handle" => "!= NULL",
				"type" => "Congregation",
				"is_active" => "Y",
				"order" => "RANDOM()"
			],
			$props
		);
	}
	
	public function setConsecutivePropsForGetRandomCongregation(...$propSets)
	{
		$this->setConsecutivePropsForQuery(
			[
				"handle" => "!= NULL",
				"type" => "Congregation",
				"is_active" => "Y",
				"order" => "RANDOM()"
			],
			...$propSets
		);
	}
	
	private function getQueryReturnValue()
	{
		return $this->getNextQueryReturnValue() ?? $this->getOnlyQueryReturnValue();
	}
	
	/**
	 * @return mixed|null
	 */
	private function getNextQueryReturnValue()
	{
		$key = $this->deriveQueryKey($this->currentQuery);
		
		if (!isset($this->consecutiveQueryReturnValues[$key])) { return null; }
		
		return array_shift($this->consecutiveQueryReturnValues[$key]);
	}
	
	/**
	 * @return mixed
	 */
	private function getOnlyQueryReturnValue()
	{
		$key = $this->deriveQueryKey($this->currentQuery);
		
		if (!isset($this->queryReturnValues[$key])) { return null; }
		
		return $this->queryReturnValues[$key];
	}
	
	/**
	 * @param $requirements
	 * @return false|string
	 */
	private function deriveQueryKey($requirements)
	{
		return json_encode($requirements);
	}
	
	private function abandonQuery()
	{
		if (!$this->currentQuery) return;
		
		$this->abandonedQueries[] = $this->currentQuery;
		$this->currentQuery = [];
	}
	
	private function saveExecutedQuery()
	{
		if (!$this->currentQuery) return;
		
		$this->executedQueries[] = $this->currentQuery;
		$this->currentQuery = [];
	}
	
	public function wasQueryExecuted($requirements)
	{
		return in_array($requirements, $this->executedQueries);
	}
	
	public function setPropsForFind(array $props = [])
	{
		$this->setReturnValue(
			'find',
			$this->buildOrgRow($props)
		);
	}
	
	public function setPropSetsForFind(...$propSets)
	{
		$this->setOrgRowsReturnValueWithPropSets($propSets, "find");
	}
	
	public function setConferencePropsForFind(array $props)
	{
		$props["org_type_id"] = $props["org_type_id"] ?? 4;
		
		$this->conferenceRow = $this->buildOrgRow($props);
	}
	
	/* Everything Else */
	
	public function wasMethodCalled($method)
	{
		return count($this->getCalls($method)) > 0;
	}
	
	public function wasMethodCalledWith($method, ...$args)
	{
		$calls = $this->getCalls($method);
		
		return in_array($args, $calls);
	}
	
	public function setPropsForSelectRandomCongregation(array $props = [])
	{
		$this->setReturnValue(
			'selectRandomCongregation',
			$this->buildOrgRow($props)
		);
	}
	
	public function setConsecutivePropsForSelectRandomCongregation(...$propSets)
	{
		$orgRows = array_map(function ($propSet) {
			return $this->buildOrgRow($propSet);
		}, $propSets);
		
		$this->setReturnValues(
			"selectRandomCongregation",
			...$orgRows
		);
	}
	
	public function setPropsForSelectAllOrgsWithParkedDomains(...$propSets)
	{
		$this->setOrgRowsReturnValueWithPropSets($propSets, "selectAllOrgsWithParkedDomains");
	}
	
	/**
	 * @param $propSets
	 * @param $method
	 */
	private function setOrgRowsReturnValueWithPropSets($propSets, $method)
	{
		$orgRows = array_map(function ($props) {
			return $this->buildOrgRow($props);
		}, $propSets);
		
		$this->setReturnValue($method, $orgRows);
	}
	
	public function setPropsForSelectJsonCongregationsMissingHandles(...$propSets)
	{
		$this->setJsonOrgsReturnValueWithPropSets(
			$propSets,
			["org_type_id" => 5],
			"selectJsonCongregationsMissingHandles"
		);
	}
	
	public function setPropsForSelectJsonSchoolsMissingHandles(...$propSets)
	{
		$this->setJsonOrgsReturnValueWithPropSets(
			$propSets,
			["org_type_id" => 7],
			"selectJsonSchoolsMissingHandles"
		);
	}
	
	/**
	 * @param $propSets
	 * @param $defaultProps
	 * @param $method
	 */
	private function setJsonOrgsReturnValueWithPropSets($propSets, $defaultProps, $method)
	{
		$jsonOrgs = array_map(function ($props) use ($defaultProps) {
			return json_encode(array_merge($defaultProps, $props));
		}, $propSets);
		
		$this->setReturnValue($method, $jsonOrgs);
	}
	
	public function setReturnValue($method, $returnValue)
	{
		$this->returnValues[$method] = $returnValue;
	}
	
	public function setReturnValues($method, ...$returnValues)
	{
		$this->consecutiveReturnValues[$method] = $returnValues;
	}
	
	private function buildOrgRow($props = [])
	{
		// parked_domain isn't stored in json in the db.
		$parked_domain = $props["parked_domain"] ?? null;
		unset($props["parked_domain"]);
		
		$props = array_merge([
			"handle" => "handle",
			"is_active" => "Y",
			"org_type_id" => 5,
			"org_code" => "abcdef"
		], $props);
		
		return [
			"org_code" => $props["org_code"],
			"org_type_id" => $props["org_type_id"],
			"handle" => $props["handle"],
			"parked_domain" => $parked_domain,
			"json" => json_encode($props)
		];
	}
	
	private function recordMethodCall($method, $args)
	{
		$this->calls[$method][] = $args;
	}
	
	/**
	 * @param $method
	 * @return array
	 */
	public function getCalls($method): array
	{
		return $this->calls[$method] ?? [];
	}
	
	/**
	 * @param $method
	 * @return mixed
	 */
	private function getNextReturnValue(string $method)
	{
		$return_value = $this->returnValues[$method] ?? null;
		return !empty($this->consecutiveReturnValues[$method]) ?
			array_shift($this->consecutiveReturnValues[$method]) :
			$return_value;
	}
}