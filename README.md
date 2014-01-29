HTMLTextFinder
==============

Finds the main text of a HTML/XML

Example

	<?php

	require 'Node.php';

	$html = file_get_contents('http://www.theguardian.com/science/2014/jan/29/fifth-neanderthals-genetic-code-lives-on-humans');

	$parser = new Node;
	//$parser->debug = true;
	$topContent = $parser->parse($html);

	echo $topContent->content;

	?>

Returns

  The last of the Neanderthals may have died out tens of thousands of years ago, but large stretches of their genetic code live on in people today.
  
  Though many of us can claim only a handful of Neanderthal genes, when added together, the human population carries more than a fifth of the archaic human's DNA, researchers found.
  
  The finding means that scientists can study about 20% of the Neanderthal genome without having to prise the genetic material from fragile and ancient fossils....
