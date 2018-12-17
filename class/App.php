<?php

namespace LittleToDo;

class App
{
	private $renderer;
	
	public function __construct(Renderer $renderer)
	{
		$this->renderer = $renderer;
	}
	
	public function render()
	{
		$this->renderer->render("app.twig");
	}
}