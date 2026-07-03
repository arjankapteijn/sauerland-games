<?php

namespace App\Enums;

enum ChallengeStatus: string
{
    case Draft = 'draft';
    case Released = 'released';
    case Expired = 'expired';
}
