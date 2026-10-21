<?php

namespace Tests\Unit;

use App\Modules\Shared\Support\EmailMasker;
use PHPUnit\Framework\TestCase;

class EmailMaskerTest extends TestCase
{
    public function test_it_keeps_only_a_prefix_of_the_local_part_and_domain(): void
    {
        $this->assertSame('ju***@gma***.com', EmailMasker::mask('juan.perez@gmail.com'));
        $this->assertSame('ve***@imp***.com.co', EmailMasker::mask('ventas@importcorporal.com.co'));
    }

    public function test_it_handles_short_and_invalid_values(): void
    {
        $this->assertSame('a***@x***', EmailMasker::mask('a@x'));
        $this->assertSame('***', EmailMasker::mask('not-an-email'));
    }
}
