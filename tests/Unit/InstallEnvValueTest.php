<?php

namespace Tests\Unit;

use App\Http\Controllers\Install\InstallController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * InstallController::envValue() is what stands between a buyer's DB
 * password and a corrupted or injectable .env file (see its writeEnv()
 * doc comment) - a plain preg_replace() with the raw value used to treat
 * "$1"-style substrings as backreferences and let spaces/quotes/newlines
 * break or extend the file. Tested directly via reflection since it's a
 * small pure function with no DB/Artisan dependency.
 */
class InstallEnvValueTest extends TestCase
{
    private function envValue(string $value): string
    {
        $method = new ReflectionMethod(InstallController::class, 'envValue');
        $method->setAccessible(true);

        return $method->invoke(new InstallController, $value);
    }

    public function test_a_simple_value_is_left_unquoted(): void
    {
        $this->assertSame('root', $this->envValue('root'));
    }

    public function test_a_value_with_a_space_gets_quoted(): void
    {
        $this->assertSame('"my pass"', $this->envValue('my pass'));
    }

    public function test_an_empty_value_gets_quoted(): void
    {
        $this->assertSame('""', $this->envValue(''));
    }

    public function test_embedded_quotes_and_backslashes_are_escaped(): void
    {
        $this->assertSame('"a\\"b\\\\c"', $this->envValue('a"b\\c'));
    }

    public function test_a_dollar_backreference_looking_value_is_not_mangled(): void
    {
        // A naive preg_replace($pattern, "$1literal", $subject) would try
        // to substitute a capture group here - envValue() must return it
        // completely unchanged (aside from quoting, if needed).
        $this->assertSame('$1weird', $this->envValue('$1weird'));
    }

    public function test_newlines_are_stripped_so_they_cannot_inject_a_second_env_line(): void
    {
        $this->assertSame('injected', $this->envValue("inj\nected"));
    }
}
