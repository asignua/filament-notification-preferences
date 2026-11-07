<?php

declare(strict_types=1);

namespace Workbench\App\Notifications;

/**
 * A subclass of a locked notification: declaring it as a type of its own, unlocked, must be refused.
 */
class UrgentSecurityAlert extends SecurityAlert {}
