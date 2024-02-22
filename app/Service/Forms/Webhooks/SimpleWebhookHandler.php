<?php

namespace App\Service\Forms\Webhooks;

use App\Service\Forms\FormSubmissionFormatter;
use Illuminate\Support\Str;

class SimpleWebhookHandler extends AbstractWebhookHandler
{
    protected function getProviderName(): string
    {
        return 'webhook';
    }

    protected function getWebhookUrl(): ?string
    {
        return $this->form->webhook_url;
    }

    protected function shouldRun(): bool
    {
        $pro = $this->form->is_pro;
        $pro = true;
        return !is_null($this->getWebhookUrl()) && $pro;
    }
}
