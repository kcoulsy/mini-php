<?php

declare(strict_types=1);

namespace Tests\Unit;

use Framework\Testing\TestCase;
use Framework\View;

final class ViewScriptStackTest extends TestCase
{
    private string $dir;

    private View $view;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/miniphp-scripts-' . bin2hex(random_bytes(4));
        mkdir($this->dir);
        mkdir($this->dir . '/layouts');

        $this->view = new View($this->dir);
    }

    public function testDedupesScriptsById(): void
    {
        file_put_contents($this->dir . '/dedupe.php', <<<'PHP'
<?php
use Framework\View;

View::script('<script>first</script>', 'shared');
require __DIR__ . '/dedupe-partial.php';
PHP);

        file_put_contents($this->dir . '/dedupe-partial.php', <<<'PHP'
<?php
use Framework\View;

View::script('<script>second</script>', 'shared');
PHP);

        file_put_contents($this->dir . '/layouts/test.php', <<<'PHP'
<main><?= $unsafe_content ?></main>
<?= $unsafe_scripts ?>
</body>
PHP);

        $html = $this->view->render('dedupe', [], 'layouts/test');

        $this->assertEquals(1, substr_count($html, '<script>first</script>'));
        $this->assertFalse(str_contains($html, '<script>second</script>'));
    }

    public function testAppendsScriptsWithoutId(): void
    {
        file_put_contents($this->dir . '/append.php', <<<'PHP'
<?php
use Framework\View;

View::script('<script>a</script>');
View::script('<script>b</script>');
PHP);

        file_put_contents($this->dir . '/layouts/test.php', <<<'PHP'
<?= $unsafe_scripts ?>
PHP);

        $html = $this->view->render('append', [], 'layouts/test');

        $this->assertTrue(str_contains($html, "<script>a</script>\n<script>b</script>"));
    }

    public function testScriptCaptureMovesOutputToStack(): void
    {
        file_put_contents($this->dir . '/capture.php', <<<'PHP'
<?php
use Framework\View;

?>
<p>body</p>
<?php View::scriptStart('inline'); ?>
<script>inline();</script>
<?php View::scriptEnd(); ?>
PHP);

        file_put_contents($this->dir . '/layouts/test.php', <<<'PHP'
<main><?= $unsafe_content ?></main>
<?= $unsafe_scripts ?>
PHP);

        $html = $this->view->render('capture', [], 'layouts/test');

        $mainStart = strpos($html, '<main>');
        $mainEnd = strpos($html, '</main>');
        $main = substr($html, $mainStart, $mainEnd - $mainStart);

        $this->assertContains('<p>body</p>', $main);
        $this->assertFalse(str_contains($main, '<script>'));
        $this->assertContains('<script>inline();</script>', $html);
        $this->assertTrue(strpos($html, '<script>inline();</script>') > $mainEnd);
    }

    public function testScriptCaptureSkipsWhenIdAlreadyRegistered(): void
    {
        file_put_contents($this->dir . '/capture-skip.php', <<<'PHP'
<?php
use Framework\View;

View::script('<script>existing</script>', 'dup');
View::scriptStart('dup');
?>
<script>ignored</script>
<?php
View::scriptEnd();
PHP);

        file_put_contents($this->dir . '/layouts/test.php', <<<'PHP'
<?= $unsafe_scripts ?>
PHP);

        $html = $this->view->render('capture-skip', [], 'layouts/test');

        $this->assertEquals(1, substr_count($html, '<script>'));
        $this->assertContains('<script>existing</script>', $html);
        $this->assertFalse(str_contains($html, 'ignored'));
    }

    public function testRenderResetsScriptsBetweenCalls(): void
    {
        file_put_contents($this->dir . '/once.php', <<<'PHP'
<?php
use Framework\View;

View::script('<script>once</script>');
PHP);

        file_put_contents($this->dir . '/empty.php', '<p>none</p>');

        file_put_contents($this->dir . '/layouts/test.php', <<<'PHP'
<?= $unsafe_scripts ?>
PHP);

        $first = $this->view->render('once', [], 'layouts/test');
        $second = $this->view->render('empty', [], 'layouts/test');

        $this->assertContains('<script>once</script>', $first);
        $this->assertFalse(str_contains($second, '<script>once</script>'));
    }

    public function testFlushScriptsReturnsJoinedHtml(): void
    {
        file_put_contents($this->dir . '/flush.php', '');
        file_put_contents($this->dir . '/layouts/test.php', '');

        $this->view->render('flush', [], 'layouts/test');

        View::script('<script>x</script>', 'a');
        View::script('<script>y</script>');

        $this->assertEquals("<script>x</script>\n<script>y</script>", View::flushScripts());

        $this->view->render('flush', [], 'layouts/test');
    }
}
