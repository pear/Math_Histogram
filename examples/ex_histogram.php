<?php

require_once "Math/Histogram.php";

// create a boring array
$vals = array(
			1.5,2,3,4,0,3.2,0.1,0,0,5,3,2,3,4,1,2,4,5,1,3,2,4,5,2,3,4,1,2,
			1.5,2,3,4,0,3.2,0.1,0,0,5,3,2,3,4,1,2,4,5,1,3,2,4,5,2,3,4,1,2,
			1.5,2,3,4,0,3.2,0.1,0,0,5,3,2,3,4,1,2,4,5,1,3,2,4,5,2,3,4,1,2
		);

// create an instance 
$h = new Math_Histogram();

// let's do a cummulative histogram
$h->setType(HISTOGRAM_CUMMULATIVE);
$h->setData($vals);
$h->calculate();
print_r($h->getHistogramInfo());
print_r($h->getBins(HISTOGRAM_HI_BINS));
echo $h->printHistogram();
echo "\n=====\n";

// let us read a bigger data set:
$data = array();
foreach(file("ex_histogram.data") as $item)
	$data[] = floatval(trim($item));

// let's do a simple histogram
$h->setType(HISTOGRAM_SIMPLE);
// and set new bin options
$h->setBinOptions(20,1.7,2.7);
// then set a the big data set
$h->setData($data);
// and calculate using full stats
$h->calculate(STATS_FULL);
print_r($h->getHistogramInfo());
print_r($h->getBins(HISTOGRAM_MID_BINS));
echo $h->printHistogram(HISTOGRAM_MID_BINS);

?>
