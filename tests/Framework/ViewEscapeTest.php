<?php

declare(strict_types=1);

namespace Tests\Framework;

use Framework\Escaped;
use Framework\Testing\TestCase;
use Framework\View;

final class ViewEscapeTest extends TestCase
{
    private View $view;

    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/miniphp-view-' . bin2hex(random_bytes(4));
        mkdir($this->dir);

        file_put_contents($this->dir . '/escape-test.php', <<<'PHP'
<p><?= $name ?></p>
<p><?= $unsafe_html ?></p>
PHP);

        $this->view = new View($this->dir);
    }

    public function testStringsAreEscapedByDefault(): void
    {
        $html = $this->view->render('escape-test', [
            'name' => '<script>alert(1)</script>',
            'unsafe_html' => '<em>raw</em>',
        ], null);

        $this->assertContains('&lt;script&gt;', $html);
        $this->assertContains('<em>raw</em>', $html);
        $this->assertFalse(str_contains($html, '<script>alert'));
    }

    public function testEscapedWrapperEscapesOnCast(): void
    {
        $escaped = new Escaped('<b>hi</b>');

        $this->assertEquals('&lt;b&gt;hi&lt;/b&gt;', (string) $escaped);
        $this->assertEquals('<b>hi</b>', $escaped->raw());
    }

    public function testNestedArraysEscapeStringValues(): void
    {
        file_put_contents($this->dir . '/nested.php', '<p><?= $items["label"] ?></p>');

        $html = $this->view->render('nested', [
            'items' => ['label' => '<i>x</i>'],
        ], null);

        $this->assertContains('&lt;i&gt;x&lt;/i&gt;', $html);
    }
}
