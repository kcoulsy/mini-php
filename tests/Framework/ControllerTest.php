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

        $response = (new PageTestController(new View($dir)))->page();

        $this->assertEquals(200, $response->status());
        $this->assertContains('<p>Hello</p>', $response->body());
    }

    public function testRedirectUsesResponseRedirect(): void
    {
        $response = (new RedirectTestController(new View(sys_get_temp_dir())))->go();

        $this->assertEquals(302, $response->status());
        $this->assertEquals('/items', $response->header('Location'));
    }

    public function testFlashIsConsumedOnce(): void
    {
        $_SESSION['flash'] = 'Saved.';

        $controller = new FlashReadTestController(new View(sys_get_temp_dir()));

        $this->assertEquals('Saved.', $controller->read());
        $this->assertNull($controller->read());
    }

    public function testSetFlashStoresMessageForNextRead(): void
    {
        unset($_SESSION['flash']);

        $controller = new FlashWriteReadTestController(new View(sys_get_temp_dir()));

        $this->assertEquals('Done.', $controller->writeAndRead());
        $this->assertNull($controller->readAgain());
    }
}

final class PageTestController extends Controller
{
    public function page(): Response
    {
        return $this->render('page', ['title' => 'Hello']);
    }
}

final class RedirectTestController extends Controller
{
    public function go(): Response
    {
        return $this->redirect('/items');
    }
}

final class FlashReadTestController extends Controller
{
    public function read(): ?string
    {
        return $this->flash();
    }
}

final class FlashWriteReadTestController extends Controller
{
    public function writeAndRead(): ?string
    {
        $this->setFlash('Done.');

        return $this->flash();
    }

    public function readAgain(): ?string
    {
        return $this->flash();
    }
}
