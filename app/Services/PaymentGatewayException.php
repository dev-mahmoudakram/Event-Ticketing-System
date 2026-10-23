<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * Kashier could not be reached, refused a request, or answered with something we won't trust.
 */
class PaymentGatewayException extends RuntimeException {}
