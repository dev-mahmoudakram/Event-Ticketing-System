<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * A vote or code request the rules turn down. The message is already translated and safe to
 * show the visitor as-is.
 */
final class VotingRefused extends RuntimeException {}
