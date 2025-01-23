<?php declare(strict_types = 1);

namespace WebChemistry\Emails;

enum OperationType
{

	case Insert;
	case Update;
	case Upsert;

}
