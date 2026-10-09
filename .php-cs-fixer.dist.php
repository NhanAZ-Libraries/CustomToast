<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

return (new Config())
	->setRules([
		'array_syntax' => ['syntax' => 'short'],
		'no_trailing_whitespace' => true,
		'no_unused_imports' => true,
		'ordered_imports' => ['imports_order' => ['class', 'function', 'const']],
		'single_blank_line_at_eof' => true,
	])
	->setFinder(Finder::create()->in([__DIR__ . '/src', __DIR__ . '/tools'])->name('*.php'));
