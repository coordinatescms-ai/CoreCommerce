<?php

namespace App\Core\Plugin;

/**
 * Викидається коли плагін намагається виконати заборонену операцію.
 */
class PluginSecurityException extends \RuntimeException
{
}
