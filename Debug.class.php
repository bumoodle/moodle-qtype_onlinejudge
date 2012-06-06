<?php

class Debug
{

	/**
	 * Wrapper for Moodle's global debug variable, so we don't have to resort to non-idomatic calls. 
	 */
	public static function on()
	{
		//moodle use the global $CFG to represent the global configuration table
		//(ugly; it should be a static class- we'll use this one)
		global $CFG;
		return $CFG->debug;
	}
	
	/**
	 * Prints an object in human readable-format in side of a html page. 
     */
	public static function pre_print($object, $return = 1)
	{
		if(!self::on())
			return;
		
		//no need to use output buffering for something this trivial
		$buff = '<pre>'.print_r($object, true).'</pre>';
		
		//output the content
		if($return)
			return $buff;
		else
			echo $buff;
			
		//and ensure it's displayed immediately
		flush();
	}
}