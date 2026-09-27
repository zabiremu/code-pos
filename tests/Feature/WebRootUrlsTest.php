<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Support\WebRoot;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Tests\Concerns\CreatesStaff;
use Tests\TestCase;

/**
 * The whole project uploaded into a subfolder (example.com/pos/), with the
 * root .htaccess rewriting into public/. The server variables below are what
 * Apache hands PHP in that case, passed through WebRoot like public/index.php
 * does. Every URL the app builds must keep /pos and never show /public.
 */
class WebRootUrlsTest extends TestCase
{
    use CreatesStaff, RefreshDatabase;

    private function viaRootHtaccess(string $uri): static
    {
        $server = WebRoot::normalize([
            'SCRIPT_NAME' => '/pos/public/index.php',
            'PHP_SELF' => '/pos/public/index.php',
            'SCRIPT_FILENAME' => public_path('index.php'),
            'REQUEST_URI' => $uri,
        ]);

        return $this->withServerVariables(array_intersect_key($server, array_flip(['SCRIPT_NAME', 'PHP_SELF', 'SCRIPT_FILENAME'])));
    }

    public function test_pages_forms_and_redirects_keep_the_subfolder(): void
    {
        $html = $this->viaRootHtaccess('/pos/login')->get('/pos/login')->assertOk()->getContent();

        $this->assertStringContainsString('action="http://localhost/pos/login"', $html);
        $this->assertStringNotContainsString('/public', $html);

        // The folder's home page. $this->get() trims trailing slashes, so hand the
        // kernel the request exactly as Apache delivers it: "/pos/", or bare "/pos".
        foreach (['/pos/', '/pos'] as $uri) {
            $server = WebRoot::normalize([
                'SCRIPT_NAME' => '/pos/public/index.php',
                'SCRIPT_FILENAME' => public_path('index.php'),
                'REQUEST_URI' => $uri,
                'HTTP_HOST' => 'localhost',
            ]);
            $request = Request::createFromBase(SymfonyRequest::create('http://localhost'.$uri, 'GET', server: $server));
            $request->server->set('REQUEST_URI', $server['REQUEST_URI']);

            $response = $this->app->make(Kernel::class)->handle($request);

            $this->assertSame(302, $response->getStatusCode(), $uri);
            $this->assertSame('http://localhost/pos/login', $response->headers->get('Location'), $uri);
        }
    }

    public function test_vite_assets_and_upload_urls_have_no_public_segment(): void
    {
        $product = Product::create(['name' => 'Tea', 'base_price' => 10, 'image_path' => 'products/tea.webp', 'is_available' => true]);

        $html = $this->viaRootHtaccess('/pos/admin/dashboard')
            ->actingAs($this->staff('admin'))
            ->get('/pos/admin/dashboard')
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('#"http://localhost/pos/build/assets/app-[^"]+\.css"#', $html);
        $this->assertMatchesRegularExpression('#"http://localhost/pos/build/assets/app-[^"]+\.js"#', $html);
        $this->assertStringNotContainsString('/public/', $html);

        // Same request context the product list and register render in.
        $this->assertSame('http://localhost/pos/uploads/products/tea.webp', $product->imageUrl());
    }

    public function test_installer_redirect_keeps_the_subfolder(): void
    {
        config([
            'app.installer_redirect' => true,
            'app.installed_lock' => storage_path('framework/testing/not-installed-'.uniqid().'.lock'),
        ]);

        $this->viaRootHtaccess('/pos/login')->get('/pos/login')->assertRedirect('http://localhost/pos/install');
    }

    public function test_without_mod_rewrite_links_go_through_index_php_and_assets_through_public(): void
    {
        [$server, $assetUrl] = WebRoot::withoutRewrite(['SCRIPT_NAME' => '/pos/index.php', 'REQUEST_URI' => '/pos/index.php/login']);
        config(['app.asset_url' => $assetUrl]);
        $this->app->forgetInstance('url');

        $html = $this->withServerVariables(['SCRIPT_NAME' => $server['SCRIPT_NAME'], 'PHP_SELF' => $server['SCRIPT_NAME'], 'SCRIPT_FILENAME' => base_path('index.php')])
            ->get('/pos/index.php/login')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('action="http://localhost/pos/index.php/login"', $html);
        $this->assertSame('/pos/public/uploads/products/a.webp', asset('uploads/products/a.webp'));
    }
}
