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
 *   foreach(file("sqldata") as $item)
 *   	$data[] = floatval(trim($item));
 *   
 *   // and set new bin options
 *   $h->setBinOptions(6,null,null);
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
     * Sets the binning options
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
     * Sets the data to be processed. The data will be filtered using
     * the low and high values for the range if set. So in the general
     * case the original data and the one used for the histogram will
     * not have the same number of elements.
     *
     * @access  public
     * @param   array   $data   the numeric array
     * @return  mixed   boolean true on success, a PEAR_Error object otherwise
     * 
     * @see _clear()
     */
    function setData($data) {/*{{{*/
        $this->_clear();
        if (!is_array($data))
            return PEAR::raiseError("array of numeric data expected");
        foreach ($data as $item)
            if (!is_numeric($item))
                return PEAR::raiseError("non-numeric item in array");
        $this->_orig["data"] = array_values($data);
        if (!is_null($this->_rangeLow) && !is_null($this->_rangeHigh)) {
            foreach ($data as $item)
                if ($item >= $this->_rangeLow || $item <= $this->_rangeHigh)
                    $this->_data[] = $item;
        } else {
            $this->_data =& $this->_orig["data"];
            $this->_rangeLow = min($this->_data);
            $this->_rangeHigh = max($this->_data);
        }
        $this->_orig["count"] = count($data);
        $this->_orig["min"] = min($data);
        $this->_orig["max"] = max($data);
        sort($this->_data);
        return true;
    }/*}}}*/

    function getData() {
        if (is_null($this->_data))
            return PEAR::raiseError("data has not been set");
        else
            return $this->_data;
    }

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
        $data = $this->_filterData();
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
                if ($i == 0) {
                    if ($data[$j] >= $loBin
                        && $data[$j] <= $hiBin) {
                        $this->_bins[$i]["count"]++;
                        if ($this->_type == HISTOGRAM_CUMMULATIVE)
                            $cumm++;
                        continue;
                    } else {
                        $lastpos = $j;
                        break;
                    }
                } else {
                    if ($data[$j] > $loBin
                        && $data[$j] <= $hiBin) {
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
                    );
            // add the stats for the histogram subset if the bin ranges are
            // not null
            if (!is_null($this->_rangeLow) && !is_null($this->_rangeHigh))
                $info ["hist_data_stats"] = $this->getHistogramDataStats();
                        
            $info["bins"] = $this->_bins;
            $info["nbins"] = $this->_nbins;
            $info["range"] = array(
                                "low" => $this->_rangeLow,
                                "high" => $this->_rangeHigh
                                );
            return $info;
        } else {
            return PEAR::raiseError("histogram has not been calculated");
        }
    }/*}}}*/

    /**
     * Returns the original data set, before it was filtered using the range
     * limits
     *
     * @access  public
     * @return  mixed   an associative array on success, a PEAR_Error object otherwise
     */
    function getOriginal() {/*{{{*/
        if (empty($this->_orig))
            return PEAR::raiseError("data has not been set");
        else
            return $this->_orig;
    }/*}}}*/

    /**
     * Returns just the statistics for the data set
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
     * Returns just the statistics for the data set, filtered using the bin
     * range
     *
     * @access  public
     * @return  mixed   an associative array on success, a PEAR_Error object otherwise
     */
    function getHistogramDataStats() {/*{{{*/
        if (!empty($this->_nbins)) {
            $this->_stats->setData($this->_filterData());
            return $this->_stats->calc($this->_statsMode);
        } else {
            return PEAR::raiseError("histogram has not been calculated");
        }
    }/*}}}*/


    /**
     * Returns just bins and frequencies for the data set
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
        $out .= ", Number of bins: ".$this->_nbins."\n";
        $out .= "Histogram range: [".$this->_rangeLow.", ".$this->_rangeHigh."]\n";
        $out .= "Data range: [".min($this->_data).", ".max($this->_data)."]\n";
        $out .= "BIN (COUNT) BAR (%)\n";
        $fmt = "%-4.2f (%-4d) |%s\n";
        $bins = $this->_filterBins($mode);
        $maxfreq = max(array_values($bins));
        $total = count($this->_data);
        foreach ($bins as $bin=>$freq)
            $out .=  sprintf($fmt, $bin, $freq, $this->_bar($freq, $maxfreq, $total));
        return $out;
    }/*}}}*/

    function _bar($freq, $maxfreq, $total) {/*{{{*/
        $fact = floatval(($maxfreq > 40) ? 40/$maxfreq: 1);
		$niter = round($freq * $fact);
		$out = "";
		for ($i=0; $i < $niter; $i++) 
			$out .= "*";
		return $out.sprintf(" (%.1f%%)", $freq/$total * 100);

    }/*}}}*/

    function _clear() {/*{{{*/
        $this->_stats = null;
        $this->_statsMode = null;
        $this->_data = null;
        $this->_orig = array();
        $this->_bins = array();
    }/*}}}*/

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

    function _filterData() {
        $data = array();
        foreach ($this->_data as $val)
            if ($val < $this->_rangeLow || $val > $this->_rangeHigh)
                continue;
            else
                $data[] = $val;
        return $data;
    }
    
}/*}}}*/

// vim: ts=4:sw=4:et:
// vim6: fdl=0:

?>
