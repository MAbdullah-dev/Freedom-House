<?php

namespace App\Livewire\Concerns;

use InvalidArgumentException;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert as Alert;

trait LivewireAlert
{
    public function alert(string $type = 'success', string $message = '', array $options = []): void
    {
        $allowed = ['success', 'error', 'warning', 'info', 'question'];

        if (! in_array($type, $allowed, true)) {
            throw new InvalidArgumentException("Invalid '{$type}' alert icon.");
        }

        $alert = Alert::title($message)->{$type}();

        if ($options !== []) {
            $alert->withOptions($options);
        }

        $alert->show();
    }
}
