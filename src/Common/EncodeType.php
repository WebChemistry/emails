<?php

namespace WebChemistry\Emails\Common;

enum EncodeType: string
{

	case Basic = 'b';
	case Salt = 's';
	case Encrypted = 'e';

}
