<?php
/**
 * timer_example.php
 *
 * PHP version 4
 *
 * Copyright (c) 2001-2006 Sebastian Bergmann <sb@sebastian-bergmann.de>. 
 * 
 * This source file is subject to the New BSD license, That is bundled    
 * with this package in the file LICENSE, and is available through        
 * the world-wide-web at                                                  
 * http://www.opensource.org/licenses/bsd-license.php                     
 * If you did not receive a copy of the new BSDlicense and are unable     
 * to obtain it through the world-wide-web, please send a note to         
 * license@php.net so we can mail you a copy immediately.                 
 *
 * @category  Benchmarking
 * @package   Benchmark
 * @author    Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright 2002-2005 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license   http://www.php.net/license/3_0.txt The PHP License, Version 3.0
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/Benchmark
 */

require 'Benchmark/Timer.php';

/**
 * Wait
 *
 * @param int $amount Amount to wait
 *
 * @return void
 */
function wait($amount) 
{
    for ($i=0; $i < $amount; $i++) {
        for ($i=0; $i < 100; $i++) {
        }
    }
}

// Pass the param "true" to constructor to automatically display the results
$timer = new Benchmark_Timer();
$timer->start();
wait(10);
$timer->setMarker('Mark1');
echo "Elapsed time between Start and Mark1: " .
      $timer->timeElapsed('Start', 'Mark1') . "\n";
wait(50);
$timer->stop();
$timer->display();
