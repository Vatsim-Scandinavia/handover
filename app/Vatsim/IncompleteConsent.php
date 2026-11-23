<?php

declare(strict_types=1);

namespace App\Vatsim;

use RuntimeException;

/**
 * The member withheld data or continuous access that we require.
 */
final class IncompleteConsent extends RuntimeException {}
