<?php
namespace Obfuscator\Tests\Unit;

use Tests\TestCase;
use Obfuscator\Tests\Libs\PhpObfuscatorAccessor;
use Obfuscator\Libs\PhpObfuscator;

class PhpObfuscatorTest extends TestCase
{
    private $errors;

    protected function setUp()
    {
        $this->errors = array();
        set_error_handler(array($this, "errorHandler"));
    }

    public function errorHandler($errno, $errstr, $errfile, $errline, $errcontext)
    {
        $this->errors[] = compact("errno", "errstr", "errfile",
            "errline", "errcontext");
    }

    public function assertError($errstr, $errno)
    {
        foreach ($this->errors as $error) {
            if ($error["errstr"] === $errstr
                && $error["errno"] === $errno) {
                return;
            }
        }

        $this->fail("Error with level " . $errno .
            " and message '" . $errstr . "' not found in ",
            var_export($this->errors, TRUE));
    }

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
        foreach ([
            'PhpClass.stub',
            'PhpClassClosed.stub',
            'PhpClassNamespaced.stub',
            'PhpProcedural.stub',
            'PhpProceduralClosed.stub',
            ] as $file) {

            $code = self::getTestFileContents($file);
            $this->assertContains('<?php', $code);

            $removed = (new PhpObfuscatorAccessor)->accessPhpWrapperRemove($code);
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

        $removed = (new PhpObfuscatorAccessor)->accessPhpWrapperRemove($code);
        $this->assertFalse($removed);
    }

     /**
      * @expectedException Error
      * @expectedExceptionMessage Class 'PhpClass' not found
      */
    public function testObfuscatePhpClass_Exception()
    {
        $origin = self::getTestFile('PhpClass.stub');
        $saved_file = self::getTempFile();

        // Ofusca o arquivo e salva do disco
        $ob = (new PhpObfuscator)->obfuscateFile($origin);
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
        $ob = (new PhpObfuscator)->obfuscateFile($origin);
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
        $ob = (new PhpObfuscator)->obfuscateFile($origin);
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
        $ob = (new PhpObfuscator)->obfuscateFile($origin);
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
        $ob = (new PhpObfuscator)->obfuscateFile($origin);
        $this->assertTrue($ob->save($saved_file));

        // Esta chamada deve emitir um erro no PHP
        // pois a invocação não é possivel sem a reversão
        \PhpProceduralClosed();
    }

    public function testObfuscate()
    {

        // Inclusão do arquivo com as funções de reversão
        // para que a ofuscação possa ser desfeita

        foreach ([
            'PhpClass.stub',
            'PhpClassClosed.stub',
            'PhpClassNamespaced.stub',
            'PhpProcedural.stub',
            'PhpProceduralClosed.stub',
            ] as $file) {

            $origin = self::getTestFile($file);
            $saved_file = self::getTempFile();

            // Ofusca o arquivo e salva do disco
            $ob = (new PhpObfuscator)->obfuscateFile($origin);
            $this->assertTrue($ob->save($saved_file));

            // Inclusão do arquivo ofuscado
            require_once $saved_file;

            // Executa a classe ofuscada
            $call_name = pathinfo($file, PATHINFO_FILENAME);
            if (in_array($call_name, ['PhpProcedural', 'PhpProceduralClosed'])) {
                // Classes comuns
                $this->assertEquals($call_name(), $call_name . ' executando com sucesso');

            } elseif($call_name == 'PhpClassNamespaced') {
                // Classes com namespace
                $call_namespaced = 'Php\Name\Space\\' . $call_name;
                $this->assertEquals((new $call_namespaced)->method(), $call_namespaced . ' executando com sucesso');

            } else {
                // Funções
                $this->assertEquals((new $call_name)->method(), $call_name . ' executando com sucesso');
            }
        }
    }


}
