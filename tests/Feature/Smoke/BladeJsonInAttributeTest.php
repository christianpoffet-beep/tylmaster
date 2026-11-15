<?php

namespace Tests\Feature\Smoke;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * @json prints its strings in raw double quotes. Inside a double-quoted
 * attribute - x-data="{ types: @json($types) }" - the first of them ends the
 * attribute: Alpine gets half an expression, and the rest of it lands on the
 * page as text up to the next ">". That is what printed script above the
 * product form. @js prints JSON.parse('...') and is safe there.
 */
class BladeJsonInAttributeTest extends TestCase
{
    public function test_no_view_puts_json_inside_a_double_quoted_attribute(): void
    {
        $offenders = [];
        foreach (File::allFiles(resource_path('views')) as $file) {
            if (!str_ends_with($file->getFilename(), '.blade.php')) continue;

            foreach (self::jsonInsideAttributes($file->getContents()) as $line) {
                $offenders[] = str_replace('\\', '/', $file->getRelativePathname()) . ':' . $line;
            }
        }

        $this->assertSame([], $offenders, "Use @js() instead of @json() inside these attributes:\n" . implode("\n", $offenders));
    }

    public function test_the_scan_finds_the_broken_pattern_and_leaves_scripts_alone(): void
    {
        $this->assertSame([2], self::jsonInsideAttributes("<p>\n<div x-data=\"{ a: @json(\$a) }\">"));
        $this->assertSame([], self::jsonInsideAttributes("<div x-data=\"{ a: @js(\$a) }\">"));
        $this->assertSame([], self::jsonInsideAttributes("<script>\nconst a = @json(\$a);\n</script>"));
        $this->assertSame([], self::jsonInsideAttributes("<div x-data='{ a: @json(\$a) }'>"));
        $this->assertSame([], self::jsonInsideAttributes("<div x-show=\"a => b\">@json(\$a)</div>"));
    }

    /** @return int[] lines where @json( sits inside a double-quoted attribute value */
    private static function jsonInsideAttributes(string $source): array
    {
        $lines = [];
        $line = 1;
        $inTag = $inScript = false;
        $quote = null;
        $length = strlen($source);

        for ($i = 0; $i < $length; $i++) {
            $char = $source[$i];
            if ($char === "\n") $line++;

            // Blade comments and echoes can hold anything
            foreach (['{{--' => '--}}', '{{' => '}}', '{!!' => '!!}'] as $open => $close) {
                if (substr_compare($source, $open, $i, strlen($open)) === 0) {
                    $end = strpos($source, $close, $i + strlen($open));
                    if ($end === false) return $lines;
                    $line += substr_count($source, "\n", $i, $end - $i);
                    $i = $end + strlen($close) - 1;
                    continue 2;
                }
            }

            if (!$inTag) {
                if ($inScript) {
                    if (substr_compare($source, '</script', $i, 8, true) === 0) $inScript = false;
                    continue;
                }
                if ($char === '<' && ctype_alpha($source[$i + 1] ?? '')) {
                    $inTag = true;
                    $inScript = substr_compare($source, '<script', $i, 7, true) === 0;
                }
                continue;
            }

            if ($quote !== null) {
                if ($quote === '"' && substr_compare($source, '@json(', $i, 6) === 0) $lines[] = $line;
                if ($char === $quote) $quote = null;
                continue;
            }

            if ($char === '"' || $char === "'") {
                $quote = $char;
            } elseif ($char === '>') {
                $inTag = false;
            }
        }

        return $lines;
    }
}
