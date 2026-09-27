<?php

namespace Tests\Unit;

use App\Support\WebRoot;
use PHPUnit\Framework\TestCase;

/** How $_SERVER is adjusted so URLs never contain /public - see WebRoot. */
class WebRootTest extends TestCase
{
    private function rewritten(string $script, string $uri): array
    {
        return WebRoot::normalize(['SCRIPT_NAME' => $script, 'PHP_SELF' => $script, 'REQUEST_URI' => $uri]);
    }

    public function test_project_in_the_web_root_reports_the_script_at_the_root(): void
    {
        $server = $this->rewritten('/public/index.php', '/admin/dashboard?x=1');

        $this->assertSame('/index.php', $server['SCRIPT_NAME']);
        $this->assertSame('/index.php', $server['PHP_SELF']);
    }

    public function test_project_in_a_subfolder_keeps_the_subfolder(): void
    {
        $server = $this->rewritten('/pos/public/index.php', '/pos/install/database');

        $this->assertSame('/pos/index.php', $server['SCRIPT_NAME']);
    }

    public function test_the_bare_subfolder_url_is_treated_as_its_home_page(): void
    {
        $server = $this->rewritten('/pos/public/index.php', '/pos?ref=1');

        $this->assertSame('/pos/index.php', $server['SCRIPT_NAME']);
        $this->assertSame('/pos/?ref=1', $server['REQUEST_URI']);
    }

    public function test_document_root_at_public_is_left_alone(): void
    {
        $server = $this->rewritten('/index.php', '/login');

        $this->assertSame('/index.php', $server['SCRIPT_NAME']);
    }

    public function test_a_url_the_visitor_typed_with_public_is_left_alone(): void
    {
        $this->assertSame('/pos/public/index.php', $this->rewritten('/pos/public/index.php', '/pos/public/login')['SCRIPT_NAME']);
        $this->assertSame('/public/index.php', $this->rewritten('/public/index.php', '/public')['SCRIPT_NAME']);
    }

    public function test_a_route_that_merely_contains_public_is_still_rewritten(): void
    {
        $this->assertSame('/index.php', $this->rewritten('/public/index.php', '/publications')['SCRIPT_NAME']);
    }

    public function test_without_rewrite_the_folder_url_gets_index_php_and_assets_come_from_public(): void
    {
        [$server, $assets] = WebRoot::withoutRewrite(['SCRIPT_NAME' => '/pos/index.php', 'REQUEST_URI' => '/pos/?ref=1']);

        $this->assertSame('/pos/index.php/?ref=1', $server['REQUEST_URI']);
        $this->assertSame('/pos/public', $assets);

        [$server, $assets] = WebRoot::withoutRewrite(['SCRIPT_NAME' => '/index.php', 'REQUEST_URI' => '/index.php/login']);

        $this->assertSame('/index.php/login', $server['REQUEST_URI']);
        $this->assertSame('/public', $assets);
    }
}
