<?php

declare(strict_types=1);

namespace OCA\Workplanner\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version010104Date20260823194500 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('workplanner_plans')) {
			$table = $schema->getTable('workplanner_plans');

			if (!$table->hasColumn('location_name')) {
				$table->addColumn('location_name', 'string', [
					'notnull' => false,
					'length' => 255,
				]);
			}

			if (!$table->hasColumn('location_color')) {
				$table->addColumn('location_color', 'string', [
					'notnull' => false,
					'length' => 32,
				]);
			}
		}

		return $schema;
	}
}
