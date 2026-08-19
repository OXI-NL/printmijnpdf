<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Compileert elke Blade-template en controleert de PHP-syntax.
 *
 * Vangt directive-botsingen die routetests missen, omdat e-mailtemplates
 * geen eigen route hebben. Dit is de guard tegen herhaling van de @context-bug.
 */
class BladeTemplatesTest extends TestCase
{
    public static function templates(): array
    {
        $root = dirname(__DIR__, 2).'/resources/views';
        $cases = [];

        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));
        foreach ($it as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            $relative = str_replace($root.'/', '', $file->getPathname());
            $cases[$relative] = [$file->getPathname(), $relative];
        }

        ksort($cases);

        return $cases;
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('templates')]
    public function test_template_compileert_naar_geldige_php(string $path, string $relative): void
    {
        $compiled = Blade::compileString(file_get_contents($path));

        $tmp = tempnam(sys_get_temp_dir(), 'blade_').'.php';
        file_put_contents($tmp, $compiled);

        $output = [];
        $status = 0;
        exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($tmp).' 2>&1', $output, $status);
        @unlink($tmp);

        $this->assertSame(
            0,
            $status,
            "resources/views/{$relative} compileert niet naar geldige PHP:\n".implode("\n", $output),
        );
    }

    public function test_er_zijn_daadwerkelijk_templates_gevonden(): void
    {
        $this->assertGreaterThanOrEqual(10, count(self::templates()));
    }
}
