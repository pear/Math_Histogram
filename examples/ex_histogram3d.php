<?php

require 'Math/Histogram3D.php';

// let's generate some values;
for ($i=0; $i < 100; $i++) {
	$a['x'][$i] = rand(-1,6);
	$a['y'][$i] = rand(-1,6);
}

$h = new Math_Histogram3D();
$h->setData($a);
$h->calculate();
// and output a tab delimited data set
echo $h->toSeparated(HISTOGRAM_MID_BINS, "\t");
// finally, let's get all the histogram info
print_r($h->getHistogramInfo());

// now, let's change the options a wee bit
$h->setBinOptions(
	array(
		'low' => array( 'x' => 0, 'y' => 0),
		'high' => array( 'x' => 5, 'y' => 5),
		'nbins' => array( 'x' => 5, 'y' => 5)
		)
	);
$h->setType(HISTOGRAM_CUMMULATIVE);
$h->calculate();
// and print the whole histogram info, not the different
// statistics for the data and the histogram data entries
print_r($h->getHistogramInfo());
?>
