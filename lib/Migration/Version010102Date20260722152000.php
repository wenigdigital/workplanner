<?php

declare(strict_types=1);

namespace OCA\Workplanner\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version010102Date20260722152000 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('workplanner_plans')) {
			$table = $schema->getTable('workplanner_plans');

			if (!$table->hasColumn('note_text')) {
				$table->addColumn('note_text', 'text', [
					'notnull' => false,
				]);
			}

			if (!$table->hasColumn('time_value')) {
				$table->addColumn('time_value', 'string', [
					'notnull' => false,
					'length' => 32,
				]);
			}
		}

		return $schema;
	}
}
