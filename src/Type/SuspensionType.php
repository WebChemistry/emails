<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Type;

enum SuspensionType: string
{

	case HardBounce = 'hard_bounce';
	case SoftBounce = 'soft_bounce';
	case SpamComplaint = 'spam_complaint';

}
