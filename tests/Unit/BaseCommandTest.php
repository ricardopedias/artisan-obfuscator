<?php
namespace Obfuscator\Tests\Unit;

use Tests\TestCase;
use Obfuscator\Libs\PhpObfuscator;
use Obfuscator\Tests\Libs\BaseObfuscateAccessor;

class BaseCommandTest extends TestCase
{
    public static function getTestFilesPath($path = '')
    {
        return rtrim(implode(DIRECTORY_SEPARATOR, [dirname(__DIR__), 'Files', $path]), "/");
    }

    public static function getTempFile($prefix = 'obfuscating_')
    {
        return tempnam(sys_get_temp_dir(), $prefix) . ".php";
    }

    public static function getTestFile($filename)
    {
        $stub_file = implode(DIRECTORY_SEPARATOR, [dirname(__DIR__), 'Files', $filename]);
        $stub_contents = file_get_contents($stub_file);

        $temp_file = tempnam(sys_get_temp_dir(), 'obfuscating_class_') . ".php";
        file_put_contents($temp_file, $stub_contents);

        return $temp_file;
    }

    public function testGetObfuscator()
    {
        $this->assertInstanceOf(PhpObfuscator::class,
            (new BaseObfuscateAccessor)->method('getObfuscator'));
    }

    //
    // Files Path
    //

    public function testGetFilesPath_Exception()
    {
        $this->expectException(\InvalidArgumentException::class);

        $ob = new BaseObfuscateAccessor;
        $this->assertNull($ob->method('getFilesPath'));

        // Diretório 'www' não contem um arquivo composer.json correspondente
        $ob->method('setFilesPath', '/var/www');
    }

    public function testGetFilesPath_Setted()
    {
        $ob = new BaseObfuscateAccessor;
        $this->assertNull($ob->method('getFilesPath'));

        $app_test = self::getTestFilesPath('app');
        $ob->method('setFilesPath', $app_test);
        $this->assertNotNull($ob->method('getFilesPath'));
        $this->assertEquals($app_test, $ob->method('getFilesPath'));
    }

    public function testGetFilesPath_Fixed()
    {
        $ob = new BaseObfuscateAccessor;
        $this->assertNull($ob->method('getFilesPath'));

        $app_test = self::getTestFilesPath('app') . "/"; // barra adicional no final
        $app_test_no_bar = self::getTestFilesPath('app');
        $ob->method('setFilesPath', $app_test);
        $this->assertNotNull($ob->method('getFilesPath'));
        $this->assertNotEquals($app_test, $ob->method('getFilesPath'));
        $this->assertEquals($app_test_no_bar, $ob->method('getFilesPath'));
    }

    public function testObfuscatedPath()
    {
        $ob = new BaseObfuscateAccessor;
        $this->assertNull($ob->method('getFilesPath'));
        $app_test = self::getTestFilesPath('app');
        $ob->method('setFilesPath', $app_test);

        // Nomes padrões
        $ob_dir = $ob->property('obfuscated_dir');
        $ob_path = dirname($ob->method('getFilesPath')) . DIRECTORY_SEPARATOR . $ob_dir;
        $this->assertNotNull($ob->method('getObfuscatedPath'));
        $this->assertEquals($ob_path, $ob->method('getObfuscatedPath'));

        // Nomes personalizados
        $ob->property('obfuscated_dir', 'custom_ob');

        $ob_dir = $ob->property('obfuscated_dir');
        $ob_path = dirname($ob->method('getFilesPath')) . DIRECTORY_SEPARATOR . $ob_dir;
        $this->assertNotNull($ob->method('getObfuscatedPath'));
        $this->assertEquals($ob_path, $ob->method('getObfuscatedPath'));
    }

    public function testBackupPath()
    {
        $ob = new BaseObfuscateAccessor;
        $this->assertNull($ob->method('getFilesPath'));
        $app_test = self::getTestFilesPath('app');
        $ob->method('setFilesPath', $app_test);

        // Nomes padrões
        $bk_dir = $ob->property('backup_dir');
        $bk_path = dirname($ob->method('getFilesPath')) . DIRECTORY_SEPARATOR . $bk_dir;
        $this->assertNotNull($ob->method('getBackupPath'));
        $this->assertEquals($bk_path, $ob->method('getBackupPath'));

        // Nomes personalizados
        $ob->property('backup_dir', 'custom_ob');

        $bk_dir = $ob->property('backup_dir');
        $bk_path = dirname($ob->method('getFilesPath')) . DIRECTORY_SEPARATOR . $bk_dir;
        $this->assertNotNull($ob->method('getBackupPath'));
        $this->assertEquals($bk_path, $ob->method('getBackupPath'));
    }

    public function testUnpackFile()
    {
        $ob = new BaseObfuscateAccessor;
        $this->assertNull($ob->method('getFilesPath'));
        $app_test = self::getTestFilesPath('app');
        $ob->method('setFilesPath', $app_test);

        // Nomes padrões
        $file_name = $ob->property('unpack_file');
        $file = $ob->method('getFilesPath') . DIRECTORY_SEPARATOR . $file_name;
        $this->assertNotNull($ob->method('getUnpackFile'));
        $this->assertEquals($file, $ob->method('getUnpackFile'));

        // Nomes personalizados
        $ob->property('unpack_file', 'Unp.php');

        $file_name = $ob->property('unpack_file');
        $file = $ob->method('getFilesPath') . DIRECTORY_SEPARATOR . $file_name;
        $this->assertNotNull($ob->method('getUnpackFile'));
        $this->assertEquals($file, $ob->method('getUnpackFile'));
    }

    public function testComposerFile()
    {
        $ob = new BaseObfuscateAccessor;
        $this->assertNull($ob->method('getFilesPath'));
        $app_test = self::getTestFilesPath('app');
        $ob->method('setFilesPath', $app_test);

        // Nomes padrões
        $file = dirname($ob->method('getFilesPath')) . DIRECTORY_SEPARATOR . 'composer.json';
        $this->assertNotNull($ob->method('getComposerFile'));
        $this->assertEquals($file, $ob->method('getComposerFile'));
    }
}
