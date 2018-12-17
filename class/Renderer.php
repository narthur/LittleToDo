<?php

namespace LittleToDo;

class Renderer
{
	private $twig;
	
	public function __construct(Twig $twig)
	{
		$this->twig = $twig;
	}
	
	public function render($template, $data = [], $shouldReturn = false)
	{
		try {
			$data = ["_GET" => $_GET, "_POST" => $_POST, "ltd" => $data];
			$output = $this->twig->render($template, $data);
		} catch (\Exception $e) {
			$output = "Oops! Something went wrong while rendering this page.";
			$output .= "<br>" . $e->getMessage();
			echo $output;
		} finally {
			if ($shouldReturn) {
				return $output;
			} else {
				echo $output;
			}
		}
	}
}