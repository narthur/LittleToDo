<?php
namespace LittleToDo;

class Twig
{
	/** @var \Twig_Environment $twig */
	private $twig;
	
	public function __construct()
	{
		$pluginDirectory = dirname(dirname(__FILE__));
		$loader = new \Twig_Loader_Filesystem($pluginDirectory . "/view");
		
		$this->twig = new \Twig_Environment($loader, array(
			"cache" => $pluginDirectory . "/cache",
			"debug" => true
		));
		
		$this->twig->addExtension(new \Twig_Extension_Debug());
	}
	
	/**
	 * @param $templateFile
	 * @param array $data
	 * @return string
	 * @throws \Twig_Error_Loader
	 * @throws \Twig_Error_Runtime
	 * @throws \Twig_Error_Syntax
	 */
	public function render($templateFile, $data = [])
	{
		$template = $this->twig->load($templateFile);
		
		return $template->render($data);
	}
}