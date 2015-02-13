<?php

namespace Xoeoro\HelperBundle\Tests;

use Xoeoro\HelperBundle\XoeoroHelperBundle;
/**
 * Xoeoro Helper bundle test.
 *
 * @author xoeoro <xoeoro@gmail.com>
 */
class XoeoroHelperBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testBundle()
    {
        $bundle = new XoeoroHelperBundle();
        $this->assertInstanceOf('Symfony\Component\HttpKernel\Bundle\Bundle', $bundle);
    }
}
