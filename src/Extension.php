<?php

declare(strict_types=1);

namespace Exercism\PhpTestRunner;

use Exercism\PhpTestRunner\Tracer;
use PHPUnit\Runner\Extension\Extension as ExtensionInterface;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

final class Extension implements ExtensionInterface
{
    public function bootstrap(
        Configuration $configuration,
        Facade $facade,
        ParameterCollection $parameters,
    ): void {
        $outFileName = \getenv('EXERCISM_RESULT_FILE');
        if (empty($outFileName)) {
            $outFileName = $parameters->get('outFileName');
        }

        $exerciseDir = \getenv('EXERCISM_EXERCISE_DIR');
        if (empty($exerciseDir)) {
            $exerciseDir = \getenv('PWD');
        }

        $facade->registerTracer(new Tracer($outFileName, $exerciseDir));
    }
}
