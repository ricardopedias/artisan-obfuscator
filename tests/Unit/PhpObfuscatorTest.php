<?php
namespace Obfuscator\Tests\Unit;

use Tests\TestCase;
use Obfuscator\Tests\Libs\PhpObfuscatorAccessor;
use Obfuscator\Libs\PhpObfuscator;

class PhpObfuscatorTest extends TestCase
{
    public static function getTempFile()
    {
        return tempnam(sys_get_temp_dir(), 'obfuscating_') . ".php";
    }

    public static function getTestFile($filename)
    {
        $stub_file = implode(DIRECTORY_SEPARATOR, [dirname(__DIR__), 'Files', $filename]);
        $stub_contents = file_get_contents($stub_file);

        $temp_file = tempnam(sys_get_temp_dir(), 'obfuscating_class_') . ".php";
        file_put_contents($temp_file, $stub_contents);

        return $temp_file;
    }

    public static function getTestFileContents($filename)
    {
        return file_get_contents(self::getTestFile($filename));
    }

    public function testPhpWrapperRemove()
    {
        // ---------------------------------------------------------------------
        // Class: Abertura Apenas
        //
        $code = self::getTestFileContents('PhpClass.stub');
        $this->assertContains('<?php', $code);
        $this->assertNotContains('?>', $code);

        $removed = (new PhpObfuscatorAccessor)->accessPhpWrapperRemove($code);
        $this->assertNotContains('<?php', $removed);
        $this->assertNotContains('?>', $removed);

        // ---------------------------------------------------------------------
        // Class: Abertura + Fechamento
        //
        $code = self::getTestFileContents('PhpClassClosed.stub');
        $this->assertContains('<?php', $code);
        $this->assertContains('?>', $code);

        $removed = (new PhpObfuscatorAccessor)->accessPhpWrapperRemove($code);
        $this->assertNotContains('<?php', $removed);
        $this->assertNotContains('?>', $removed);

        // ---------------------------------------------------------------------
        // Procedural: Abertura
        //
        $code = self::getTestFileContents('PhpProcedural.stub');
        $this->assertContains('<?php', $code);
        $this->assertNotContains('?>', $code);

        $removed = (new PhpObfuscatorAccessor)->accessPhpWrapperRemove($code);
        $this->assertNotContains('<?php', $removed);
        $this->assertNotContains('?>', $removed);

        // ---------------------------------------------------------------------
        // Procedural: Abertura + Fechamento
        //
        $code = self::getTestFileContents('PhpProceduralClosed.stub');
        $this->assertContains('<?php', $code);
        $this->assertContains('?>', $code);

        $removed = (new PhpObfuscatorAccessor)->accessPhpWrapperRemove($code);
        $this->assertNotContains('<?php', $removed);
        $this->assertNotContains('?>', $removed);

        // ---------------------------------------------------------------------
        // Procedural: Abertura + Fechamento + Mixeds
        //
        $code = self::getTestFileContents('PhpProceduralMixed.stub');

        $this->assertContains('<?php', $code);
        $this->assertContains('<?=', $code);
        $this->assertContains('?>', $code);

        $removed = (new PhpObfuscatorAccessor)->accessPhpWrapperRemove($code);
        $this->assertFalse($removed);
    }

    public function testObfuscate()
    {
        $origin = self::getTestFile('PhpClass.stub');
        $saved_file = self::getTempFile();

        $ob = (new PhpObfuscator)->obfuscateFile($origin);
        $this->assertTrue($ob->save($saved_file));

        // dd(file_get_contents($saved_file));

        $contents = $ob->getObfuscated();

        require_once $saved_file;

        // Executa a classe ofuscada
        $this->assertEquals((new \PlainClass)->method(), 'PlainClass executando com sucesso');

        // TODO: implementar de outra forma o wrapCode sem incluir o arquivo RevertObfuscation
        // para que seja possível testar arquivos sem as funções de reversão
    }
}
