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

include_once "Math/Stats.php";

// constants for the selection of bins
define("HISTOGRAM_ALL_BINS", 1);
define("HISTOGRAM_MID_BINS", 2);
define("HISTOGRAM_LO_BINS", 3);
define("HISTOGRAM_HI_BINS", 4);

// histogram types
define("HISTOGRAM_SIMPLE", 1);
define("HISTOGRAM_CUMMULATIVE", 2);

/**
 * Class to calculate the histogram distribution of a numerical data set.
 * It can calculate a regular distribution or a cummulative distribution.
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
class Math_Histogram {/*{{{*/
    // properties /*{{{*/

    /**
     * The Math_Stats object
     * 
     * @access  private
     * @var object  Math_Stats
     * @see Math_Stats
     */
    var $_stats = null;
    /**
     * Mode for the calculation of statistics
     * 
     * @access  private
     * @var int one of STATS_BASIC or STATS_FULL
     * @see Math_Stats
     */
    var $_statsMode;
    /**
     * Array of bins
     * 
     * @access  private
     * @var array
     */
    var $_bins = array();
    /**
     * Number of bins to use in calculation
     *
     * @access  private
     * @var int
     */
    var $_nbins;
    /**
     * The lowest value to be used when generating the bins
     *
     * @access  private
     * @var float
     */
    var $_rangeLow;
    /**
     * The highest value to be used when generating the bins
     *
     * @access  private
     * @var float
     */
    var $_rangeHigh;
    /**
     * The data set after filtering using $this->_rangeHigh and
     * $this->_rangeLow
     *
     * @access  private
     * @var array
     * @see $_rangeLow
     * @see $_rangeHigh
     */
    var $_data = null;
    /**
     * The original data, count, etc.
     *
     * @access  private
     * @var array
     * @see $_data
     */
    var $_orig = array();

    /*}}}*/

    /**
     * Constructor
     *
     * @access  public
     * @param   optional    int $type   one of HISTOGRAM_SIMPLE or HISTOGRAM_CUMMULATIVE
     * @param   optional    int $nbins  number of bins to use
     * @param   optional    float   $rangeLow   lowest value to use for bin frequency calculation
     * @param   optional    float   $rangeHigh   highest value to use for bin frequency calculation
     * @return  object  Math_Histogram
     */
    function Math_Histogram($type=HISTOGRAM_SIMPLE,$nbins=-1, $rangeLow=null, $rangeHigh=null) {/*{{{*/
        $this->setType($type);
        $this->setBinOptions($nbins, $rangeLow, $rangeHigh);
    }/*}}}*/

    function setType($type) {/*{{{*/
        $this->_type = $type;
    }/*}}}*/
    
    function setBinOptions($nbins, $rangeLow, $rangeHigh) {/*{{{*/
        $this->_nbins = (is_int($nbins) && $nbins > 2) ? $nbins : 10;
        $this->_rangeLow = $rangeLow;
        $this->_rangeHigh = $rangeHigh;
    }/*}}}*/
    
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

    function calculate($statsMode=STATS_BASIC) {/*{{{*/
        $this->_stats = new Math_Stats();
        $this->_stats->setData($this->_data);
        $this->_statsMode = $statsMode;
        $delta = ($this->_rangeHigh - $this->_rangeLow) / $this->_nbins;
        $ndata = count($this->_data);
        $lastpos = 0;
        $cumm = 0;
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
                    if ($this->_data[$j] >= $loBin
                        && $this->_data[$j] <= $hiBin) {
                        $this->_bins[$i]["count"]++;
                        if ($this->_type == HISTOGRAM_CUMMULATIVE)
                            $cumm++;
                        continue;
                    } else {
                        $lastpos = $j;
                        break;
                    }
                } else {
                    if ($this->_data[$j] > $loBin
                        && $this->_data[$j] <= $hiBin) {
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
    }/*}}}*/

    function getHistogramInfo() {/*{{{*/
        if (!empty($this->_nbins))
            return array (
                        "type" => ($this->_type == HISTOGRAM_CUMMULATIVE) ?  
                                            "cummulative frequency" : "histogram",
                        "statistics" => $this->_stats->calc($this->_statsMode),
                        "bins" => $this->_bins,
                        "nbins" => $this->_nbins,
                        "range" => array(
                                    "low" => $this->_rangeLow,
                                    "high" => $this->_rangeHigh
                                    )
                    );
        else
            return PEAR::raiseError("histogram has not been calculated");
    }/*}}}*/

    function getOriginal() {/*{{{*/
        if (empty($this->_orig))
            return PEAR::raiseError("data has not been set");
        else
            return $this->_orig;
    }/*}}}*/

    function getStats() {/*{{{*/
        if (!empty($this->_nbins))
            return $this->_stats->calc($this->_statsMode);
        else
            return PEAR::raiseError("histogram has not been calculated");
    }/*}}}*/

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

    function printHistogram($mode = HISTOGRAM_HI_BINS) {/*{{{*/
        if (empty($this->_bins))
            return PEAR::raiseError("histogram has not been calculated");
        $out = ($this->_type == HISTOGRAM_CUMMULATIVE) ?  "Cummulative Frequency" : "Histogram";
        $out .= ", Number of bins: ".$this->_nbins."\n";
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
    
}/*}}}*/

// vim: ts=4:sw=4:et:
// vim6: fdl=0:

?>
