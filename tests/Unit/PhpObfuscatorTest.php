<?php
namespace Obfuscator\Tests\Unit;

use Tests\TestCase;
use Obfuscator\Tests\Libs\PhpObfuscatorAccessor;

class PhpObfuscatorTest extends TestCase
{
    public function getTempFile()
    {
        return tempnam(sys_get_temp_dir(), 'obfuscating_') . ".php";
    }

    public function getTestFile($filename)
    {
        return implode(DIRECTORY_SEPARATOR, [dirname(__DIR__), 'Files', $filename]);
    }

    public function getTestFileContents($filename)
    {
        return file_get_contents($this->getTestFile($filename));
    }

    public function testPhpWrapperRemove()
    {
        // ---------------------------------------------------------------------
        // Class: Abertura Apenas
        //
        $code = $this->getTestFileContents('PhpClass.php');
        $this->assertContains('<?php', $code);
        $this->assertNotContains('?>', $code);

        $removed = (new PhpObfuscatorAccessor)->accessPhpWrapperRemove($code);
        $this->assertNotContains('<?php', $removed);
        $this->assertNotContains('?>', $removed);

        // ---------------------------------------------------------------------
        // Class: Abertura + Fechamento
        //
        $code = $this->getTestFileContents('PhpClassClosed.php');
        $this->assertContains('<?php', $code);
        $this->assertContains('?>', $code);

        $removed = (new PhpObfuscatorAccessor)->accessPhpWrapperRemove($code);
        $this->assertNotContains('<?php', $removed);
        $this->assertNotContains('?>', $removed);

        // ---------------------------------------------------------------------
        // Procedural: Abertura
        //
        $code = $this->getTestFileContents('PhpProcedural.php');
        $this->assertContains('<?php', $code);
        $this->assertNotContains('?>', $code);

        $removed = (new PhpObfuscatorAccessor)->accessPhpWrapperRemove($code);
        $this->assertNotContains('<?php', $removed);
        $this->assertNotContains('?>', $removed);

        // ---------------------------------------------------------------------
        // Procedural: Abertura + Fechamento
        //
        $code = $this->getTestFileContents('PhpProceduralClosed.php');
        $this->assertContains('<?php', $code);
        $this->assertContains('?>', $code);

        $removed = (new PhpObfuscatorAccessor)->accessPhpWrapperRemove($code);
        $this->assertNotContains('<?php', $removed);
        $this->assertNotContains('?>', $removed);

        // ---------------------------------------------------------------------
        // Procedural: Abertura + Fechamento + Mixeds
        //
        $code = $this->getTestFileContents('PhpProceduralMixed.php');

        $this->assertContains('<?php', $code);
        $this->assertContains('<?=', $code);
        $this->assertContains('?>', $code);

        $removed = (new PhpObfuscatorAccessor)->accessPhpWrapperRemove($code);
        $this->assertFalse($removed);
    }

}
