<?php
namespace Obfuscator\Tests\Libs;

use Obfuscator\Libs\PhpObfuscator;

class PhpObfuscatorAccessor extends PhpObfuscator
{
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


}
