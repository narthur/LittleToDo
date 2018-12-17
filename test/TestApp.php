<?php

namespace LittleToDo;

final class TestApp extends TestCase
{
	/** App $app */
	private $app;
	
	protected function setUp()
	{
		parent::setUp();
		
		$this->app = $this->factory->getApp();
	}
	
	public function testRendersApp()
	{
		$this->app->render();
		
		$this->stubTwig->assertTwigTemplateRendered("app.twig");
	}
}