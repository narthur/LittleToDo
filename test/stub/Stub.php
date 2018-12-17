<?php

namespace LittleToDo;

trait Stub
{
	private $calls = [];
	private $methodCallIndices = [];
	private $indexedReturnValues = [];
	private $mappedReturnValues = [];
	private $consecutiveReturnValues = [];
	private $returnCallbacks = [];
	private $returnValues = [];
	
	/** @var \PHPUnit\Framework\TestCase $testCase */
	private $testCase;
	
	/** @noinspection PhpMissingParentConstructorInspection */
	public function __construct(\PHPUnit\Framework\TestCase $testCase)
	{
		$this->testCase = $testCase;
	}
	
	/**
	 * @param $method
	 * @param $args
	 * @return mixed|null
	 */
	public function handleCall($method, $args)
	{
		$this->calls[$method][] = $args;
		return $this->getIndexedReturnValue($method) ?:
			$this->getMappedReturnValue($method, $args) ?:
				$this->getConsecutiveReturnValue($method) ?:
					$this->getCallbackReturnValue($method, $args) ?:
						$this->getReturnValue($method);
	}
	
	private function getConsecutiveReturnValue($method)
	{
		if (!isset($this->consecutiveReturnValues[$method])) return null;
		return (count($this->consecutiveReturnValues[$method]) > 0) ? array_shift($this->consecutiveReturnValues[$method]) : null;
	}
	
	private function getCallbackReturnValue($method, $args)
	{
		if (!isset($this->returnCallbacks[$method])) return null;
		return call_user_func($this->returnCallbacks[$method], ...$args);
	}
	
	/**
	 * @param $method
	 * @return mixed
	 */
	private function getIndexedReturnValue($method)
	{
		$this->incrementCallIndex($method);
		$currentIndex = $this->methodCallIndices[$method];
		return isset($this->indexedReturnValues[$method][$currentIndex]) ? $this->indexedReturnValues[$method][$currentIndex] : null;
	}
	
	/**
	 * @param $method
	 */
	private function incrementCallIndex($method)
	{
		$this->methodCallIndices[$method] =
			isset($this->methodCallIndices[$method]) ? $this->methodCallIndices[$method] + 1 : 0;
	}
	
	/**
	 * @param $method
	 * @param $args
	 * @return null
	 */
	private function getMappedReturnValue($method, $args)
	{
		$callSignature = json_encode($args);
		return isset($this->mappedReturnValues[$method][$callSignature]) ? $this->mappedReturnValues[$method][$callSignature] : null;
	}
	
	/**
	 * @param $method
	 * @return mixed
	 */
	private function getReturnValue($method)
	{
		return isset($this->returnValues[$method]) ? $this->returnValues[$method] : null;
	}
	
	/**
	 * @param $method
	 * @param $returnValue
	 */
	public function setReturnValue($method, $returnValue)
	{
		$this->returnValues[$method] = $returnValue;
	}
	
	public function setReturnValues($method, ...$returnValues)
	{
		$this->consecutiveReturnValues[$method] = $returnValues;
	}
	
	/**
	 * @param $method
	 * @param $callback
	 */
	public function setReturnCallback($method, $callback)
	{
		$this->returnCallbacks[$method] = $callback;
	}
	
	/**
	 * @param int $index Zero-based call index
	 * @param $method
	 * @param $returnValue
	 */
	public function setReturnValueAt($index, $method, $returnValue)
	{
		$this->indexedReturnValues[$method][$index] = $returnValue;
	}
	
	/**
	 * @param string $method
	 * @param array $map Array of arrays, each internal array representing a list of arguments followed by a single
	 * return value
	 */
	public function setMappedReturnValues($method, array $map)
	{
		$processedMap = array_reduce($map, function ($carry, $entry) use ($method) {
			$returnValue = array_pop($entry);
			$callSignature = json_encode($entry);
			return array_merge($carry, [
				$callSignature => $returnValue
			]);
		}, []);
		$this->mappedReturnValues[$method] = array_merge(
			isset($this->mappedReturnValues[$method]) ? $this->mappedReturnValues[$method] : [],
			$processedMap
		);
	}
	
	/**
	 * @param string $method
	 */
	public function assertMethodCalled($method)
	{
		$this->testCase->assertTrue(
			$this->wasMethodCalled($method),
			"Failed asserting that '$method' was called"
		);
	}
	
	/**
	 * @param string $method
	 */
	public function assertMethodNotCalled($method)
	{
		$this->testCase->assertFalse(
			$this->wasMethodCalled($method),
			"Failed asserting that '$method' was not called"
		);
	}
	
	/**
	 * @param string $method
	 * @return bool
	 */
	public function wasMethodCalled($method)
	{
		return !empty($this->getCalls($method));
	}
	
	/**
	 * @param string $method
	 * @param mixed ...$args
	 */
	public function assertMethodCalledWith($method, ...$args)
	{
		$argsExport = var_export($args, TRUE);
		$haystackExport = var_export($this->getCalls($method), TRUE);
		$message = "Failed asserting that '$method' was called with args $argsExport\r\n\r\nHaystack:\r\n$haystackExport";
		$this->testCase->assertTrue(
			$this->wasMethodCalledWith($method, ...$args),
			$message
		);
	}
	
	/**
	 * @param string $method
	 * @param mixed ...$args
	 * @return bool
	 */
	public function wasMethodCalledWith($method, ...$args)
	{
		return in_array($args, $this->getCalls($method));
	}
	
	public function assertAnyCallMatches($method, callable $callable, $message = false)
	{
		$calls = $this->getCalls($method);
		$bool = array_reduce($calls, $callable, FALSE);
		$error = $message ?: "Failed asserting any call matches callback.";
		$this->testCase->assertTrue($bool, $error);
	}
	
	/**
	 * @param string $method
	 * @param string $needle
	 */
	public function assertCallsContain($method, $needle)
	{
		$message = "Failed asserting that '$needle' is in haystack: \r\n" .
			$this->getCallHaystack($method);
		$this->testCase->assertTrue(
			$this->doCallsContain($method, $needle),
			$message
		);
	}
	
	/**
	 * @param string $method
	 * @param string $needle
	 * @return bool
	 */
	public function doCallsContain($method, $needle)
	{
		$haystack = $this->getCallHaystack($method);
		return strpos($haystack, $needle) !== false;
	}
	
	public function assertCallCount($method, $count)
	{
		$this->testCase->assertCount($count, $this->getCalls($method));
	}
	
	/**
	 * @param string $method
	 * @return string
	 */
	private function getCallHaystack($method)
	{
		return stripslashes(var_export($this->getCalls($method), true));
	}
	
	/**
	 * @param $method
	 * @return array
	 */
	public function getCalls($method)
	{
		return (isset($this->calls[$method])) ? $this->calls[$method] : [];
	}
}