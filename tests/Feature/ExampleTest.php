<?php
namespace Adm\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Contracts\Console\Kernel;
use Adm\Tests\Libs\IModelTestCase;
use Illuminate\Database\Eloquent\Collection;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function testExample()
    {
        $this->assertTrue(true);
    }
}
