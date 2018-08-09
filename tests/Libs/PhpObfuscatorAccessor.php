<?php
namespace ArtisanObfuscator\Tests\Libs;

use ArtisanObfuscator\Libs\PhpArtisanObfuscator;

class PhpArtisanObfuscatorAccessor extends PhpArtisanObfuscator
{
    public function getProperty($name)
    {
        return $this->$name;
    }

    public function accessPhpWrapperRemove($code)
    {
        return $this->phpWrapperRemove($code);
    }

    public function accessPhpWrapperAdd($code)
    {
        return $this->phpWrapperAdd($code);
    }

    public function accessEncodedWrapperAdd($code)
    {
        return $this->encodedWrapperAdd($code);
    }

    //
    // Funções ramdomicas
    //

    public function accessGetPackerName()
    {
        return $this->getPackerName();
    }

    public function accessGetPackerMethodName()
    {
        return $this->getPackerMethodName();
    }

    public function accessGetArgumenterName()
    {
        return $this->getArgumenterName();
    }

    //
    // Geração do arquivo de ofuscação
    //

    public function accessGetRevertFileContents()
    {
        return $this->getRevertFileContents();
    }


}
