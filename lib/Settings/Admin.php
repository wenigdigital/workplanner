<?php

declare(strict_types=1);

namespace OCA\Workplanner\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;

class Admin implements ISettings {
	public function getForm(): TemplateResponse {
		return new TemplateResponse('workplanner', 'admin');
	}

	public function getSection(): string {
		return 'workplanner';
	}

	public function getPriority(): int {
		return 50;
	}
}
