<?php

declare(strict_types=1);

namespace OCA\Workplanner\Settings;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class AdminSection implements IIconSection {
	public function __construct(
		private IL10N $l,
		private IURLGenerator $url,
	) {
	}

	public function getID(): string {
		return 'workplanner';
	}

	public function getName(): string {
		return $this->l->t('Workplanner');
	}

	public function getPriority(): int {
		return 65;
	}

	public function getIcon(): string {
		return $this->url->imagePath('workplanner', 'app-dark.svg');
	}
}
