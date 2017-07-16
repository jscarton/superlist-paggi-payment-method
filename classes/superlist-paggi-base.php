<?php

class SuperlistPaggiBase{

	private $_data;

	public function __construct($data)
	{
		$this->_data=[];
		if (is_array($data)){
			foreach ($data as $key => $value) {
				if (!is_array($value))
					$this->_data[strtolower($key)]=$value;
				else
					$this->_data[strtolower($key)]=new SuperlistPaggiBase($value);
			}
		}

	}

	public function __get($name)
	{
		if (isset($this->_data[$name]))
			return $this->_data[$name];
		return null;
	}

	public function __set($name,$value)
	{
		if (!is_array($value))
			$this->_data[$name]=$value;
		else
			$this->_data[$name]= new SuperlistPaggiBase($value);
	}

	public function dump()
	{
		$dump=[];
		foreach ($this->_data as $key => $value) {
				$dump[$key]=$value;
		}
		return $dump;
	}
}