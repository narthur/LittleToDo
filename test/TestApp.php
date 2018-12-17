<?php

final class TestApp extends LittleToDo\TestCase
{
	public function testExists()
	{
		$app = $this->factory->getApp();
		
		$this->assertInstanceOf("\\LittleToDo\\App", $app);
	}
}