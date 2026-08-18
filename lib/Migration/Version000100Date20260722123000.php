<?php

declare(strict_types=1);

namespace OCA\Workplanner\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version000100Date20260722123000 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('workplanner_locations')) {
			$table = $schema->createTable('workplanner_locations');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 4,
			]);
			$table->addColumn('name', 'string', [
				'notnull' => true,
				'length' => 120,
			]);
			$table->addColumn('color', 'string', [
				'notnull' => true,
				'length' => 16,
				'default' => '#2f6fdd',
			]);
			$table->addColumn('description', 'text', [
				'notnull' => false,
			]);
			$table->addColumn('active', 'integer', [
				'notnull' => true,
				'default' => 1,
			]);
			$table->addColumn('sort_order', 'integer', [
				'notnull' => true,
				'default' => 0,
			]);
			$table->addColumn('created_at', 'integer', [
				'notnull' => true,
				'default' => 0,
			]);
			$table->setPrimaryKey(['id']);
			$table->addIndex(['active'], 'wp_locations_active');
		}

		if (!$schema->hasTable('workplanner_plans')) {
			$table = $schema->createTable('workplanner_plans');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 4,
			]);
			$table->addColumn('user_id', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('day', 'string', [
				'notnull' => true,
				'length' => 10,
			]);
			$table->addColumn('location_id', 'integer', [
				'notnull' => false,
				'length' => 4,
			]);
			$table->addColumn('note', 'string', [
				'notnull' => false,
				'length' => 255,
			]);
			$table->addColumn('note_text', 'text', [
				'notnull' => false,
			]);
			$table->addColumn('time_value', 'string', [
				'notnull' => false,
				'length' => 32,
			]);
			$table->addColumn('time_from', 'string', [
				'notnull' => false,
				'length' => 32,
			]);
			$table->addColumn('time_to', 'string', [
				'notnull' => false,
				'length' => 32,
			]);
			$table->addColumn('created_at', 'integer', [
				'notnull' => true,
				'default' => 0,
			]);
			$table->addColumn('updated_at', 'integer', [
				'notnull' => true,
				'default' => 0,
			]);
			$table->setPrimaryKey(['id']);
			$table->addIndex(['day'], 'wp_plans_day');
			$table->addIndex(['location_id'], 'wp_plans_location');
		}

		return $schema;
	}
}
