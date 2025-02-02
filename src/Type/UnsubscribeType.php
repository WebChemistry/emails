<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Type;

enum UnsubscribeType: string
{

	case Inactivity = 'inactivity';
	case User = 'user';

}
