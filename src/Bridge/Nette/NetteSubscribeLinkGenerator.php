<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Bridge\Nette;

use InvalidArgumentException;
use Nette\Application\LinkGenerator;
use WebChemistry\Emails\Common\Encoder;
use WebChemistry\Emails\Link\BaseUrlSubscribeLinkGenerator;
use WebChemistry\Emails\Link\DecorateSubscribeLinkGenerator;
use WebChemistry\Emails\Section\Sections;

final readonly class NetteSubscribeLinkGenerator extends DecorateSubscribeLinkGenerator
{

	/**
	 * @param mixed[] $arguments
	 */
	public function __construct(
		string $destination,
		LinkGenerator $linkGenerator,
		Sections $sections,
		Encoder $encoder,
		array $arguments = [],
	)
	{
		$link = $linkGenerator->link($destination, $arguments);

		if ($link === null) {
			throw new InvalidArgumentException(sprintf('Cannot generate link for destination %s', $destination));
		}

		parent::__construct(new BaseUrlSubscribeLinkGenerator($link, $sections, $encoder));
	}

}
