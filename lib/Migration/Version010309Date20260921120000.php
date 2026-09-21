<?php

declare(strict_types=1);

namespace OCA\Workplanner\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version010309Date20260921120000 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('workplanner_plans')) {
			$table = $schema->getTable('workplanner_plans');

			if (!$table->hasIndex('wp_plans_user')) {
				$table->addIndex(['user_id'], 'wp_plans_user');
			}
		}

		return $schema;
	}
}
