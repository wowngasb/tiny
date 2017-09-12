<?php

namespace Tiny\Tests;

use phpmock\phpunit\PHPMock;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/10 0010
 * Time: 11:04
 */
class BuiltinMockTest extends BaseNothingTest
{

    use PHPMock;

    public function testTime()
    {
        $time = $this->getFunctionMock(__NAMESPACE__, "time");
        $time->expects($this->once())->willReturn(3);

        $this->assertEquals(3, time());
    }

    public function testExec()
    {
        $exec = $this->getFunctionMock(__NAMESPACE__, "exec");
        $exec->expects($this->once())->willReturnCallback(
            function ($command, &$output, &$return_var) {
                $this->assertEquals("foo", $command);
                $output = ["failure"];
                $return_var = 1;
            }
        );

        exec("foo", $output, $return_var);
        $this->assertEquals(["failure"], $output);
        $this->assertEquals(1, $return_var);
    }
}