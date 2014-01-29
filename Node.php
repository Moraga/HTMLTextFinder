<?php
/**
 * HTML/XML Node
 */
class Node {
	/**
	 * Node name
	 * @var string
	 */
	public $name;
	
	/**
	 * Node attributes
	 * @var array
	 */
	public $attributes = array();
	
	/**
	 * Child nodes
	 * @var array
	 */
	public $childNodes = array();
	
	/**
	 * Parent nodes
	 * @var array
	 */
	public $parents = array();
	
	/**
	 * Node content
	 * @var string
	 */
	public $content;
	
	/**
	 * Output logs
	 * @var boolean
	 */
	public $debug = false;
	
	/**
	 * Creates a new Node object
	 * @param string $name The name of the node
	 * @param array $attributes The attributes of the node
	 */
	function __construct($name=null, $attributes=array()) {
		$this->name = $name;
		$this->attributes = $attributes;
	}
	
	/**
	 * Get the text of the node
	 * @return string
	 */
	function text() {
		return trim(preg_replace('#&lt;/?[a-z]+&gt;#', '', strip_tags($this->content)));
	}
	
	/**
	 * Calculates the text rank of the node
	 * @return float
	 */
	function rank() {
		$Ec = 0;
		$influence = array('p');
		$influence_acumulative = 1;
		
		// calculate child nodes rank
		if ($this->childNodes) {
			foreach ($this->childNodes as $node) {
				$Ec += strlen($node->text());
				
				if (in_array($node->name, $influence))
					$influence_acumulative *= 10;
			}
		}
		
		$Ec = $Ec + (strlen($this->text()) - $Ec);
		
		if ($influence_acumulative > 1)
			$Ec = $Ec * (($influence_acumulative / 100) + 1);
		
		return (count($this->childNodes) * $Ec) / (count($this->parents) + 1);
	}
	
	/**
	 * Parses HTML/XML string
	 * @param string $str The input string
	 * @return Node Returns the top rank node
	 */
	function parse($str) {
		// single tags
		$single = array('link', 'meta', 'input', 'br', 'img');
		// first step
		$is1 = 0;
		// second step
		$is2 = 0;
		// close
		$cl = 0;
		// in name
		$is_name = 1;
		// element name
		$name = '';
		// attributes of element
		$attr = array();
		// attribute temp name
		$attrn = '';
		// attribute temp value
		$attrv = '';
		// in attribute
		$in_attrv = 0;
		// char used to open attr value
		$cl_attrv = '';
		// content
		$content = '';
		// top rank
		$top_rank = 0;
		// top node
		$top_e = '';
		// stack of nodes
		$stack = array();
		// total characteres to parse
		$strn = strlen($str);

		for ($i=0; $i < $strn; $i++) {
			// character
			$c = $str[$i];
			
			// inside element
			if ($is1 && $is2) {
				// end of element
				if ($c == '>') {
					// create element
					if (!$cl || $str[$i-1] == '/') {
						if ($attrn)
							$attr[$attrn] = $attrv;
						
						// creates a new Node
						$node = new self($name, $attr);
						
						if ($stack) {
							$stack[count($stack)-1]->content .= $content;
							$stack[count($stack)-1]->childNodes[] = $node;
						}
						// first element
						else {
							if ($this->debug)
								echo "<h1>is the document!</h1>";
							$this->childNodes[] = $node;
						}
						
						// single element
						if (in_array($name, $single)) {
							$stack[count($stack)-1]->content .= '<'. $node->name .'/>';
						}
						else
							$stack[] = $node;
					}
					// closes the open tag
					else {
						$closed = array_pop($stack);
						$closed->content .= $content;
						
						if ($this->debug)
							echo "<h2>rank of {$closed->name} = {$closed->rank()}</h2>";
						
						if ($closed->rank() > $top_rank) {
							$top_rank = $closed->rank();
							$top_e = $closed;
						}
						
						// parent
						$node = $stack[count($stack) - 1];
						$node->parents = array_merge(
							is_array($node->parents) ? $node->parents : array(),
							array($closed->name),
							$closed->parents);
						$node->content .= '<'. $closed->name .'>'. $closed->content .'</'. $closed->name.'>';
						
						if ($this->debug)
							echo 'close ';
					}

					// reset
					$is_name = 1;
					$is1 = $is2 = $cl = $in_attrv = 0;
					$attr = array();
					$attrn = $attrv = $cl_attrv = $content = '';
					
					if ($this->debug)
						echo "element: -- <b>{$name}</b> -- i: {$i}, c: {$c}<br/>";
					
					$name = '';
					
				}
				// capturing node name and attributes
				else {
					if ($c == ' ' && !$cl_attrv) {					
						$is_name = 0;
						if ($attrn) {
							$attr[$attrn] = $attrv;
							$attrn = $attrv = '';
							$in_attrv = 0;
						}
					}
					else if ($c == '/' && $str[$i+1] == '>') {
						if ($this->debug)
							echo "close {$name}<br/>";
						$cl = 1;
					}
					else {
						// concatening to name
						if ($is_name) {
							$name .= $c;
						}
						// attributes
						else {
							if (!$in_attrv && $c == '=') {
								$in_attrv = 1;
							}
							else {
								// value of attr
								if ($in_attrv) {
									// if is opening value
									if ($str[$i-1] == '=' && ($c == '"' || $c == '\''))
										$cl_attrv = $c;
									else {
										if ($cl_attrv && $cl_attrv == $c) {
											$attr[$attrn] = $attrv;
											$attrn = $attrv = $cl_attrv = '';
											$in_attrv = 0;
										}
										else {
											$attrv .= $c;
										}
									}
								}
								// name of attr
								else {
									$attrn .= $c;
								}
							}
						}
					}
				}
			}
			else {
				// first step
				if ($is1) {
					if ($c >= 'a' && $c <= 'z' || $c == '/') {
						$is2 = 1;
						
						if ($c == '/')
							$cl = 1;
						else
							$name = $c;
					}
					else {
						$is1 = 0;
						// add to content
						$content .= $str[$i-1];
						if ($this->debug)
							echo 'not is element<br/>';
					}
				}		
				else {
					if ($c == '<') {
						$is1 = 1;
					}
					else {
						$content .= $c;
					}
				}
			}
		}
		
		if ($this->debug)
			echo '<h1>Top rank is '.$top_rank.' in '.$top_e->name.'. We talking about:</h1><h4>'.$top_e->content.'</h4>';
		
		return $top_e;
	}
}

?>