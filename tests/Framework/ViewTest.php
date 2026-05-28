<?php

declare(strict_types=1);

namespace Tests\Framework;

use Framework\Csrf;
use Framework\Session;
use Framework\Testing\TestCase;
use Framework\View;

final class ViewTest extends TestCase
{
    private string $dir;

    private View $view;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/miniphp-view-' . bin2hex(random_bytes(4));
        mkdir($this->dir);
        $this->view = new View($this->dir);

        Session::start();
        $_SESSION = [];
    }

    public function testMissingTemplateThrows(): void
    {
        $threw = false;

        try {
            $this->view->render('missing', [], null);
        } catch (\RuntimeException $e) {
            $threw = str_contains($e->getMessage(), 'missing');
        }

        $this->assertTrue($threw);
    }

    public function testCsrfFieldEscapesNameAndValue(): void
    {
        $_SESSION['_csrf_token'] = '<bad>';

        $field = View::csrfField();

        $this->assertContains('&lt;bad&gt;', $field);
        $this->assertContains('name="' . View::e(Csrf::FIELD) . '"', $field);
    }

    public function testRenderWithoutLayoutReturnsContentOnly(): void
    {
        file_put_contents($this->dir . '/solo.php', '<p>solo</p>');

        $html = $this->view->render('solo', [], null);

        $this->assertEquals('<p>solo</p>', $html);
    }

    public function testEscapeHelperEncodesHtml(): void
    {
        $this->assertEquals('&lt;b&gt;', View::e('<b>'));
    }
}
