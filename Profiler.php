<?php
//
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2002 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Matthias Englert <Matthias.Englert@gmx.de>                  |
// | Based on code from: Sebastian Bergmann <sb@sebastian-bergmann.de>    |
// +----------------------------------------------------------------------+
//
// $Id$
//
/**
 * Benchmark::Profiler
 *
 * Purpose:
 *
 *     Timing Script Execution, Generating Profiling Information
 *
 * Example with automatic profiling start, stop, and output:
 *
 *     $profiler =& new Benchmark_Profiler(true);
 *     function myFunction() {
 *         $profiler->enterSection('myFunction');
 *         //do something
 *         $prfiler->leaveSection('myFunction');
 *         return;
 *     }
 *     //do something
 *     myFunction();
 *     //do more
 *
 *
 * Example without automatic profiling:
 *
 *     $profiler =& new Benchmark_Profiler();
 *     function myFunction() {
 *         $profiler->enterSection('myFunction');
 *         //do something
 *         $prfiler->leaveSection('myFunction');
 *         return;
 *     }
 *     $profiler->start();
 *     //do something
 *     myFunction();
 *     //do more
 *     $profiler->stop();
 *     $profiler->display();
 *
 * @author   Matthias Englert <Matthias.Englert@gmx.de>
 * @version  $Revision$
 * @access   public
 */

require_once 'PEAR.php';

class Benchmark_Profiler extends PEAR {

   /**
     * Contains the total ex. time of eache section
     *
     * @var    array
     * @access private
     */
    var $_sections = array();

    /**
     * Calling stack
     *
     * @var    array
     * @access private
     */
    var $_stack = array();

    /**
     * Notes how often a section was entered
     *
     * @var    array
     * @access private
     */
    var $_num_calls = array();

    /**
     * Notes for eache section how often it calls which section
     *
     * @var    array
     * @access private
     */
    var $_calls = array();

    /**
     * Notes for eache section how often it was called by which section
     *
     * @var    array
     * @access private
     */
    var $_callers = array();

    /**
     * Auto-start and stop profiler
     *
     * @var    boolean
     * @access private
     */
    var $_auto = false;

    /**
     * Max marker name length for non-html output
     *
     * @var    integer
     * @access private
     */
    var $_strlen_max = 0;

    /**
     * Constructor, starts profiling recording
     *
     * @access public
     */
    function Benchmark_Profiler($auto = false) {
        $this->PEAR();
        if ($auto) {
            $this->auto = $auto;
            $this->start();
        }
    }

    /**
     * Destructor, stops profiling recording
     *
     * @access private
     */
    function _Benchmark_Profiler() {
        if ($this->auto) {
            $this->stop();
            $this->display();
        }
    }

    /**
     * Return formatted profiling information.
     *
     * @see    display()
     * @access private
     */
    function _getOutput() {
        if (function_exists('version_compare') &&
            version_compare(phpversion(), '4.1', 'ge')) {
            $http = isset($_SERVER['SERVER_PROTOCOL']);
        } else {
            global $HTTP_SERVER_VARS;
            $http = isset($HTTP_SERVER_VARS['SERVER_PROTOCOL']);
        }
        if ($http) {
            $out = "<table border=1>\n";
            $out .=
                '<tr><td>&nbsp;</td><td align="center"><b>total ex. time</b></td>'.
                '<td align="center"><b>#calls</b></td><td align="center"><b>%</b></td>'.
                '<td align="center"><b>calls</b></td><td align="center"><b>callers</b></td></tr>'.
                "\n";
        } else {
            $dashes = $out =
                str_pad("\n", ($this->_strlen_max + 52), '-',
                        STR_PAD_LEFT);
            $out .= str_pad('section', $this->_strlen_max);
            $out .= str_pad("total ex time", 22);
            $out .= str_pad("#calls", 22);
            $out .= "perct\n";
            $out .= $dashes;
        }
        foreach($this->_sections as $name => $time) {
            $per = 100 * $time / $this->_sections["Global"];
            $calls_str = "";
            if ($this->_calls[$name]) {
                foreach($this->_calls[$name] as $key => $val) {
                    if ($calls_str)
                        $calls_str .= ", ";
                    $calls_str .= "$key ($val)";
                }
            }
            $callers_str = "";
            if ($this->_callers[$name]) {
                foreach($this->_callers[$name] as $key => $val) {
                    if ($callers_str)
                        $callers_str .= ", ";
                    $callers_str .= "$key ($val)";
                }
            }
            if ($http) {
                $out .=
                    "<tr><td><b>$name</b></td><td>$time</td><td>{$this->_num_calls[$name]}</td>".
                    "<td align=\"right\">".number_format($per, 2, '.', '')."%</td>\n";
                $out .= "<td>$calls_str</td><td>$callers_str</td></tr>";
            } else {
                $out .= str_pad($name, $this->_strlen_max, ' ');
                $out .= str_pad($time, 22);
                $out .= str_pad($this->_num_calls[$name], 22);
                $out .=
                str_pad(number_format($per, 2, '.', '')."%\n", 8, ' ',
                            STR_PAD_LEFT);
            }
        }
        $out .= "</table>";
        return $out;        
    }

    /**
     * Return formatted profiling information.
     *
     * @access public
     */
    function display() {
        print $this->_getOutput();
    }

    /**
     * Enter "Global" section.
     *
     * @see    enterSection(), stop()
     * @access public
     */
    function start() {
        $this->enterSection('Global');
    }

    /**
     * Leave "Global" section.
     *
     * @see    leaveSection(), start()
     * @access public
     */
    function stop() {
        $this->leaveSection('Global');
    }

    /**
     * Enter code section.
     *
     * @param  string  name of the code section
     * @see    start(), leaveSection()
     * @access public
     */
    function enterSection($name) {
        if (count($this->_stack)) {
            $this->_callers[$name][$this->_stack[count($this->_stack) - 1]["name"]]++;
            $this->_calls[$this->_stack[count($this->_stack) - 1]["name"]][$name]++;
        }
        $this->_num_calls[$name]++;
        $microtime = explode(" ", microtime());
        $microtime = $microtime[1].substr($microtime[0], 1);
        array_push($this->_stack,
                   array("name" => $name, "time" => $microtime));
    }

    /**
     * Leave code section.
     *
     * @param  string  name of the marker to be set
     * @see     stop(), enterSection()
     * @access public
     */
    function leaveSection($name) {
        $microtime = explode(" ", microtime());
        $microtime = $microtime[1].substr($microtime[0], 1);
        $x = array_pop($this->_stack);
        if ($x["name"] != $name) {
            $this->raiseError("reached end of section $name but expecting end of ".
                               $x["name"]."\n",null,PEAR_ERROR_DIE);
        }
        $this->_sections[$name] += $microtime - $x["time"];
    }

}

?>