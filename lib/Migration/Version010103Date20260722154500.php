<?php

declare(strict_types=1);

namespace OCA\Workplanner\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version010103Date20260722154500 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('workplanner_plans')) {
			$table = $schema->getTable('workplanner_plans');

			if ($table->hasIndex('wp_plans_user_day')) {
				$table->dropIndex('wp_plans_user_day');
			}

			if (!$table->hasColumn('time_from')) {
				$table->addColumn('time_from', 'string', [
					'notnull' => false,
					'length' => 32,
				]);
			}

			if (!$table->hasColumn('time_to')) {
				$table->addColumn('time_to', 'string', [
					'notnull' => false,
					'length' => 32,
				]);
			}
		}

		return $schema;
	}
}
