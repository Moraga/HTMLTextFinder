<?php

require 'Node.php';

$html = file_get_contents('http://www.theguardian.com/science/2014/jan/29/fifth-neanderthals-genetic-code-lives-on-humans');

$parser = new Node;
//$parser->debug = true;
$topContent = $parser->parse($html);

echo $topContent->content;

?>