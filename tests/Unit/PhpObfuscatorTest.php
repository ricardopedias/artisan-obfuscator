<?php
namespace ArtisanObfuscator\Tests\Unit;

use Tests\TestCase;
use ArtisanObfuscator\Tests\Libs\PhpArtisanObfuscatorAccessor;
use ArtisanObfuscator\Libs\PhpArtisanObfuscator;

class PhpArtisanObfuscatorTest extends TestCase
{
    private $errors;

    private $test_files = [
        'PhpClass.stub',
        'PhpClassClosed.stub',
        'PhpClassNamespaced.stub',
        'PhpProcedural.stub',
        'PhpProceduralClosed.stub',
    ];

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

    public static function getTestFileContents($filename)
    {
        return file_get_contents(self::getTestFile($filename));
    }

    public function testPhpWrapperRemove()
    {
        foreach ($this->test_files as $file) {

            $code = self::getTestFileContents($file);
            $this->assertContains('<?php', $code);

            $removed = (new PhpArtisanObfuscatorAccessor)->accessPhpWrapperRemove($code);
            $this->assertNotContains('<?php', $removed);
            $this->assertNotContains('?>', $removed);

        }

        // ---------------------------------------------------------------------
        // Procedural: Abertura + Fechamento + Mixeds
        //
        $code = self::getTestFileContents('PhpProceduralMixed.stub');

        $this->assertContains('<?php', $code);
        $this->assertContains('<?=', $code);
        $this->assertContains('?>', $code);

        $removed = (new PhpArtisanObfuscatorAccessor)->accessPhpWrapperRemove($code);
        $this->assertFalse($removed);
    }

    //
    // Compressão e descompressão
    //

    public function testBreakOne()
    {
        foreach ($this->test_files as $file) {

            $string = self::getTestFileContents($file);

            $ob = new PhpArtisanObfuscatorAccessor;
            $compressed = $ob->packerOnePack($string);
            $this->assertEquals($string, $ob->packerOneUnpack($compressed));
        }
    }

    public function testBreakTwo()
    {
        foreach ($this->test_files as $file) {

            $string = self::getTestFileContents($file);

            $ob = new PhpArtisanObfuscatorAccessor;
            $compressed = $ob->packerTwoPack($string);
            $this->assertEquals($string, $ob->packerTwoUnpack($compressed));
        }
    }

    public function testBreakThree()
    {
        foreach ($this->test_files as $file) {

            $string = self::getTestFileContents($file);

            $ob = new PhpArtisanObfuscatorAccessor;
            $compressed = $ob->packerThreePack($string);
            $this->assertEquals($string, $ob->packerThreeUnpack($compressed));
        }
    }

    //
    // Funções aleatórias
    //

    public function testGetPackerName()
    {
        $ob = new PhpArtisanObfuscatorAccessor;
        // $list = $ob->getProperty('map_packer_functions');
        $name_one = $ob->accessGetPackerName();
        $name_two = $ob->accessGetPackerName();
        $this->assertEquals($name_one, $name_two);
    }

    public function testGetPackerMethodName()
    {
        $ob = new PhpArtisanObfuscatorAccessor;
        // $list = $ob->getProperty('map_packer_functions');
        $name_one = $ob->accessGetPackerMethodName();
        $name_two = $ob->accessGetPackerMethodName();
        $this->assertEquals($name_one, $name_two);
    }

    public function testGetArgumenterName()
    {
        $ob = new PhpArtisanObfuscatorAccessor;
        // $list = $ob->getProperty('map_argumenter_functions');
        $name_one = $ob->accessGetArgumenterName();
        $name_two = $ob->accessGetArgumenterName();
        $this->assertEquals($name_one, $name_two);
    }

    //
    // Ofuscação e Execução
    //

     /**
      * @expectedException Error
      * @expectedExceptionMessage Class 'PhpClass' not found
      */
    public function testObfuscatePhpClass_Exception()
    {
        $origin = self::getTestFile('PhpClass.stub');
        $saved_file = self::getTempFile();

        // Ofusca o arquivo e salva do disco
        $ob = (new PhpArtisanObfuscator)->obfuscateFile($origin);
        $this->assertTrue($ob->save($saved_file));

        // Esta chamada deve emitir um erro no PHP
        // pois a invocação não é possivel sem a reversão
        (new \PhpClass)->method();
    }

    /**
     * @expectedException Error
     * @expectedExceptionMessage Class 'PhpClassClosed' not found
     */
    public function testObfuscatePhpClassClosed_Exception()
    {
        $origin = self::getTestFile('PhpClassClosed.stub');
        $saved_file = self::getTempFile();

        // Ofusca o arquivo e salva do disco
        $ob = (new PhpArtisanObfuscator)->obfuscateFile($origin);
        $this->assertTrue($ob->save($saved_file));

        // Esta chamada deve emitir um erro no PHP
        // pois a invocação não é possivel sem a reversão
        (new \PhpClassClosed)->method();
    }

    /**
     * @expectedException Error
     * @expectedExceptionMessage Class 'PhpClassNamespaced' not found
     */
    public function testObfuscatePhpClassNamespaced_Exception()
    {
        $origin = self::getTestFile('PhpClassNamespaced.stub');
        $saved_file = self::getTempFile();

        // Ofusca o arquivo e salva do disco
        $ob = (new PhpArtisanObfuscator)->obfuscateFile($origin);
        $this->assertTrue($ob->save($saved_file));

        // Esta chamada deve emitir um erro no PHP
        // pois a invocação não é possivel sem a reversão
        (new \PhpClassNamespaced)->method();
    }

    /**
     * @expectedException Error
     * @expectedExceptionMessage Call to undefined function PhpProcedural()
     */
    public function testObfuscatePhpProcedural_Exception()
    {
        $origin = self::getTestFile('PhpProcedural.stub');
        $saved_file = self::getTempFile();

        // Ofusca o arquivo e salva do disco
        $ob = (new PhpArtisanObfuscator)->obfuscateFile($origin);
        $this->assertTrue($ob->save($saved_file));

        // Esta chamada deve emitir um erro no PHP
        // pois a invocação não é possivel sem a reversão
        \PhpProcedural();
    }

    /**
     * @expectedException Error
     * @expectedExceptionMessage Call to undefined function PhpProceduralClosed()
     */
    public function testObfuscatePhpProceduralClosed_Exception()
    {
        $origin = self::getTestFile('PhpProceduralClosed.stub');
        $saved_file = self::getTempFile();

        // Ofusca o arquivo e salva do disco
        $ob = (new PhpArtisanObfuscator)->obfuscateFile($origin);
        $this->assertTrue($ob->save($saved_file));

        // Esta chamada deve emitir um erro no PHP
        // pois a invocação não é possivel sem a reversão
        \PhpProceduralClosed();
    }

    public function testGetRevertFileContents()
    {
        $ob = (new PhpArtisanObfuscatorAccessor)->accessGetRevertFileContents();
        $this->assertTrue(true);
    }

    public function testObfuscatePhpClass()
    {
        $origin = self::getTestFile('PhpClass.stub');
        $saved_file = self::getTempFile();
        $saved_revert_file = self::getTempFile('revert_obfuscate_');

        // Ofusca o arquivo e salva do disco
        $ob = new PhpArtisanObfuscatorAccessor;
        $ob->obfuscateFile($origin);
        $this->assertTrue($ob->save($saved_file));
        $this->assertTrue($ob->saveRevertFile($saved_revert_file));

        // Inclusão do arquivo com as funções de reversão
        //dd(file_get_contents($saved_revert_file));
        require_once $saved_revert_file;

        // Inclusão do arquivo ofuscado
        require_once $saved_file;

        // Funções
        $this->assertEquals((new \PhpClass)->method(), 'PhpClass executando com sucesso');
    }

    public function testObfuscatePhpClassClosed()
    {
        $origin = self::getTestFile('PhpClassClosed.stub');
        $saved_file = self::getTempFile();
        $saved_revert_file = self::getTempFile('revert_obfuscate_');

        // Ofusca o arquivo e salva do disco
        $ob = new PhpArtisanObfuscatorAccessor;
        $ob->obfuscateFile($origin);
        $this->assertTrue($ob->save($saved_file));
        $this->assertTrue($ob->saveRevertFile($saved_revert_file));

        // As funções de reversão já estão na memória pois o
        // arquivo foi incluido no teste testObfuscatePhpClass

        // Inclusão do arquivo ofuscado
        require_once $saved_file;

        // Funções
        $this->assertEquals((new \PhpClassClosed)->method(), 'PhpClassClosed executando com sucesso');
    }

    public function testObfuscatePhpClassNamespaced()
    {
        $origin = self::getTestFile('PhpClassNamespaced.stub');
        $saved_file = self::getTempFile();
        $saved_revert_file = self::getTempFile('revert_obfuscate_');

        // Ofusca o arquivo e salva do disco
        $ob = new PhpArtisanObfuscatorAccessor;
        $ob->obfuscateFile($origin);
        $this->assertTrue($ob->save($saved_file));
        $this->assertTrue($ob->saveRevertFile($saved_revert_file));

        // As funções de reversão já estão na memória pois o
        // arquivo foi incluido no teste testObfuscatePhpClass

        // Inclusão do arquivo ofuscado
        require_once $saved_file;

        $this->assertEquals((new \Php\Name\Space\PhpClassNamespaced)->method(), 'Php\Name\Space\PhpClassNamespaced executando com sucesso');
    }

    public function testObfuscatePhpProcedural()
    {
        $origin = self::getTestFile('PhpProcedural.stub');
        $saved_file = self::getTempFile();
        $saved_revert_file = self::getTempFile('revert_obfuscate_');

        // Ofusca o arquivo e salva do disco
        $ob = new PhpArtisanObfuscatorAccessor;
        $ob->obfuscateFile($origin);
        $this->assertTrue($ob->save($saved_file));
        $this->assertTrue($ob->saveRevertFile($saved_revert_file));

        // As funções de reversão já estão na memória pois o
        // arquivo foi incluido no teste testObfuscatePhpClass

        // Inclusão do arquivo ofuscado
        require_once $saved_file;

        // Funções
        $this->assertEquals(\PhpProcedural(), 'PhpProcedural executando com sucesso');
    }

    public function testObfuscatePhpProceduralClosed()
    {
        $origin = self::getTestFile('PhpProceduralClosed.stub');
        $saved_file = self::getTempFile();
        $saved_revert_file = self::getTempFile('revert_obfuscate_');

        // Ofusca o arquivo e salva do disco
        $ob = new PhpArtisanObfuscatorAccessor;
        $ob->obfuscateFile($origin);
        $this->assertTrue($ob->save($saved_file));
        $this->assertTrue($ob->saveRevertFile($saved_revert_file));

        // As funções de reversão já estão na memória pois o
        // arquivo foi incluido no teste testObfuscatePhpClass

        // Inclusão do arquivo ofuscado
        require_once $saved_file;

        // Funções
        $this->assertEquals(\PhpProceduralClosed(), 'PhpProceduralClosed executando com sucesso');
    }

}
