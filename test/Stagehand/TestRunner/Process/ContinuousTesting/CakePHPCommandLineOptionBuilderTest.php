<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP version 5.3
 *
 * Copyright (c) 2013 KUBO Atsuhiro <kubo@iteman.jp>,
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
 * @copyright  2013 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      File available since Release 3.6.0
 */

namespace Stagehand\TestRunner\Process\ContinuousTesting;

use Stagehand\TestRunner\Test\ComponentAwareTestCase;
use Stagehand\TestRunner\Core\Plugin\CakePHPPlugin;
use Stagehand\TestRunner\Core\ApplicationContext;

/**
 * @package    Stagehand_TestRunner
 * @copyright  2013 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 3.6.0
 */
class CakePHPCommandLineOptionBuilderTest extends ComponentAwareTestCase
{
    /**
     * @var array
     */
    private static $configurators;

    public static function setUpBeforeClass()
    {
        self::$configurators[] = function (ApplicationContext $applicationContext) {
            \Phake::when($applicationContext->createComponent('preparer'))->getCakePHPAppPath()->thenReturn('DIRECTORY');
        };
        self::$configurators[] = function (ApplicationContext $applicationContext) {
            \Phake::when($applicationContext->createComponent('preparer'))->getCakePHPCorePath()->thenReturn('DIRECTORY');;
        };
    }

    /**
     * @return string
     */
    protected function getPluginID()
    {
        return CakePHPPlugin::getPluginID();
    }

    protected function setUp()
    {
        parent::setUp();

        $this->setComponent('cakephp.preparer', \Phake::mock('Stagehand\TestRunner\Preparer\CakePHPPreparer'));
    }

    /**
     * @link http://redmine.piece-framework.com/issues/314
     *
     * @test
     * @dataProvider commandLineOptions
     * @param integer $configuratorIndex
     * @param array  $expectedBuiltOptions
     * @param array $shouldPreserve
     */
    public function buildsCommandLineOptions($configuratorIndex, array $expectedBuiltOptions,array $shouldPreserve)
    {
        call_user_func(self::$configurators[$configuratorIndex], $this->applicationContext);

        $builtOptions = array();
        $builtOptions = $this->createComponent('cakephp.command_line_option_builder')->build($builtOptions);

        for ($i = 0; $i < count($expectedBuiltOptions); ++$i) {
            $preserved = in_array($expectedBuiltOptions[$i], $builtOptions);
            $this->assertThat($preserved, $this->equalTo($shouldPreserve[$i]));
        }
    }

    /**
     * @return array
     */
    public function commandLineOptions()
    {
        $commandLineOptions = array(
            array(array('--cakephp-app-path=' . escapeshellarg('DIRECTORY')), array(true, true)),
            array(array('--cakephp-core-path=' . escapeshellarg('DIRECTORY')), array(true, true)),
        );

        return array_map(function (array $commandLineOption) {
            static $index = 0;
            array_unshift($commandLineOption, $index++);
            return $commandLineOption;
        }, $commandLineOptions);
    }
}

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
