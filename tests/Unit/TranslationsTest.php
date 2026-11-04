<?php

namespace Javaabu\Settings\Tests\Unit;

use Javaabu\Settings\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class TranslationsTest extends TestCase
{
    #[Test]
    public function every_string_has_a_dhivehi_translation(): void
    {
        $dv = json_decode(file_get_contents(__DIR__ . '/../../lang/dv.json'), true);
        $en = json_decode(file_get_contents(__DIR__ . '/../../lang/en.json'), true);

        $missing = array_values(array_diff($this->translatableStrings(), array_keys($dv)));

        $this->assertEquals([], $missing, 'Add these to lang/dv.json');
        $this->assertEquals(array_keys($dv), array_keys($en), 'lang/en.json and lang/dv.json should have the same keys');

        foreach ($dv as $key => $value) {
            $this->assertMatchesRegularExpression('/\p{Thaana}/u', $value, "[{$key}] isn't translated");
        }
    }

    #[Test]
    public function it_translates_into_dhivehi(): void
    {
        app()->setLocale('dv');

        $this->assertEquals('ސެޓިންގްސް ސޭވްކުރެވިއްޖެ.', __('Settings saved.'));
        $this->assertEquals('ސެޓިންގްސް ބަދަލުކުރުން', __('Edit settings'));
    }

    /**
     * Literal strings passed to __() in src and resources, outside comments,
     * and the permission descriptions
     *
     * @return array<int, string>
     */
    protected function translatableStrings(): array
    {
        $strings = ['View settings', 'Edit settings', 'Settings'];

        foreach (['src', 'resources/views', 'resources/js'] as $directory) {
            $path = __DIR__ . "/../../{$directory}";

            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
                foreach (file($file->getPathname()) as $line) {
                    if (preg_match('/^\s*(\*|\/\/|\/\*)/', $line)) {
                        continue;
                    }

                    preg_match_all("/(?<![\w>])__\(\s*'((?:[^'\\\\]|\\\\.)*)'/", $line, $matches);

                    foreach ($matches[1] as $string) {
                        $strings[] = stripslashes($string);
                    }
                }
            }
        }

        return array_values(array_unique($strings));
    }
}
