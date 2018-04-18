<?php

if(!function_exists('build_path')) {
	/**
	 * building path
	 * @param array ...$segments
	 * @return string
	 */
	function build_path(...$segments)
	{
		return implode(DIRECTORY_SEPARATOR, array_filter($segments));
	}
}
if(!function_exists('dd')) {
	/**
	 * dump and die
	 * @param array ...$arguments
	 */
	\Symfony\Component\VarDumper\VarDumper::setHandler(function ($var) {
		$cloner = new \Symfony\Component\VarDumper\Cloner\VarCloner();
		$dumper = 'cli' === PHP_SAPI
			? new \Symfony\Component\VarDumper\Dumper\CliDumper()
			: new \Symfony\Component\VarDumper\Dumper\HtmlDumper();
		if($dumper instanceof \Symfony\Component\VarDumper\Dumper\HtmlDumper) {
			$style = array(
				'default' => 'background-color:#fff; color:#FF8400; line-height:1.2em; font:1rem Menlo, Monaco, Consolas, monospace; word-wrap: break-word; white-space: pre-wrap; position:relative; z-index:99999; word-break: normal',
				'num' => 'color:#1299DA;',
				'const' => 'font-weight:bold',
				'str' => 'color:#FF8400',
				'note' => 'color:#1299DA;',
				'ref' => 'color:#FF8400;font-weight:bold',
				'public' => 'color:#f66153;font-weight:lighter',
				'protected' => 'color:#f66555;font-weight:lighter',
				'private' => 'color:#FF6666;font-weight:lighter',
				'meta' => 'color:#B729D9',
				'key' => 'color:#388E3C',
				'index' => 'color:#1299DA',
			);
			$dumper->setStyles($style);
		}
		$dumper->dump($cloner->cloneVar($var));
	});

	function dd(...$arguments)
	{
		while (ob_get_level()) {
			if(!@ob_end_clean()) {
				ob_clean();
			}
		}
		ob_start();
		array_map(function ($args) {
			dump($args);
		}, $arguments);
		die(ob_get_clean());
	}
}

if(!function_exists('memory')) {
	/**
	 * print current system using memory
	 */
	function memory()
	{
		$size = memory_get_usage();
		$unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
		$i = floor(log($size, 1024));
		$size = @round($size / pow(1024, $i), 2) . ' ' . $unit[$i];
		echo sprintf("<script>console.log('%s')</script>", $size);
	}
}

if(!function_exists('route')) {
	/**
	 * @param $route
	 * @return string
	 */
	function route($route): string
	{
		return $route;
	}
}

if(!function_exists('assets')) {
	/**
	 * @param $path
	 * @return string
	 */
	function assets($path): string
	{
		if($path[0] === '/') {
			return $path;
		}
		return '/app/' . trim($path, '/');
	}
}
