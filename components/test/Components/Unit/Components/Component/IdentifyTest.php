<?php
/**
 * Test the identification of the selected component.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Components
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Components
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../Autoload.php';

/**
 * Test the identification of the selected component.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Horde
 * @package    Components
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Components
 */
class Components_Unit_Components_Component_IdentifyTest
extends Components_TestCase
{
    public function tearDown()
    {
        if (isset($this->oldcwd) && $this->oldcwd != getcwd()) {
            chdir($this->oldcwd);
        }
    }

    /**
     * @expectedException Components_Exception
     */
    public function testHelp()
    {
        $this->_initIdentify(array('help'));
        $this->config->getComponent();
    }

    /**
     * @expectedException Components_Exception
     */
    public function testNoArgument()
    {
        $this->oldcwd = getcwd();
        chdir(dirname(__FILE__) . '/../../../fixture/');
        $this->_initIdentify(array());
        chdir($this->oldcwd);
    }

    public function testWithPackageXml()
    {
        $this->_initIdentify(
            array(dirname(__FILE__) . '/../../../fixture/framework/Install/package.xml')
        );
        $this->assertInstanceOf(
            'Components_Component_Source',
            $this->config->getComponent()
        );
    }

    public function testWithPackageXmlDirectory()
    {
        $this->_initIdentify(
            array(dirname(__FILE__) . '/../../../fixture/framework/Install')
        );
        $this->assertInstanceOf(
            'Components_Component_Source',
            $this->config->getComponent()
        );
    }

    public function testWithPackageXmlDirectoryAndSlash()
    {
        $this->_initIdentify(
            array(dirname(__FILE__) . '/../../../fixture/framework/Install/')
        );
        $this->assertInstanceOf(
            'Components_Component_Source',
            $this->config->getComponent()
        );
    }

    public function testWithinComponent()
    {
        $this->oldcwd = getcwd();
        chdir(dirname(__FILE__) . '/../../../fixture/framework/Install');
        $this->_initIdentify(array('test'));
        chdir($this->oldcwd);
        $this->assertInstanceOf(
            'Components_Component_Source',
            $this->config->getComponent()
        );
    }

    public function testWithinComponentNoAction()
    {
        $this->oldcwd = getcwd();
        chdir(dirname(__FILE__) . '/../../../fixture/framework/Install');
        $this->_initIdentify(array());
        chdir($this->oldcwd);
        $this->assertInstanceOf(
            'Components_Component_Source',
            $this->config->getComponent()
        );
    }

    /**
     * @expectedException Components_Exception
     */
    public function testWithoutValidComponent()
    {
        $this->_initIdentify(
            array(dirname(__FILE__) . '/../../../fixture/DOESNOTEXIST')
        );
    }

    public function testWithoutValidComponentButRemote()
    {
        $dependencies = new Components_Dependencies_Injector();
        $mock = $this->getMock('Horde_Pear_Remote');
        $mock->expects($this->once())
            ->method('getLatestDownloadUri')
            ->with('Horde_Core')
            ->will(
                $this->returnValue(
                    'http://pear.horde.org/get/Horde_Core-1.0.0.tgz'
                )
            );
        $dependencies->setInstance(
            'Horde_Pear_Remote',
            $mock
        );
        $this->_initIdentify(
            array('Horde_Core'), array('allow_remote' => true), $dependencies
        );
    }

    private function _initIdentify(
        $arguments, $options = array(), $dependencies = null
    )
    {
        if ($dependencies === null) {
            $dependencies = new Components_Dependencies_Injector();
        }
        $this->config = new Components_Stub_Config($arguments, $options);
        $dependencies->initConfig($this->config);
        $identify = new Components_Component_Identify(
            $this->config,
            array(
                'list' => array('test'),
                'missing_argument' => array('help')
            ),
            $dependencies
        );
        $identify->setComponentInConfiguration();
    }

}