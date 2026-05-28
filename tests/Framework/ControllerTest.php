<?php

declare(strict_types=1);

namespace Tests\Framework;

use Framework\Controller;
use Framework\Response;
use Framework\Testing\TestCase;
use Framework\View;

final class ControllerTest extends TestCase
{
    public function testRenderReturnsHtmlResponse(): void
    {
        $dir = sys_get_temp_dir() . '/miniphp-controller-' . bin2hex(random_bytes(4));
        mkdir($dir);
        mkdir($dir . '/layouts');
        file_put_contents($dir . '/layouts/main.php', '<?= $unsafe_content ?>');
        file_put_contents($dir . '/page.php', '<p><?= $title ?></p>');

        $controller = new class (new View($dir)) extends Controller {
            public function page(): Response
            {
                return $this->render('page', ['title' => 'Hello']);
            }
        };

        $response = $controller->page();

        $this->assertEquals(200, $response->status());
        $this->assertContains('<p>Hello</p>', $response->body());
    }

    public function testRedirectUsesResponseRedirect(): void
    {
        $controller = new class (new View(sys_get_temp_dir())) extends Controller {
            public function go(): Response
            {
                return $this->redirect('/items');
            }
        };

        $response = $controller->go();

        $this->assertEquals(302, $response->status());
        $this->assertEquals('/items', $response->header('Location'));
    }
}
