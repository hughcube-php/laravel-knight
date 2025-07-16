<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/2/18
 * Time: 19:11.
 */

namespace HughCube\Laravel\Knight\Tests\Support;

use HughCube\Laravel\Knight\Support\Str;
use HughCube\Laravel\Knight\Tests\TestCase;

class StrTest extends TestCase
{
    public function testCountCommonChars()
    {
        $this->assertSame(Str::countCommonChars('我喜欢编程', '编程让我快乐'), 3);
        $this->assertSame(Str::countCommonChars('我喜欢编程', '编a程让我快乐'), 3);
        $this->assertSame(Str::countCommonChars('a喜欢编程', '编a程让我快乐'), 3);

        $this->assertSame(Str::countCommonChars('我喜欢编程', '编程让我快乐', true), 1);
        $this->assertSame(Str::countCommonChars('我喜欢编程', '程编让我快乐', true), 1);
        $this->assertSame(Str::countCommonChars('a喜欢编程我', '编a程让我快乐', true), 3);
    }

    public function testBase64Url()
    {
        for ($i = 1; $i <= 100; $i++) {
            $data = random_bytes($i * 100);

            $string = Str::base64UrlEncode($data);
            $this->assertSame($data, Str::base64UrlDecode($string));
        }
    }

    public function testIsChinese()
    {
        $this->assertTrue(!Str::isChinese('a'));
        $this->assertTrue(!Str::isChinese('张三a'));

        $this->assertTrue(Str::isChinese('张三'));
        $this->assertTrue(Str::isChinese('犇猋骉麤毳淼焱垚昍琰'));
    }

    public function testHasChinese()
    {
        $this->assertTrue(!Str::hasChinese('a'));
        $this->assertTrue(!Str::hasChinese('abcd1234ddg&)129'));

        $this->assertTrue(Str::hasChinese('张三a'));

        $this->assertTrue(Str::hasChinese('张三'));
        $this->assertTrue(Str::hasChinese('犇猋骉麤毳淼焱垚昍琰'));
    }

    public function testIsChineseName()
    {
        $this->assertTrue(!Str::isChineseName('a'));
        $this->assertTrue(!Str::isChineseName('abcd1234ddg&)129'));

        $this->assertTrue(!Str::isChineseName('张三a'));

        $this->assertTrue(!Str::isChineseName('张'));

        $this->assertTrue(Str::isChineseName('张三'));
        $this->assertTrue(Str::isChineseName('犇猋骉麤毳淼焱垚昍琰'));

        $this->assertTrue(!Str::isChineseName('·犇猋骉麤毳淼焱垚昍琰'));
        $this->assertTrue(Str::isChineseName('犇猋骉麤毳·淼焱垚昍琰'));
        $this->assertTrue(!Str::isChineseName('犇猋骉麤毳淼焱垚昍琰·'));
    }

    public function testIsCnCarLicensePlate()
    {
        // 测试普通车牌（7位）
        $this->assertTrue(Str::isCnCarLicensePlate('粤B85XS6'));
        $this->assertTrue(Str::isCnCarLicensePlate('京A12345'));
        $this->assertTrue(Str::isCnCarLicensePlate('沪B23456'));
        $this->assertTrue(Str::isCnCarLicensePlate('川A12345'));
        $this->assertTrue(Str::isCnCarLicensePlate('宁B54321')); // 修复：南->宁

        // 测试新能源车牌（8位）
        $this->assertTrue(Str::isCnCarLicensePlate('京AD12345'));
        $this->assertTrue(Str::isCnCarLicensePlate('沪AF23456'));
        $this->assertTrue(Str::isCnCarLicensePlate('粤BD12345'));

        // 测试特殊车牌
        $this->assertTrue(Str::isCnCarLicensePlate('京A88888警')); // 警车
        $this->assertTrue(Str::isCnCarLicensePlate('京A12345学')); // 教练车
        $this->assertTrue(Str::isCnCarLicensePlate('京A12345使')); // 使馆车
        $this->assertTrue(Str::isCnCarLicensePlate('京A12345领')); // 领馆车
        $this->assertTrue(Str::isCnCarLicensePlate('粤Z12345港')); // 港澳车牌
        $this->assertTrue(Str::isCnCarLicensePlate('赣E12345挂')); // 挂车
        $this->assertTrue(Str::isCnCarLicensePlate('京A12345试')); // 试验车
        $this->assertTrue(Str::isCnCarLicensePlate('京A12345超')); // 超限车
        $this->assertTrue(Str::isCnCarLicensePlate('京X1234应急')); // 应急救援车

        // 测试武警车牌
        $this->assertTrue(Str::isCnCarLicensePlate('WJ京12345'));
        $this->assertTrue(Str::isCnCarLicensePlate('WJ12345'));
        $this->assertTrue(Str::isCnCarLicensePlate('WJ·12345'));

        // 测试军车车牌
        $this->assertTrue(Str::isCnCarLicensePlate('A12345'));
        $this->assertTrue(Str::isCnCarLicensePlate('AB12345'));

        // 测试各省份简称
        $this->assertTrue(Str::isCnCarLicensePlate('京A12345')); // 北京
        $this->assertTrue(Str::isCnCarLicensePlate('津A12345')); // 天津
        $this->assertTrue(Str::isCnCarLicensePlate('冀A12345')); // 河北
        $this->assertTrue(Str::isCnCarLicensePlate('晋A12345')); // 山西
        $this->assertTrue(Str::isCnCarLicensePlate('蒙A12345')); // 内蒙古
        $this->assertTrue(Str::isCnCarLicensePlate('辽A12345')); // 辽宁
        $this->assertTrue(Str::isCnCarLicensePlate('吉A12345')); // 吉林
        $this->assertTrue(Str::isCnCarLicensePlate('黑A12345')); // 黑龙江
        $this->assertTrue(Str::isCnCarLicensePlate('沪A12345')); // 上海
        $this->assertTrue(Str::isCnCarLicensePlate('苏A12345')); // 江苏
        $this->assertTrue(Str::isCnCarLicensePlate('浙A12345')); // 浙江
        $this->assertTrue(Str::isCnCarLicensePlate('皖A12345')); // 安徽
        $this->assertTrue(Str::isCnCarLicensePlate('闽A12345')); // 福建
        $this->assertTrue(Str::isCnCarLicensePlate('赣A12345')); // 江西
        $this->assertTrue(Str::isCnCarLicensePlate('鲁A12345')); // 山东
        $this->assertTrue(Str::isCnCarLicensePlate('豫A12345')); // 河南
        $this->assertTrue(Str::isCnCarLicensePlate('鄂A12345')); // 湖北
        $this->assertTrue(Str::isCnCarLicensePlate('湘A12345')); // 湖南
        $this->assertTrue(Str::isCnCarLicensePlate('粤A12345')); // 广东
        $this->assertTrue(Str::isCnCarLicensePlate('桂A12345')); // 广西
        $this->assertTrue(Str::isCnCarLicensePlate('琼A12345')); // 海南
        $this->assertTrue(Str::isCnCarLicensePlate('渝A12345')); // 重庆
        $this->assertTrue(Str::isCnCarLicensePlate('川A12345')); // 四川
        $this->assertTrue(Str::isCnCarLicensePlate('贵A12345')); // 贵州
        $this->assertTrue(Str::isCnCarLicensePlate('云A12345')); // 云南
        $this->assertTrue(Str::isCnCarLicensePlate('藏A12345')); // 西藏
        $this->assertTrue(Str::isCnCarLicensePlate('陕A12345')); // 陕西
        $this->assertTrue(Str::isCnCarLicensePlate('甘A12345')); // 甘肃
        $this->assertTrue(Str::isCnCarLicensePlate('青A12345')); // 青海
        $this->assertTrue(Str::isCnCarLicensePlate('宁A12345')); // 宁夏
        $this->assertTrue(Str::isCnCarLicensePlate('新A12345')); // 新疆

        // 测试无效车牌
        $this->assertFalse(Str::isCnCarLicensePlate('')); // 空字符串
        $this->assertFalse(Str::isCnCarLicensePlate('赣E1234')); // 太短
        $this->assertFalse(Str::isCnCarLicensePlate('京A1234')); // 太短
        $this->assertFalse(Str::isCnCarLicensePlate('京A12345678')); // 太长
        $this->assertFalse(Str::isCnCarLicensePlate('京I12345')); // 包含I
        $this->assertFalse(Str::isCnCarLicensePlate('京O12345')); // 包含O
        $this->assertFalse(Str::isCnCarLicensePlate('英A12345')); // 无效省份
        $this->assertFalse(Str::isCnCarLicensePlate('123456')); // 纯数字
        $this->assertFalse(Str::isCnCarLicensePlate('ABCDEF')); // 纯字母
        $this->assertFalse(Str::isCnCarLicensePlate('京a12345')); // 小写字母
        $this->assertFalse(Str::isCnCarLicensePlate(null)); // null值
        $this->assertFalse(Str::isCnCarLicensePlate(123)); // 数字类型
    }
}
