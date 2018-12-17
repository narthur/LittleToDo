<?php

namespace LittleToDo;

final class TestApp extends TestCase
{
	/** App $mockedApp */
	private $app;
	
	protected function setUp()
	{
		parent::setUp();
		
		$this->app = $this->factory->getApp();
	}
	
	public function testExists()
	{
		$this->assertInstanceOf("\\LittleToDo\\App", $this->app);
	}
	
	public function testRendersApp()
	{
		$this->app->render();
		
		$this->stubTwig->assertTwigTemplateRendered("app.twig");
	}
}