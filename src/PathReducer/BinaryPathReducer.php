<?php

namespace Tienvx\Bundle\MbtBundle\PathReducer;

use Throwable;
use Tienvx\Bundle\MbtBundle\Graph\Path;

class BinaryPathReducer extends AbstractPathReducer
{
    public function reduce(Path $path, string $model, string $subject, Throwable $throwable): Path
    {
        $try = 1;
        $quotient = floor($path->countEdges() / pow(2, $try));
        $remainder = $path->countEdges() % pow(2, $try);
        $maxTries = $path->countVertices();

        while (($try <= $maxTries && $quotient > 0) && $path->countEdges() >= 2) {
            for ($i = 0; $i < pow(2, $try); $i++) {
                $j = $quotient * $i;
                if ($i === (pow(2, $try) - 1)) {
                    $k = $quotient * ($i + 1) + $remainder;
                }
                else {
                    $k = $quotient * ($i + 1);
                }
                $newPath = $this->getNewPath($path, $j, $k);
                // Make sure new path shorter than old path.
                if ($newPath->countVertices() < $path->countVertices()) {
                    try {
                        $this->runner->run($newPath, $model, $subject);
                    } catch (Throwable $newThrowable) {
                        if ($newThrowable->getMessage() === $throwable->getMessage()) {
                            $path = $newPath;
                            $try = 1;
                            $maxTries = $path->countVertices();
                            break;
                        }
                    }
                }
            }
            $try++;
            $quotient = floor($path->countEdges() / pow(2, $try));
            $remainder = $path->countEdges() % pow(2, $try);
        }

        // Tired of trying.
        return $path;
    }

    public static function getName()
    {
        return 'binary';
    }
}
