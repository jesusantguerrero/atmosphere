<?php
namespace App\Domains\Automation\Enums;

enum AutomationTaskType: string  {
    const ACTION = 'action';
    const TRIGGER = 'trigger';
    const COMPONENT = 'component';
}
