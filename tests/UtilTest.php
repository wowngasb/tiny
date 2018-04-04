<?php

namespace Tiny\Tests;

use phpmock\phpunit\PHPMock;
use PHPUnit_Framework_Assert;
use Tiny\Util;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/10 0010
 * Time: 10:25
 */
class UtilTest extends BaseNothingTest
{
    public static $_class = '';

    use PHPMock;

    public function test_split_seq()
    {
        Util::split_seq('12345678');
        $_func = self::_buildFunc(__METHOD__);
        $test_data = [
            [
                'args' => [
                    '12345678',
                ],
                'return' => '12,345,678'
            ], [
                'args' => [
                    12345678,
                ],
                'return' => '12,345,678'
            ], [
                'args' => [
                    123,
                ],
                'return' => '123'
            ], [
                'args' => [
                    'abcdefg',
                ],
                'return' => 'a,bcd,efg'
            ], [
                'args' => [
                    'abcdefg', 3, ' '
                ],
                'return' => 'a bcd efg'
            ], [
                'args' => [
                    'abcdefg', 3, ''
                ],
                'return' => 'abcdefg'
            ], [
                'args' => [
                    '中华人民共和国',
                ],
                'return' => '中,华人民,共和国'
            ], [
                'args' => [
                    '12345678', 4
                ],
                'return' => '1234,5678'
            ],
        ];
        foreach ($test_data as $test_item) {
            $item = $test_item['args'];
            $tmp = call_user_func_array([static::$_class, $_func], $item);
            PHPUnit_Framework_Assert::assertEquals($test_item['return'], $tmp, static::_buildMsg($_func, $item, $tmp));
        }
    }

    public function test_deep_merge()
    {
        Util::deep_merge(['a' => 1], ['b' => 2]);
        $_func = self::_buildFunc(__METHOD__);
        $test_data = [
            [
                'args' => [
                    ['a' => 1], ['b' => 2]
                ],
                'return' => ['a' => 1, 'b' => 2]
            ], [
                'args' => [
                    ['a' => 1], ['a' => 2]
                ],
                'return' => ['a' => 2]
            ], [
                'args' => [
                    ['a' => ['c' => 1]], ['b' => 2]
                ],
                'return' => ['a' => ['c' => 1], 'b' => 2]
            ], [
                'args' => [
                    ['a' => ['c' => 1]], ['a' => ['d' => 1]]
                ],
                'return' => ['a' => ['c' => 1, 'd' => 1]]
            ], [
                'args' => [
                    ['a' => ['c' => 1]], ['a' => ['c' => 2]]
                ],
                'return' => ['a' => ['c' => 2]]
            ],
        ];
        foreach ($test_data as $test_item) {
            $item = $test_item['args'];
            $tmp = call_user_func_array([static::$_class, $_func], $item);
            PHPUnit_Framework_Assert::assertEquals($test_item['return'], $tmp, static::_buildMsg($_func, $item, $tmp));
        }
    }

    public function test_file_name()
    {
        Util::file_name('http://g.cn/a.html');
        $_func = self::_buildFunc(__METHOD__);
        $test_data = [
            [
                'args' => ['http://g.cn/a.html'],
                'return' => 'a.html'
            ], [
                'args' => ['http://g.cn/a'],
                'return' => 'a'
            ], [
                'args' => ['http://g.cn/a.html?b=1'],
                'return' => 'a.html'
            ], [
                'args' => ['http://g.cn/a.html#c=2'],
                'return' => 'a.html'
            ], [
                'args' => ['D:\g\cn\a.html'],
                'return' => 'a.html'
            ], [
                'args' => ['D:\g\cn\a'],
                'return' => 'a'
            ], [
                'args' => ['D:\g\cn\a.html?b=1'],
                'return' => 'a.html'
            ], [
                'args' => ['D:\g\cn\a.html#c=2'],
                'return' => 'a.html'
            ],
        ];
        foreach ($test_data as $test_item) {
            $item = $test_item['args'];
            $tmp = call_user_func_array([static::$_class, $_func], $item);
            PHPUnit_Framework_Assert::assertEquals($test_item['return'], $tmp, static::_buildMsg($_func, $item, $tmp));
        }
    }

    ##########################
    ######## DSL相关 ########
    ##########################

    public function test_safe_str()
    {
        Util::safe_str('abc');
        $_func = self::_buildFunc(__METHOD__);
        $test_data = [
            [
                'args' => [''],
                'return' => ''
            ], [
                'args' => ['test#a=1#bACW=2'],
                'return' => 'testa1bACW2'
            ], [
                'args' => ['test%$21"da-adw_s'],
                'return' => 'test21da-adw_s'
            ], [
                'args' => ['中文#a=1#b=2#d=s'],
                'return' => 'a1b2ds'
            ],
        ];
        foreach ($test_data as $test_item) {
            $item = $test_item['args'];
            $tmp = call_user_func_array([static::$_class, $_func], $item);
            PHPUnit_Framework_Assert::assertEquals($test_item['return'], $tmp, static::_buildMsg($_func, $item, $tmp));
        }
    }

    public function test_dsl()
    {
        Util::dsl('abc');
        $_func = self::_buildFunc(__METHOD__);
        $test_data = [
            [
                'args' => [''],
                'return' => ['base' => '', 'args' => []]
            ], [
                'args' => ['test#a=1#b=2'],
                'return' => ['base' => 'test', 'args' => ['a' => 1, 'b' => 2]]
            ], [
                'args' => ['test#a=1#b=2#d=s'],
                'return' => ['base' => 'test', 'args' => ['a' => 1, 'b' => 2, 'd' => 's']]
            ], [
                'args' => ['中文#a=1#b=2#d=s'],
                'return' => ['base' => '中文', 'args' => ['a' => 1, 'b' => 2, 'd' => 's']]
            ],
        ];
        foreach ($test_data as $test_item) {
            $item = $test_item['args'];
            $tmp = call_user_func_array([static::$_class, $_func], $item);
            PHPUnit_Framework_Assert::assertEquals($test_item['return'], $tmp, static::_buildMsg($_func, $item, $tmp));
        }
    }

    ##########################
    ######## 数组处理 ########
    ##########################

    public function __construct($name = '', array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        static::$_class = Util::_class();
    }

    public function test_filter_keys()
    {
        $_func = self::_buildFunc(__METHOD__);
        $test_data = [
            [
                'args' => [[], ['a', 'b', 'c'], 1],
                'return' => ['a' => 1, 'b' => 1, 'c' => 1]
            ], [
                'args' => [['a' => 2, 'b' => 2], ['a', 'b', 'c'], 1],
                'return' => ['a' => 2, 'b' => 2, 'c' => 1]
            ], [
                'args' => [['a' => 2, 'b' => 2], [], 1],
                'return' => []
            ], [
                'args' => [['a' => 2, 'b' => 2], ['a', 'b', 'c']],
                'return' => ['a' => 2, 'b' => 2, 'c' => '']
            ], [
                'args' => [[], []],
                'return' => []
            ],
        ];
        foreach ($test_data as $test_item) {
            $item = $test_item['args'];
            $tmp = call_user_func_array([static::$_class, $_func], $item);
            PHPUnit_Framework_Assert::assertEquals($test_item['return'], $tmp, static::_buildMsg($_func, $item, $tmp));
        }
    }

    public function test_v()
    {
        $_func = self::_buildFunc(__METHOD__);
        $test_data = [
            [
                'args' => [['a' => 2, 'b' => 2], 'c'],
                'return' => null
            ], [
                'args' => [['a' => 2, 'b' => 2], 'b'],
                'return' => 2
            ], [
                'args' => [['a' => 2, 'b' => 2], 'c', 3],
                'return' => 3
            ],
        ];
        foreach ($test_data as $test_item) {
            $item = $test_item['args'];
            $tmp = call_user_func_array([static::$_class, $_func], $item);
            PHPUnit_Framework_Assert::assertEquals($test_item['return'], $tmp, static::_buildMsg($_func, $item, $tmp));
        }
    }

    ##########################
    ######## 时间处理 ########
    ##########################

    public function test_add_month()
    {
        $_func = self::_buildFunc(__METHOD__);
        $test_data = [
            [
                'args' => ['2017-01-21 12:34:56', 1],
                'return' => '2017-02-21 12:34:56'
            ], [
                'args' => ['2017-01-31 12:34:56', 1],
                'return' => '2017-02-28 12:34:56'
            ], [
                'args' => ['2016-01-31 12:34:56', 1],
                'return' => '2016-02-29 12:34:56'
            ], [
                'args' => ['2016-02-29 12:34:56', 12],
                'return' => '2017-02-28 12:34:56'
            ], [
                'args' => ['2016-02-29 12:34:56', 0],
                'return' => '2016-02-29 12:34:56'
            ], [
                'args' => ['2016-02-29 12:34:56', -1],
                'return' => '2016-02-29 12:34:56'
            ], [
                'args' => ['2016-02-29 12:34:56', 48000],
                'return' => '6016-02-29 12:34:56'
            ],
        ];
        foreach ($test_data as $test_item) {
            $item = $test_item['args'];
            $tmp = call_user_func_array([static::$_class, $_func], $item);
            PHPUnit_Framework_Assert::assertEquals($test_item['return'], $tmp, static::_buildMsg($_func, $item, $tmp));
        }
    }

    public function test_diff_time()
    {
        $_func = self::_buildFunc(__METHOD__);
        $test_data = [
            [
                'args' => [1502435519, 1502435519 + 60 + 1],
                'return' => ["day" => 0, "hour" => 0, "min" => 1, "sec" => 1]
            ], [
                'args' => [1502435519, 1502435519 + 3600 + 60 + 1],

                'return' => ["day" => 0, "hour" => 1, "min" => 1, "sec" => 1]
            ], [
                'args' => [1502435519, 1502435519 + 24 * 3600 + 3600 + 60 + 1],
                'return' => ["day" => 1, "hour" => 1, "min" => 1, "sec" => 1]
            ],
        ];
        foreach ($test_data as $test_item) {
            $item = $test_item['args'];
            $tmp = call_user_func_array([static::$_class, $_func], $item);
            PHPUnit_Framework_Assert::assertEquals($test_item['return'], $tmp, static::_buildMsg($_func, $item, $tmp));
        }

        for ($i = 0; $i < 100; $i++) {
            list($day, $hour, $min, $sec) = [rand(0, 10000), rand(0, 23), rand(0, 59), rand(0, 59)];
            $test_i = time();
            $tmp = $test_i + $day * 24 * 3600 + $hour * 3600 + $min * 60 + $sec * 1;
            $test_o = Util::diff_time($test_i, $tmp);
            $test_o2 = Util::diff_time($tmp, $test_i);
            $test_r = ["day" => $day, "hour" => $hour, "min" => $min, "sec" => $sec];
            PHPUnit_Framework_Assert::assertEquals($test_r, $test_o);
            PHPUnit_Framework_Assert::assertEquals($test_r, $test_o2);
        }
    }

    public function test_str_time()
    {
        $_func = self::_buildFunc(__METHOD__);
        $test_data = [
            [
                'args' => [1502435519, 1502435519 + 60 + 1],
                'return' => "1分1秒"
            ], [
                'args' => [1502435519, 1502435519 + 3600 + 60 + 1],

                'return' => "1小时1分1秒"
            ], [
                'args' => [1502435519, 1502435519 + 24 * 3600 + 3600 + 60 + 1],
                'return' => "25小时1分1秒"
            ],
        ];
        foreach ($test_data as $test_item) {
            $item = $test_item['args'];
            $tmp = call_user_func_array([static::$_class, $_func], $item);
            PHPUnit_Framework_Assert::assertEquals($test_item['return'], $tmp, static::_buildMsg($_func, $item, $tmp));
        }

        for ($i = 0; $i < 100; $i++) {
            list($hour, $min, $sec) = [rand(0, 23), rand(0, 59), rand(0, 59)];
            $test_i = time();
            $tmp = $test_i + $hour * 3600 + $min * 60 + $sec * 1;
            $test_o = Util::str_time($test_i, $tmp);
            $test_o2 = Util::str_time($tmp, $test_i);
            $test_r = $hour > 0 ? "{$hour}小时" : '';
            $test_r .= $min > 0 ? "{$min}分" : '';
            $test_r .= $sec > 0 ? "{$sec}秒" : '';
            PHPUnit_Framework_Assert::assertEquals($test_r, $test_o);
            PHPUnit_Framework_Assert::assertEquals($test_r, $test_o2);
        }
    }

    ##########################
    ######## 字符串处理 ########
    ##########################

    public function test_pass_filter()
    {
        $_func = self::_buildFunc(__METHOD__);
        $test_data = [
            [
                'args' => ['abc,def,abcd', 'abc'],
                'return' => false
            ], [
                'args' => ['abc,def,abcd', 'aaa|def'],

                'return' => false
            ], [
                'args' => ['abc,def,abcd', 'aaa,def,', ','],
                'return' => false
            ], [
                'args' => ['abc,def,abcd', 'abcde'],
                'return' => true
            ], [
                'args' => ['abc,def,abcd', 'abcde  |  | defa'],
                'return' => true
            ],
        ];
        foreach ($test_data as $test_item) {
            $item = $test_item['args'];
            $tmp = call_user_func_array([static::$_class, $_func], $item);
            PHPUnit_Framework_Assert::assertEquals($test_item['return'], $tmp, static::_buildMsg($_func, $item, $tmp));
        }


    }

    public function test_byte2size()
    {
        $_func = self::_buildFunc(__METHOD__);
        $test_data = [
            [
                'args' => [1024],
                'return' => '1K'
            ], [
                'args' => [1024 + 512, 'K'],

                'return' => '1.50M'
            ], [
                'args' => [1024 + 512, 'K', '', 3],
                'return' => '1.500M'
            ], [
                'args' => [(1024 + 512) * 10, 'K', 'G', 3],
                'return' => '0.015G'
            ], [
                'args' => [0.0125, 'G'],
                'return' => '12.80M'
            ],
        ];
        foreach ($test_data as $test_item) {
            $item = $test_item['args'];
            $tmp = call_user_func_array([static::$_class, $_func], $item);
            PHPUnit_Framework_Assert::assertEquals($test_item['return'], $tmp, static::_buildMsg($_func, $item, $tmp));
        }
    }

    public function test_anonymous_telephone()
    {
        $_func = self::_buildFunc(__METHOD__);
        $test_data = [
            [
                'args' => ['15099991234'],
                'return' => '150****1234'
            ], [
                'args' => ['150999912345678'],

                'return' => '150********5678'
            ], [
                'args' => ['15099991234', 2, 3],
                'return' => '15******234'
            ], [
                'args' => ['1509999'],
                'return' => '1509999'
            ],
        ];
        foreach ($test_data as $test_item) {
            $item = $test_item['args'];
            $tmp = call_user_func_array([static::$_class, $_func], $item);
            PHPUnit_Framework_Assert::assertEquals($test_item['return'], $tmp, static::_buildMsg($_func, $item, $tmp));
        }
    }

    public function test_anonymous_email()
    {
        $_func = self::_buildFunc(__METHOD__);
        $test_data = [
            [
                'args' => ['one20170101@test.com'],
                'return' => 'one********@test.com'
            ], [
                'args' => ['one20170101@test.com', 4],

                'return' => 'one2*******@test.com'
            ], [
                'args' => ['one@test.com'],
                'return' => 'one@test.com'
            ], [
                'args' => ['one20170101#test.com'],
                'return' => 'one20170101#test.com'
            ],
        ];
        foreach ($test_data as $test_item) {
            $item = $test_item['args'];
            $tmp = call_user_func_array([static::$_class, $_func], $item);
            PHPUnit_Framework_Assert::assertEquals($test_item['return'], $tmp, static::_buildMsg($_func, $item, $tmp));
        }
    }


    // PHP中 == 运算符的安全问题
    // http://blog.jobbole.com/104107/

    public function test_str_cmp()
    {
        $_func = self::_buildFunc(__METHOD__);
        $test_data = [
            [
                'args' => ["0xff", "255"]
            ], [
                'args' => ["1.00000000000000001", "0.1e1"]
            ], [
                'args' => ["+1", "0.1e1"]
            ], [
                'args' => ["1e0", "0.1e1"]
            ], [
                'args' => ["-0e10", "0"]
            ], [
                'args' => ["1000", "0x3e8"]
            ], [
                'args' => ["1234", "  	1234"]
            ], [
                'args' => [md5('c!C123449477'), md5('d!D206687225')]
            ], [
                'args' => [md5('e!E160399390'), md5('f!F24413812')]
            ], [
                'args' => [sha1('aA1537368460!'), sha1('fF3560631665!')]
            ],
        ];

        foreach ($test_data as $test_item) {
            $item = $test_item['args'];
            $tmp = ($item[0] == $item[1]);
            PHPUnit_Framework_Assert::assertTrue($tmp, static::_buildMsg($_func, $item, $tmp));
            $tmp = call_user_func_array([static::$_class, $_func], $item);
            PHPUnit_Framework_Assert::assertFalse($tmp, static::_buildMsg($_func, $item, $tmp));
        }

        $test_data = [
            [
                'args' => ['abc', 'abc'],
                'return' => true
            ], [
                'args' => ['abc123', 'abc123'],
                'return' => true
            ], [
                'args' => ['20170101', 20170101],
                'return' => true
            ], [
                'args' => ['abc', 'Abc'],
                'return' => false
            ], [
                'args' => ['abc', 'abc '],
                'return' => false
            ], [
                'args' => ['abc', ' abc'],
                'return' => false
            ], [
                'args' => ['abc', 'ABC'],
                'return' => false
            ],
        ];
        foreach ($test_data as $test_item) {
            $item = $test_item['args'];
            $tmp = call_user_func_array([static::$_class, $_func], $item);
            PHPUnit_Framework_Assert::assertEquals($test_item['return'], $tmp, static::_buildMsg($_func, $item, $tmp));
        }
    }

    public function test_stri_cmp()
    {
        $test_data = [
            ['str1' => 'abc', 'str2' => 'ABC', 'return' => true],
            ['str1' => 'abc', 'str2' => 'ABc', 'return' => true],
            ['str1' => '20170101', 'str2' => 20170101, 'return' => true],
            ['str1' => 'abc', 'str2' => 'Abc ', 'return' => false],
            ['str1' => 'abc1', 'str2' => 'ABC', 'return' => false],
        ];
        foreach ($test_data as $item) {
            $tmp = Util::stri_cmp($item['str1'], $item['str2']);
            PHPUnit_Framework_Assert::assertEquals($item['return'], $tmp);
        }
    }

    public function test_str_startwith()
    {
        $_func = self::_buildFunc(__METHOD__);
        $test_data = [
            [
                'args' => ['', ''],
                'return' => true
            ], [
                'args' => ['one20170101@test.com', 'one'],
                'return' => true
            ], [
                'args' => ['one@test.com', 'one1'],
                'return' => false
            ], [
                'args' => ['one20170101#test.com', 'onE'],
                'return' => false
            ],
        ];
        foreach ($test_data as $test_item) {
            $item = $test_item['args'];
            $tmp = call_user_func_array([static::$_class, $_func], $item);
            PHPUnit_Framework_Assert::assertEquals($test_item['return'], $tmp, static::_buildMsg($_func, $item, $tmp));
        }
    }

    public function test_str_endwith()
    {
        $_func = self::_buildFunc(__METHOD__);
        $test_data = [
            [
                'args' => ['', ''],
                'return' => true
            ], [
                'args' => ['one20170101@test.com', 'com'],
                'return' => true
            ], [
                'args' => ['one@test.com', 'com1'],
                'return' => false
            ], [
                'args' => ['one20170101#test.com', 'coM'],
                'return' => false
            ],
        ];
        foreach ($test_data as $test_item) {
            $item = $test_item['args'];
            $tmp = call_user_func_array([static::$_class, $_func], $item);
            PHPUnit_Framework_Assert::assertEquals($test_item['return'], $tmp, static::_buildMsg($_func, $item, $tmp));
        }
    }

    public function test_stri_startwith()
    {
        $_func = self::_buildFunc(__METHOD__);
        $test_data = [
            [
                'args' => ['', ''],
                'return' => true
            ], [
                'args' => ['one20170101@test.com', 'one'],
                'return' => true
            ], [
                'args' => ['one@test.com', 'one1'],
                'return' => false
            ], [
                'args' => ['one20170101#test.com', 'onE'],
                'return' => true
            ],
        ];
        foreach ($test_data as $test_item) {
            $item = $test_item['args'];
            $tmp = call_user_func_array([static::$_class, $_func], $item);
            PHPUnit_Framework_Assert::assertEquals($test_item['return'], $tmp, static::_buildMsg($_func, $item, $tmp));
        }
    }

    public function test_stri_endwith()
    {
        $_func = self::_buildFunc(__METHOD__);
        $test_data = [
            [
                'args' => ['', ''],
                'return' => true
            ], [
                'args' => ['one20170101@test.com', 'com'],
                'return' => true
            ], [
                'args' => ['one@test.com', 'com1'],
                'return' => false
            ], [
                'args' => ['one20170101#test.com', 'coM'],
                'return' => true
            ],
        ];
        foreach ($test_data as $test_item) {
            $item = $test_item['args'];
            $tmp = call_user_func_array([static::$_class, $_func], $item);
            PHPUnit_Framework_Assert::assertEquals($test_item['return'], $tmp, static::_buildMsg($_func, $item, $tmp));
        }
    }

    ##########################
    ######## 中文处理 ########
    ##########################

    public function test_utf8_strlen()
    {
        $_func = self::_buildFunc(__METHOD__);
        $test_data = [
            [
                'args' => ['utf8长度'],
                'return' => 6
            ], [
                'args' => ['长度'],
                'return' => 2
            ], [
                'args' => ['utf8'],
                'return' => 4
            ], [
                'args' => [''],
                'return' => 0
            ], [
                'args' => [' '],
                'return' => 1
            ],
        ];
        foreach ($test_data as $test_item) {
            $item = $test_item['args'];
            $tmp = call_user_func_array([static::$_class, $_func], $item);
            PHPUnit_Framework_Assert::assertEquals($test_item['return'], $tmp, static::_buildMsg($_func, $item, $tmp));
        }
    }

    public function test_utf8_gbk_able()
    {
        $_func = self::_buildFunc(__METHOD__);
        $test_data = [
            [
                'args' => ['你我他'],
                'return' => '你我他'
            ], [
                'args' => ['abc'],
                'return' => 'abc'
            ],
        ];
        foreach ($test_data as $test_item) {
            $item = $test_item['args'];
            $tmp = call_user_func_array([static::$_class, $_func], $item);
            PHPUnit_Framework_Assert::assertEquals($test_item['return'], $tmp, static::_buildMsg($_func, $item, $tmp));
        }
    }

    public function test_unicode_decode()
    {
        $_func = self::_buildFunc(__METHOD__);
        $test_data = [
            [
                'args' => ['\u89e3\u51bb\u8d26\u53f7\u6210\u529f'],
                'return' => '解冻账号成功'
            ], [
                'args' => ['abc'],
                'return' => 'abc'
            ],
        ];
        foreach ($test_data as $test_item) {
            $item = $test_item['args'];
            $tmp = call_user_func_array([static::$_class, $_func], $item);
            PHPUnit_Framework_Assert::assertEquals($test_item['return'], $tmp, static::_buildMsg($_func, $item, $tmp));
        }

    }

    public function test_unicode_decode_char()
    {

        $_func = self::_buildFunc(__METHOD__);
        $test_data = [
            [
                'args' => ['\u89e3'],
                'return' => '解'
            ],
        ];
        foreach ($test_data as $test_item) {
            $item = $test_item['args'];
            $tmp = call_user_func_array([static::$_class, $_func], $item);
            PHPUnit_Framework_Assert::assertEquals($test_item['return'], $tmp, static::_buildMsg($_func, $item, $tmp));
        }

    }

    ##########################
    ######## 编码相关 ########
    ##########################

    protected static $test_str_data = [
        '',
        'abcd',
        '    ',
        1234,
        123456789,
        12345.6789,
        '123456789',
        'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
        'akjdhawi323 234^$%#@213719823 hjadbgqu2y3r1276JHDWGSuqyw4352367432165432984238074530856szhbdchsaeryt324uahzddwad',
    ];

    public function test_safe_base64()
    {
        foreach (self::$test_str_data as $test_i) {
            $rst = Util::safe_base64_encode($test_i);
            $test_o = Util::safe_base64_decode($rst);
            PHPUnit_Framework_Assert::assertEquals($test_i, $test_o);
        }

        foreach (self::$test_str_data as $test_i) {
            $test_i = strval($test_i);
            $rst = Util::safe_base64_encode($test_i);
            $test_o = Util::safe_base64_decode($rst);
            PHPUnit_Framework_Assert::assertEquals($test_i, $test_o);
        }
    }

    public function test_authcode_expiry4()
    {
        $key = 'zT5hF$E24*(#dfS^Yq3&6A^6';
        $test_i = 'abc';
        $now = time();
        $rst = Util::encode($test_i, $key, 10 * 365 * 24 * 3600);   //设置有效期为 10 年

        $time = $this->getFunctionMock(Util::_namespace(), "time");  // Mock Func 命名空间下 time 函数
        $time->expects($this->once())->willReturn($now + 10 * 365 * 24 * 3600 - 100);    //模拟 10年 - 10秒后的时间

        $test_o = Util::decode($rst, $key);
        PHPUnit_Framework_Assert::assertEquals($test_i, $test_o);
    }

    public function test_authcode_expiry3()
    {
        $key = 'zT5hF$E24*(#dfS^Yq3&6A^6';
        $test_i = 'abc';
        $now = time();
        $rst = Util::encode($test_i, $key, 10 * 365 * 24 * 3600);   //设置有效期为 10 年

        $time = $this->getFunctionMock(Util::_namespace(), "time");  // Mock Func 命名空间下 time 函数
        $time->expects($this->once())->willReturn($now + 10 * 365 * 24 * 3600 + 100);    //模拟 10年 + 10秒后的时间

        $test_o = Util::decode($rst, $key);
        PHPUnit_Framework_Assert::assertEmpty($test_o);
    }

    public function test_authcode_expiry2()
    {
        $key = 'zT5hF$E24*(#dfS^Yq3&6A^6';
        $test_i = 'abc';
        $now = time();

        $rst = Util::encode($test_i, $key, 10);   //设置有效期为 10 s

        $time = $this->getFunctionMock(Util::_namespace(), "time");  // Mock Func 命名空间下 time 函数
        $time->expects($this->once())->willReturn($now + 100);

        $test_o = Util::decode($rst, $key);
        PHPUnit_Framework_Assert::assertEquals($test_o, '');
    }

    public function test_authcode_expiry1()
    {
        $key = 'zT5hF$E24*(#dfS^Yq3&6A^6';
        $test_i = 'abc';
        $rst = Util::encode($test_i, $key, 10);
        $test_o = Util::decode($rst, $key);
        PHPUnit_Framework_Assert::assertEquals($test_i, $test_o);
    }

    public function test_authcode()
    {
        $key = 'zT5hF$E24*(#dfS^Yq3&6A^1';
        foreach (self::$test_str_data as $test_i) {
            $rst = Util::encode($test_i, $key);
            $test_o = Util::decode($rst, $key);
            PHPUnit_Framework_Assert::assertEquals($test_i, $test_o);
        }

        $key = 'zT5hF$E24*(#dfS^Yq3&6A^2';
        foreach (self::$test_str_data as $test_i) {
            $test_i = strval($test_i);
            $rst = Util::encode($test_i, $key);
            $test_o = Util::decode($rst, $key);
            PHPUnit_Framework_Assert::assertEquals($test_i, $test_o);
        }

        $key1 = 'zT5hF$E24*(#dfS^Yq3&6A^3';
        $key2 = 'zT5hF$E24*(#dfS^Yq3&6A^4';
        foreach (self::$test_str_data as $test_i) {
            $rst = Util::encode($test_i, $key1);
            $test_o = Util::decode($rst, $key2);
            PHPUnit_Framework_Assert::assertEmpty($test_o);
        }
    }

    public function test_xss_filter()
    {
        $_func = self::_buildFunc(__METHOD__);
        $test_data = [
            [
                'args' => [['abc' => "<div >bcd\n</div>"], ['abc']],
                'return' => ['abc' => "div bcd/div"]
            ],
        ];
        foreach ($test_data as $test_item) {
            $item = $test_item['args'];
            $tmp = call_user_func_array([static::$_class, $_func], $item);
            PHPUnit_Framework_Assert::assertEquals($test_item['return'], $tmp, static::_buildMsg($_func, $item, $tmp));
        }
    }

    public function test_xss_clean()
    {
        $_func = self::_buildFunc(__METHOD__);
        $test_data = [
            [
                'args' => ["<div >bcd\n</div>\t"],
                'return' => "div bcd/div"
            ],
        ];
        foreach ($test_data as $test_item) {
            $item = $test_item['args'];
            $tmp = call_user_func_array([static::$_class, $_func], $item);
            PHPUnit_Framework_Assert::assertEquals($test_item['return'], $tmp, static::_buildMsg($_func, $item, $tmp));
        }
    }

    ##########################
    ######## URL相关 ########
    ##########################

    /**
     *
     */
    public function test_build_get()
    {
        Util::build_get("http://test.com", []);
        $_func = self::_buildFunc(__METHOD__);
        $test_data = [
            [
                'args' => ["http://test.com"],
                'return' => "http://test.com"
            ], [
                'args' => ["http://test.com", ['a' => 1, 'b' => 2]],
                'return' => "http://test.com/?a=1&b=2"
            ], [
                'args' => ["http://test.com/", ['a' => 1, 'b' => 2]],
                'return' => "http://test.com/?a=1&b=2"
            ],
        ];
        foreach ($test_data as $test_item) {
            $item = $test_item['args'];
            $tmp = call_user_func_array([static::$_class, $_func], $item);
            PHPUnit_Framework_Assert::assertEquals($test_item['return'], $tmp, static::_buildMsg($_func, $item, $tmp));
        }
    }

    #########################################
    ########### 魔术常量相关函数 ############
    #########################################

    /**
     * 根据魔术常量获取获取 类名
     */
    public function test_class2name()
    {

        $_func = self::_buildFunc(__METHOD__);
        $test_data = [
            [
                'args' => ['Foo\Test\testClass::testFunc'],
                'return' => "testClass"
            ],
        ];
        foreach ($test_data as $test_item) {
            $item = $test_item['args'];
            $tmp = call_user_func_array([static::$_class, $_func], $item);
            PHPUnit_Framework_Assert::assertEquals($test_item['return'], $tmp, static::_buildMsg($_func, $item, $tmp));
        }
    }

    /**
     * 根据魔术常量获取获取 函数名
     */
    public function test_method2name()
    {
        $_func = self::_buildFunc(__METHOD__);
        $test_data = [
            [
                'args' => ['Foo\Test\testClass::testFunc'],
                'return' => "testFunc"
            ],
        ];
        foreach ($test_data as $test_item) {
            $item = $test_item['args'];
            $tmp = call_user_func_array([static::$_class, $_func], $item);
            PHPUnit_Framework_Assert::assertEquals($test_item['return'], $tmp, static::_buildMsg($_func, $item, $tmp));
        }

    }

    /**
     * 根据魔术常量获取获取 函数名 并转换为 小写字母加下划线格式 的 字段名
     */
    public function test_method2field()
    {
        $_func = self::_buildFunc(__METHOD__);
        $test_data = [
            [
                'args' => ['Foo\Test\testClass::testFunc'],
                'return' => "test_func"
            ],
        ];
        foreach ($test_data as $test_item) {
            $item = $test_item['args'];
            $tmp = call_user_func_array([static::$_class, $_func], $item);
            PHPUnit_Framework_Assert::assertEquals($test_item['return'], $tmp, static::_buildMsg($_func, $item, $tmp));
        }

    }

    /**
     * 根据魔术常量获取获取 类名 并转换为 小写字母加下划线格式 的 数据表名
     */
    public function test_class2table()
    {
        $_func = self::_buildFunc(__METHOD__);
        $test_data = [
            [
                'args' => ['Foo\Test\testClass::testFunc'],
                'return' => "test_class"
            ],
        ];
        foreach ($test_data as $test_item) {
            $item = $test_item['args'];
            $tmp = call_user_func_array([static::$_class, $_func], $item);
            PHPUnit_Framework_Assert::assertEquals($test_item['return'], $tmp, static::_buildMsg($_func, $item, $tmp));
        }


    }

    /**
     * 下划线转驼峰
     */
    public function test_convertUnderline()
    {
        $_func = self::_buildFunc(__METHOD__);
        $test_data = [
            [
                'args' => ['test_class'],
                'return' => "testClass"
            ], [
                'args' => ['_test_class'],
                'return' => "TestClass"
            ], [
                'args' => ['_Test_class'],
                'return' => "TestClass"
            ], [
                'args' => ['_Test_clasS'],
                'return' => "TestClasS"
            ], [
                'args' => ['__Test_class'],
                'return' => "TestClass"
            ], [
                'args' => ['__test_class'],
                'return' => "TestClass"
            ],
        ];
        foreach ($test_data as $test_item) {
            $item = $test_item['args'];
            $tmp = call_user_func_array([static::$_class, $_func], $item);
            PHPUnit_Framework_Assert::assertEquals($test_item['return'], $tmp, static::_buildMsg($_func, $item, $tmp));
        }

    }

    /**
     * 驼峰转下划线
     */
    public function test_humpToLine()
    {
        $_func = self::_buildFunc(__METHOD__);
        $test_data = [
            [
                'args' => ['testClass'],
                'return' => "test_class"
            ], [
                'args' => ['TestClass'],
                'return' => "test_class"
            ], [
                'args' => ['TestClassSSS'],
                'return' => "test_class_sss"
            ], [
                'args' => ['_Test_clasS'],
                'return' => "_test_clas_s"
            ], [
                'args' => ['ABCTestClass'],
                'return' => "abctest_class"
            ], [
                'args' => ['123TestClass'],
                'return' => "123test_class"
            ], [
                'args' => ['TestClass123'],
                'return' => "test_class123"
            ], [
                'args' => ['TestClasS123'],
                'return' => "test_clas_s123"
            ], [
                'args' => ['TestClassEx123'],
                'return' => "test_class_ex123"
            ],
        ];
        foreach ($test_data as $test_item) {
            $item = $test_item['args'];
            $tmp = call_user_func_array([static::$_class, $_func], $item);
            PHPUnit_Framework_Assert::assertEquals($test_item['return'], $tmp, static::_buildMsg($_func, $item, $tmp));
        }
    }

    /**
     * 使用 seq 把 list 数组中的非空字符串连接起来  _join('_', [1,2,3]) = '1_2_3'
     */
    public function test_joinNotEmpty()
    {

        $_func = self::_buildFunc(__METHOD__);
        $test_data = [
            [
                'args' => ['_', [1, 2, 3]],
                'return' => "1_2_3"
            ], [
                'args' => ['_', [0, 1, 2, 3]],
                'return' => "0_1_2_3"
            ], [
                'args' => ['_', [0, '', 2, 3]],
                'return' => "0_2_3"
            ], [
                'args' => ['_', [0, '', 2, '']],
                'return' => "0_2"
            ], [
                'args' => ['_', ['', '']],
                'return' => ""
            ], [
                'args' => ['_', []],
                'return' => ""
            ], [
                'args' => ['', []],
                'return' => ""
            ],
        ];
        foreach ($test_data as $test_item) {
            $item = $test_item['args'];
            $tmp = call_user_func_array([static::$_class, $_func], $item);
            PHPUnit_Framework_Assert::assertEquals($test_item['return'], $tmp, static::_buildMsg($_func, $item, $tmp));
        }
    }
}