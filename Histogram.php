<?php
//
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2002 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Jesus M. Castagnetto <jmcastagnetto@php.net>                |
// +----------------------------------------------------------------------+
//
// $Id$
//

//include_once "Math/AbstractHistogram.php";
include_once "AbstractHistogram.php";

/**
 * Class to calculate the histogram distribution of a numerical data set.
 * It can calculate a regular distribution or a cummulative distribution.
 * The resulting histogram is sometimes called a "2D Histogram"
 * Data must not have null values.
 *
 * Example of use:
 *
 *   require_once "Math/Histogram.php";
 *   // create a boring array
 *   $vals = array(
 *           1.5, 2, 3, 4, 0, 3.2, 0.1, 0, 0, 5, 3, 2, 3, 4, 1, 2, 4, 5, 1,
 *           3, 2, 4, 5, 2, 3, 4, 1, 2, 1.5, 2, 3, 4, 0, 3.2, 0.1, 0, 0, 5,
 *           3, 2, 3, 4, 1, 2, 4, 5, 1, 3, 2, 4, 5, 2, 3, 4, 1, 2, 1.5, 2,
 *           3, 4, 0, 3.2, 0.1, 0, 0, 5, 3, 2, 3, 4, 1, 2, 4, 5, 1, 3, 2, 4,
 *           5, 2, 3, 4, 1, 2
 *   		);
 *   
 *   // create an instance
 *   $h = new Math_Histogram();
 *   
 *   // let's do a cummulative histogram
 *   $h->setType(HISTOGRAM_CUMMULATIVE);
 *   $h->setData($vals);
 *   $h->calculate();
 *   print_r($h->getBins(HISTOGRAM_HI_BINS));
 *   print_r($h->getStats());
 *   echo $h->printHistogram();
 *   echo "\n\n";
 *   
 *   // let us read a bigger data set:
 *   $data = array();
 *   foreach(file("bigdatafile") as $item)
 *   	$data[] = floatval(trim($item));
 *   
 *   // and set new bin options
 *   $h->setBinOptions(20,1.7,2.7);
 *   // then set a the big data set
 *   $h->setData($data);
 *   // let's do a regular histogram
 *   $h->setType(HISTOGRAM_SIMPLE);
 *   // and calculate using full stats
 *   $h->calculate(STATS_FULL);
 *   print_r($h->getBins(HISTOGRAM_MID_BINS));
 *   print_r($h->getStats());
 *   echo $h->printHistogram();
 * 
 * Originally this class was part of NumPHP (Numeric PHP package)
 *
 * @author  Jesus M. Castagnetto <jmcastagnetto@php.net>
 * @version 1.0
 * @access  public
 * @package Math_Histogram
 */
class Math_Histogram extends Math_AbstractHistogram {/*{{{*/

    /**
     * Constructor
     *
     * @access  public
     * @param   optional    int $type   one of HISTOGRAM_SIMPLE or HISTOGRAM_CUMMULATIVE
     * @param   optional    int $nbins  number of bins to use
     * @param   optional    float   $rangeLow   lowest value to use for bin frequency calculation
     * @param   optional    float   $rangeHigh   highest value to use for bin frequency calculation
     * @return  object  Math_Histogram
     *
     * @see setBinOptions()
     * @see Math_AbstractHistogram::setType()
     * @see Math_AbstractHistogram
     */
    function Math_Histogram($type=HISTOGRAM_SIMPLE,$nbins=-1, $rangeLow=null, $rangeHigh=null) {/*{{{*/
        $this->setType($type);
        $this->setBinOptions($nbins, $rangeLow, $rangeHigh);
    }/*}}}*/

    /**
     * Sets the binning options. Overrides parent's method.
     * 
     * @access  public
     * @param   int $nbins  the number of bins to use for computing the histogram
     * @param   optional    float   $rangeLow   lowest value to use for bin frequency calculation
     * @param   optional    float   $rangeHigh   highest value to use for bin frequency calculation
     * @return  void
     */
    function setBinOptions($nbins, $rangeLow=null, $rangeHigh=null) {/*{{{*/
        $this->_nbins = (is_int($nbins) && $nbins > 2) ? $nbins : 10;
        $this->_rangeLow = $rangeLow;
        $this->_rangeHigh = $rangeHigh;
    }/*}}}*/
    
    /**
     * Sets the data to be processed. The data will be validated to 
     * be a simple uni-dimensional numerical array
     *
     * @access  public
     * @param   array   $data   the numeric array
     * @return  mixed   boolean true on success, a PEAR_Error object otherwise
     * 
     * @see _clear()
     * @see Math_AbstractHistogram::getData()
     * @see Math_AbstractHistogram
     * @see getHistogramData()
     */
    function setData($data) {/*{{{*/
        $this->_clear();
        if (!is_array($data))
            return PEAR::raiseError("array of numeric data expected");
        foreach ($data as $item)
            if (!is_numeric($item))
                return PEAR::raiseError("non-numeric item in array");
        $this->_data = $data;
        if (is_null($this->_rangeLow))
            $this->_rangeLow = min($this->_data);
        if (is_null($this->_rangeHigh))
            $this->_rangeHigh = max($this->_data);
        sort($this->_data);
        return true;
    }/*}}}*/

    /**
     * Calculates the histogram bins and frequencies
     *
     * @access  public
     * @param   optional    $statsMode  calculate basic statistics (STATS_BASIC) or full (STATS_FULL)
     * @return  mixed   boolean true on success, a PEAR_Error object otherwise
     *
     * @see Math_Stats
     */
    function calculate($statsMode=STATS_BASIC) {/*{{{*/
        $this->_stats = new Math_Stats();
        $this->_statsMode = $statsMode;
        $delta = ($this->_rangeHigh - $this->_rangeLow) / $this->_nbins;
        $lastpos = 0;
        $cumm = 0;
        $data = $this->_histogramData();
        $ndata = count($data);
        for ($i=0; $i < $this->_nbins; $i++) {
            $loBin = $this->_rangeLow + $i * $delta;
            $hiBin = $loBin + $delta;
            $this->_bins[$i]["low"] = $loBin;
            $this->_bins[$i]["high"] = $hiBin;
            $this->_bins[$i]["mid"] = ($hiBin + $loBin) / 2;
            if ($this->_type == HISTOGRAM_CUMMULATIVE)
                $this->_bins[$i]["count"] = $cumm;
            else
                $this->_bins[$i]["count"] = 0;
            for ($j=$lastpos; $j < $ndata; $j++) {
                if ( ($i == 0 && $this->_inRange($data[$j], $loBin, $hiBin, true, true))
                        || ($i > 0 && $this->_inRange($data[$j], $loBin, $hiBin))) {
                    $this->_bins[$i]["count"]++;
                    if ($this->_type == HISTOGRAM_CUMMULATIVE)
                        $cumm++;
                    continue;
                } else {
                    $lastpos = $j;
                    break;
                }
            }
        }
        return true;
    }/*}}}*/

    /**
     * Returns the statistics for the data set and the histogram bins and
     * frequencies
     *
     * @access  public
     * @return  mixed   an associative array on success, a PEAR_Error object otherwise
     */ 
    function getHistogramInfo() {/*{{{*/
        if (!empty($this->_nbins)) {
            $info = array (
                        "type" => ($this->_type == HISTOGRAM_CUMMULATIVE) ?  
                                            "cummulative frequency" : "histogram",
                        "data_stats" => $this->getDataStats(),
                        "hist_data_stats" => $this->getHistogramDataStats(),
                        "bins" => $this->_bins,
                        "nbins" => $this->_nbins,
                        "range" => array(
                                       "low" => $this->_rangeLow,
                                       "high" => $this->_rangeHigh
                                   )
                    );
            return $info;
        } else {
            return PEAR::raiseError("histogram has not been calculated");
        }
    }/*}}}*/

    /**
     * Returns the statistics for the data set
     *
     * @access  public
     * @return  mixed   an associative array on success, a PEAR_Error object otherwise
     */
    function getDataStats() {/*{{{*/
        if (!empty($this->_nbins)) {
            $this->_stats->setData($this->_data);
            return $this->_stats->calc($this->_statsMode);
        } else {
            return PEAR::raiseError("histogram has not been calculated");
        }
    }/*}}}*/

    /**
     * Returns the statistics for the data set, filtered using the bin range
     *
     * @access  public
     * @return  mixed   an associative array on success, a PEAR_Error object otherwise
     */
    function getHistogramDataStats() {/*{{{*/
        if (!empty($this->_nbins)) {
            $this->_stats->setData($this->_histogramData());
            return $this->_stats->calc($this->_statsMode);
        } else {
            return PEAR::raiseError("histogram has not been calculated");
        }
    }/*}}}*/


    /**
     * Returns bins and frequencies for the histogram data set
     *
     * @access  public
     * @param   optional    int $mode   one of HISTOGRAM_ALL_BINS, HISTOGRAM_LO_BINS, HISTOGRAM_MID_BINS, or HISTOGRAM_HI_BINS 
     * @return  mixed   an associative array on success, a PEAR_Error object otherwise
     */
    function getBins($mode = HISTOGRAM_ALL_BINS) {/*{{{*/
        if (empty($this->_bins))
            return PEAR::raiseError("histogram has not been calculated");
        switch ($mode) {
            case HISTOGRAM_ALL_BINS :
                return $this->_bins;
                break;
            case HISTOGRAM_MID_BINS :
            case HISTOGRAM_LO_BINS :
            case HISTOGRAM_HI_BINS :
                return $this->_filterBins($mode);
                break;
            default :
                return PEAR::raiseError("incorrect mode for bins");
        }
    }/*}}}*/

    /**
     * Prints a simple ASCII representation of the histogram
     *
     * @access  public
     * @param   optional    int $mode   one of HISTOGRAM_LO_BINS, HISTOGRAM_MID_BINS, or HISTOGRAM_HI_BINS (default)
     * @return  mixed   a string on success, a PEAR_Error object otherwise
     */
    function printHistogram($mode = HISTOGRAM_HI_BINS) {/*{{{*/
        if (empty($this->_bins))
            return PEAR::raiseError("histogram has not been calculated");
        $out = ($this->_type == HISTOGRAM_CUMMULATIVE) ?  "Cummulative Frequency" : "Histogram";
        $out .= "\n\tNumber of bins: ".$this->_nbins."\n";
        $out .= "\tPlot range: [".$this->_rangeLow.", ".$this->_rangeHigh."]\n";
        $hdata = $this->_histogramData();
        $out .= "\tData range: [".min($hdata).", ".max($hdata)."]\n";
        $out .= "\tOriginal data range: [".min($this->_data).", ".max($this->_data)."]\n";
        $out .= "BIN (COUNT) BAR (%)\n";
        $fmt = "%-4.2f (%-4d) |%s\n";
        $bins = $this->_filterBins($mode);
        $maxfreq = max(array_values($bins));
        $total = count($this->_data);
        foreach ($bins as $bin=>$freq)
            $out .=  sprintf($fmt, $bin, $freq, $this->_bar($freq, $maxfreq, $total));
        return $out;
    }/*}}}*/

    /**
     * Prints a simple ASCII bar
     *
     * @access  private
     * @param   int $freq   the frequency
     * @param   int $maxfreq    the maximum frequency
     * @param   int $total  the total count
     * @return  string
     */
    function _bar($freq, $maxfreq, $total) {/*{{{*/
        $fact = floatval(($maxfreq > 40) ? 40/$maxfreq: 1);
		$niter = round($freq * $fact);
		$out = "";
		for ($i=0; $i < $niter; $i++) 
			$out .= "*";
		return $out.sprintf(" (%.1f%%)", $freq/$total * 100);

    }/*}}}*/
    /**
     * Returns a subset of the bins array by bin value type
     *
     * @access  private
     * @param   int $mode one of HISTOGRAM_MID_BINS, HISTOGRAM_LO_BINS or HISTOGRAM_HI_BINS
     * @return  array
     */
    function _filterBins($mode) {/*{{{*/
        $map = array (
                HISTOGRAM_MID_BINS => "mid",
                HISTOGRAM_LO_BINS => "low",
                HISTOGRAM_HI_BINS => "high"
                );
        $filtered = array();
        foreach ($this->_bins as $bin)
            $filtered["{$bin[$map[$mode]]}"] = $bin["count"];
        return $filtered;
    }/*}}}*/

    /**
     * Returns an array of data contained within the range for the 
     * histogram calculation. Overrides the empty implementation in
     * Math_AbstractHistogram::_histogramData()
     *
     * @access  private
     * @return  array
     */
    function _histogramData() {/*{{{*/
        $data = array();
        foreach ($this->_data as $val)
            if ($val < $this->_rangeLow || $val > $this->_rangeHigh)
                continue;
            else
                $data[] = $val;
        return $data;
    }/*}}}*/
    
}/*}}}*/

// vim: ts=4:sw=4:et:
// vim6: fdl=1:

?>
