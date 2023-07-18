<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/2/18
 * Time: 23:17.
 */

namespace HughCube\Laravel\Knight\Tests\Support;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use HughCube\Laravel\Knight\Support\Downloader;
use HughCube\Laravel\Knight\Tests\TestCase;
use HughCube\StaticInstanceInterface;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DownloaderTest extends TestCase
{
    public function testPath()
    {
        $url = 'https://www.example.com/example.html';

        $this->assertSame(File::extension($url), File::extension(Downloader::path($url)));
        $this->assertTrue(Str::startsWith(Downloader::path($url), storage_path()));
    }

    public function testStaticInstance()
    {
        $this->assertInstanceOf(StaticInstanceInterface::class, Downloader::instance());
        $this->assertSame(Downloader::instance(), Downloader::instance());
        $this->assertNotSame(Downloader::instance(), Downloader::instance(true));
    }

    /**
     * @throws GuzzleException
     *
     * @return void
     */
    public function testTo()
    {
        $url = 'https://www.baidu.com/';

        $file = Downloader::path($url);
        $this->assertSame($file, Downloader::instance()->to('get', $url, $file, [
            RequestOptions::VERIFY => false
        ]));
        $this->assertFileExists($file);
        File::delete($file);
    }

    /**
     * @return void
     */
    public function testGet()
    {
        $url = 'https://www.baidu.com/';
        $file = Downloader::get($url);
        $this->assertFileExists($file);
        File::delete($file);
    }

    /**
     * @return void
     */
    public function testSave()
    {
        $url = 'https://www.baidu.com/';
        $file = Downloader::save($url);
        $this->assertFileExists($file);
        File::delete($file);
    }
}
