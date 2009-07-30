<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP version 5
 *
 * Copyright (c) 2007-2009 KUBO Atsuhiro <kubo@iteman.jp>,
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    Stagehand_TestRunner
 * @copyright  2007-2009 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      File available since Release 2.1.0
 */

require_once 'Stagehand/TestRunner/Exception.php';
require_once 'Stagehand/DirectoryScanner.php';

// {{{ Stagehand_TestRunner_Collector

/**
 * The base class for test collectors.
 *
 * @package    Stagehand_TestRunner
 * @copyright  2007-2009 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 2.1.0
 */
abstract class Stagehand_TestRunner_Collector
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access protected
     */

    protected $excludePattern;
    protected $baseClass;
    protected $suffix;
    protected $includePattern;
    protected $testsOnlySpecified = false;
    protected $config;

    /**#@-*/

    /**#@+
     * @access private
     */

    private $testCases = array();

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ __construct()

    /**
     * Initializes some properties of an instance.
     *
     * @param Stagehand_TestRunner_Config $config
     */
    public function __construct(Stagehand_TestRunner_Config $config)
    {
        $this->config = $config;
    }

    // }}}
    // {{{ collect()

    /**
     * Collects tests.
     *
     * @return mixed
     * @throws Stagehand_TestRunner_Exception
     */
    public function collect()
    {
        if (!file_exists($this->config->targetPath)) {
            if (preg_match("/{$this->suffix}\.php\$/", $this->config->targetPath)) {
                throw new Stagehand_TestRunner_Exception("The directory or file [ {$this->config->targetPath} ] is not found.");
            }

            $this->config->targetPath = "{$this->config->targetPath}{$this->suffix}.php";
        }

        $absoluteTargetPath = realpath($this->config->targetPath);
        if ($absoluteTargetPath === false) {
            throw new Stagehand_TestRunner_Exception("The directory or file [ {$this->config->targetPath} ] is not found.");
        }

        if (is_dir($absoluteTargetPath)) {
            $directoryScanner = new Stagehand_DirectoryScanner(array($this, 'collectTestCases'));
            $directoryScanner->setRecursivelyScans($this->config->recursivelyScans);
            $directoryScanner->scan($absoluteTargetPath);
        } else {
            if (count($this->config->testMethods)) {
                $this->testsOnlySpecified = true;
            }

            $this->collectTestCasesFromFile($absoluteTargetPath);
        }

        return $this->buildTestSuite();
    }

    // }}}
    // {{{ collectTestCases()

    /**
     * Collects all test cases included in the specified directory.
     *
     * @param string $element
     */
    public function collectTestCases($element)
    {
        if (is_dir($element)) {
            return;
        }

        $this->collectTestCasesFromFile($element);
    }

    /**#@-*/

    /**#@+
     * @access protected
     */

    // }}}
    // {{{ createTestSuite()

    /**
     * Creates a test suite object.
     *
     * @return mixed
     */
    abstract protected function createTestSuite();

    // }}}
    // {{{ doBuildTestSuite()

    /**
     * Aggregates a test suite object to an aggregate test suite object.
     *
     * @param mixed $aggregateSuite
     * @param mixed $suite
     * @abstract
     */
    abstract protected function doBuildTestSuite($aggregateSuite, $suite);

    // }}}
    // {{{ addTestCase()

    /**
     * Adds a test case to a test suite object.
     *
     * @param mixed  $suite
     * @param string $testCase
     * @abstract
     */
    abstract protected function addTestCase($suite, $testCase);

    /**#@-*/

    /**#@+
     * @access private
     */

    // }}}
    // {{{ createTestSuiteFromTestCases()

    /**
     * Creates a test suite object that contains all of the test cases in the
     * directory.
     *
     * @return mixed
     */
    private function createTestSuiteFromTestCases()
    {
        $suite = $this->createTestSuite();
        foreach ($this->testCases as $testCase) {
            $this->addTestCase($suite, $testCase);
        }

        return $suite;
    }

    // }}}
    // {{{ buildTestSuite()

    /**
     * Builds a test suite object.
     *
     * @return mixed
     */
    private function buildTestSuite()
    {
        $suite = $this->createTestSuite();
        $this->doBuildTestSuite($suite, $this->createTestSuiteFromTestCases());
        return $suite;
    }

    // }}}
    // {{{ collectTestCasesFromFile()

    /**
     * Collects all test cases included in the given file.
     *
     * @param string $file
     */
    private function collectTestCasesFromFile($file)
    {
        if (!preg_match("/{$this->suffix}\.php\$/", $file)) {
            return;
        }

        $currentClasses = get_declared_classes();

        if (!include_once($file)) {
            return;
        }

        $newClasses = array_values(array_diff(get_declared_classes(), $currentClasses));
        for ($i = 0, $count = count($newClasses); $i < $count; ++$i) {
            if (!is_subclass_of($newClasses[$i], $this->baseClass)) {
                continue;
            }

            if (!is_null($this->excludePattern)
                && preg_match("/{$this->excludePattern}/", $newClasses[$i])
                ) {
                continue;
            }

            if (!is_null($this->includePattern)
                && !preg_match("/{$this->includePattern}/", $newClasses[$i])
                ) {
                continue;
            }

            $this->testCases[] = $newClasses[$i];
        }
    }

    /**#@-*/

    // }}}
}

// }}}

/*
 * Local Variables:
 * mode: php
 * coding: iso-8859-1
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * indent-tabs-mode: nil
 * End:
 */