<?php

namespace App\Services;

use App\Models\SendPayslipProcess;

class PayslipProcessStartResult
{
    public const ACTION_ALLOW_NEW = 'allow_new';

    public const ACTION_BLOCK = 'block';

    public const ACTION_RESUME_EXISTING = 'resume_existing';

    public function __construct(
        public readonly string $action,
        public readonly ?SendPayslipProcess $process = null,
        public readonly ?string $messageKey = null,
        public readonly array $messageParams = [],
    ) {}

    public function isAllowed(): bool
    {
        return in_array($this->action, [self::ACTION_ALLOW_NEW, self::ACTION_RESUME_EXISTING], true);
    }

    public function message(): ?string
    {
        return $this->messageKey ? __($this->messageKey, $this->messageParams) : null;
    }

    public function detailsUrl(): ?string
    {
        if (!$this->process) {
            return null;
        }

        return route('portal.payslips.details', $this->process->id);
    }
}
