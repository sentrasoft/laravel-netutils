<?php
namespace Sentrasoft\Netutils;

trait PropertyTrait
{
	/**
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name)
	{
		$method = 'get'. ucfirst($name);
		if (!method_exists($this, $method)) {
			trigger_error('Undefined property');
			return null;
		}
		return $this->$method();
	}
	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value)
	{
		$method = 'set'. ucfirst($name);
		if (!method_exists($this, $method)) {
			trigger_error('Undefined property');
			return;
		}
		$this->$method($value);
	}
}
