<?php

namespace LittleToDo;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
	/** @var Factory $factory */
	protected $factory;
	
	protected function setUp()
	{
		parent::setUp();
		
		$this->factory = new Factory();
	}
}