<?php

namespace LittleToDo;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
	/** @var Factory $factory */
	protected $factory;
	
	/** @var Twig|StubTwig $stubTwig */
	protected $stubTwig;
	
	protected function setUp()
	{
		parent::setUp();
		
		$this->stubTwig = new StubTwig($this);
		
		$this->factory = new Factory(
			$this->stubTwig
		);
	}
}