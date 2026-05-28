<?php

declare(strict_types=1);

namespace Tests\Framework;

use Framework\Autoloader;
use Framework\Testing\TestCase;

final class AutoloaderTest extends TestCase
{
    private string $baseDir;

    protected function setUp(): void
    {
        $this->baseDir = sys_get_temp_dir() . '/miniphp-autoload-' . bin2hex(random_bytes(4));
        mkdir($this->baseDir);
    }

    protected function tearDown(): void
    {
        if (is_file($this->baseDir . '/SampleClass.php')) {
            unlink($this->baseDir . '/SampleClass.php');
        }

        if (is_dir($this->baseDir)) {
            rmdir($this->baseDir);
        }
    }

    public function testLoadRequiresClassFileForRegisteredPrefix(): void
    {
        file_put_contents($this->baseDir . '/SampleClass.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace AutoloadDemo;

final class SampleClass
{
    public static function label(): string
    {
        return 'loaded';
    }
}
PHP);

        Autoloader::register('AutoloadDemo\\', $this->baseDir);
        Autoloader::load('AutoloadDemo\\SampleClass');

        $this->assertTrue(class_exists('AutoloadDemo\\SampleClass'));
        $this->assertEquals('loaded', \AutoloadDemo\SampleClass::label());
    }
}
