<?php
/**
 *  Stamp t.e.
 *  The Beautiful Template Engine
 *  @author Gabor de Mooij
 *  @copyright 2012
 */
class StampEngine {

	private $template; 
	private $matches;
	private $catelogue;
	private $id;
	private $cache = array();

	public function __construct($tpl='',$id='root') {
		$this->id = $id;
		$this->template = $tpl;
		$this->matches = array();
		$pattern = '/<!\-\-\scut:(\w+)\s\-\->(.*)?<!\-\-\s\/cut:\1\s\-\->/sU';
		
		preg_match_all($pattern, $this->template, $this->matches);
		$this->template = preg_replace($pattern,'',$this->template);
		$this->catelogue = array_flip($this->matches[1]);
		$this->sketchBook = $this->matches[2];
		
	}
	
	public function inCatelogue($id) {
		return (boolean) (isset($this->catelogue[$id]));
	}
	
	public function get($id) {
		if (strpos($id,'.')!==false) {
			$parts = (explode('.',$id));
			$id = reset($parts);
			array_shift($parts);
			$rest = implode('.',$parts);
		}
		if ($this->inCatelogue($id)) {
			$snippet = $this->sketchBook[$this->catelogue[$id]];
			$new = new self($snippet,$id);
			//echo $new;
		}
		if (isset($parts)) { 
			return $new->get($rest);
		}
		else {
			return $new;
		}
		
	
	}
	
	public function collect($list) {
		if (isset($this->cache[$list])) return $this->cache[$list];
		$listItems = explode('|',$list);
		$collection = array();
		foreach($listItems as $item) {
			$collection[] = $this->get($item);
		}
		return $collection;
	}
	
	
	public function __toString() {
		$template = $this->template;
		$template = preg_replace("/<!--\s*[a-zA-Z0-9:\(\),\/]*\s*-->/m","",$template);
    	$template = preg_replace("/\n[\n\t\s]*\n/m","\n",$template);
    	$template = trim($template);
		return $template;
	}
	
	public function glue($what,$snippet) {
		$matches=array();
		$pattern = '/<!\-\-\spaste:'.$what.'(\(([a-zA-Z0-9,]+)\))?\s\-\->/';
		preg_match($pattern,$this->template,$matches);
		//print_r($matches); exit;
		$copyOrig = $matches[0];
			
		if (isset($matches[2])) {
			$allowedSnippets = $matches[2];
			$allowedMap = array_flip(explode(',',$allowedSnippets));
			if (!isset($allowedMap[$snippet->getID()])) {
				throw new Exception('Snippet '.$snippet->getID().' not allowed in slot '.$what);
			}
			
		}
		$this->template = preg_replace($pattern,$snippet.$copyOrig,$this->template);
		return $this;
	}
	
	public function inject($where,$data) {
		$where = "#$where#";
		$this->template = str_replace($where,$data,$this->template);
		return $this;
	}
	
	public function injectAll($array) {
		foreach($array as $key=>$value) {
			$this->inject($key, $value);
		}
		return $this;
	}
	
	public function getID() {
		return $this->id;
	}
	
	public function copy() {
		return clone($this);
	}
	
	public function writeToCache($list) {
		$this->cache[$list] = $this->collect($list);
	}
	
	public function getCache() {
		return serialize($this->cache);
	}
	
	public function loadIntoCache($rawCacheData) {
		$this->cache = unserialize($rawCacheData);
	}	
	
	
}