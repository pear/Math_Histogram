<?php

include 'Math/Histogram4D.php';

// let's generate some data
for ($i=0; $i < 100; $i++) {
	$a['x'][$i] = rand(0,4);
	$a['y'][$i] = rand(0,4);
	$a['z'][$i] = rand(0,4);
}

// and create a histogram from the data 
// with specific options
$h = new Math_Histogram4D();
$h->setBinOptions(
	array(
		'low' => array( 'x' => 0, 'y' => 0, 'z'=>0),
		'high' => array( 'x' => 4.0, 'y' => 4.0, 'z'=>4.0),
		'nbins' => array( 'x' => 4, 'y' => 4, 'z'=>4)
		)
	);
$h->setData($a);
$h->calculate();
// now we print out a comma separated set
echo $h->toSeparated();
// and the whole Histogram information
print_r($h->getHistogramInfo());

?>
