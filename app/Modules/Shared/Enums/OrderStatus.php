<?php

namespace App\Modules\Shared\Enums;

enum OrderStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Sending = 'sending';
    case Sent = 'sent';
    case Failed = 'failed';
}

