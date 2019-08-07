<?php

namespace LittleToDo;

class Database
{
	/** @var \SQLite3 $db */
	private $db;
	
	private $query;
	private $requirements = [];
	private $limit = FALSE;
	private $order = "";
	
	public function __construct()
	{
		$this->db = new \SQLite3(dir(__DIR__) . 'db.sqlite');
		
		$this->db->query("PRAGMA foreign_keys = ON;");
		$this->db->query(
			"
			CREATE TABLE IF NOT EXISTS task (
				id INT PRIMARY KEY NOT NULL,
				title TEXT NOT NULL UNIQUE,
				description TEXT,
				date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
				date_completed TIMESTAMP,
				parent_id,
				FOREIGN KEY(parent_id) REFERENCES task(id)
			);
			"
		);
		
		$this->db->createFunction('regexp', [$this, '_sqliteRegexp'], 2);
	}
	
	public function _sqliteRegexp($string, $pattern)
	{
		if (preg_match('/' . $pattern . '/i', $string)) {
			return true;
		}
		return false;
	}
	
	public function __call($attribute, $arguments)
	{
		$value = is_numeric($arguments[0]) ? $arguments[0] : "\"$arguments[0]\"";
		$operator = $arguments[1] ?? "=";
		
		$this->saveRequirement($attribute, $operator, $value);
		
		return $this;
	}
	
	public function order($order)
	{
		$this->order = $order;
		
		return $this;
	}
	
	/**
	 * @param $attribute
	 * @param $operator
	 * @param $value
	 */
	protected function saveRequirement($attribute, $operator, $value)
	{
		$this->requirements[] = [
			$attribute,
			$operator,
			$value
		];
	}
	
	public function selectTask()
	{
		$this->initQuery();
		$this->limit = TRUE;
		
		return $this;
	}
	
	public function selectTasks()
	{
		$this->initQuery();
		
		return $this;
	}
	
	protected function initQuery()
	{
		$this->resetQueryFields();
		$this->query = "SELECT * FROM task";
	}
	
	public function find()
	{
		$query = $this->makeQueryString();
		$result = $this->executeQuery($query);
		
		$this->resetQueryFields();
		
		return $result;
	}
	
	private function resetQueryFields()
	{
		$this->limit = FALSE;
		$this->order = "";
		$this->requirements = [];
	}
	
	private function makeQueryString()
	{
		$query = "SELECT * FROM task";
		
		$query = $this->addWhereString($query);
		
		if ($this->order) {
			$query .= " ORDER BY $this->order";
		}
		
		if ($this->limit) {
			$query .= " LIMIT 1";
		}
		
		return "$query;";
	}
	
	private function addWhereString($query)
	{
		if (!$this->requirements) return;
		
		return "$query WHERE {$this->makeWhereString()}";
	}
	
	/**
	 * @return string
	 */
	private function makeWhereString()
	{
		return array_reduce($this->requirements, function ($carry, $requirement) {
			$attribute = $requirement[0];
			$operator = $requirement[1];
			$value = $requirement[2];
			
			$requirementString = "$attribute $operator $value";
			
			return $carry ? $carry . " AND $requirementString" : $requirementString;
		}, "");
	}
	
	/**
	 * @param $query
	 * @return array|mixed
	 */
	private function executeQuery($query)
	{
		return ($this->limit) ? $this->db->querySingle($query, TRUE)
			: $this->getResults($query);
	}
	
	private function getResults($query)
	{
		$result = $this->db->query($query);
		
		if ($result) {
			$resultArray = [];
			while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
				$resultArray[] = $row;
			}
			
			return $resultArray;
		}
	}
}
