<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Exceptions;

use RefactorCircus\Keystone\Exceptions\PackageException;

/**
 * Everything Guardian throws descends from this, so callers can catch any
 * Guardian failure at once. The message is written for whoever made the call.
 */
abstract class GuardianException extends PackageException {}
