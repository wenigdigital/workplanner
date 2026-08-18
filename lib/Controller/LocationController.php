<?php

declare(strict_types=1);

namespace OCA\Workplanner\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IRequest;

class LocationController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private IDBConnection $db,
	) {
		parent::__construct($appName, $request);
	}

	public function index(): DataResponse {
		return new DataResponse(['locations' => $this->getLocations(false)]);
	}

	public function create(string $name, string $color = '#2f6fdd', string $description = '', int $sortOrder = 0, bool $active = true): DataResponse {
		$name = trim($name);
		if ($name === '') {
			return new DataResponse(['error' => 'The name must not be empty.'], 400);
		}

		$qb = $this->db->getQueryBuilder();
		$qb->insert('workplanner_locations')
			->values([
				'name' => $qb->createNamedParameter($name),
				'color' => $qb->createNamedParameter($this->normalizeColor($color)),
				'description' => $qb->createNamedParameter(trim($description)),
				'active' => $qb->createNamedParameter($active ? 1 : 0, IQueryBuilder::PARAM_INT),
				'sort_order' => $qb->createNamedParameter($sortOrder, IQueryBuilder::PARAM_INT),
				'created_at' => $qb->createNamedParameter(time(), IQueryBuilder::PARAM_INT),
			])
			->executeStatement();

		return new DataResponse(['locations' => $this->getLocations(false)]);
	}

	public function update(int $id, string $name, string $color = '#2f6fdd', string $description = '', int $sortOrder = 0, bool $active = true): DataResponse {
		$name = trim($name);
		if ($name === '') {
			return new DataResponse(['error' => 'The name must not be empty.'], 400);
		}

		$qb = $this->db->getQueryBuilder();
		$qb->update('workplanner_locations')
			->set('name', $qb->createNamedParameter($name))
			->set('color', $qb->createNamedParameter($this->normalizeColor($color)))
			->set('description', $qb->createNamedParameter(trim($description)))
			->set('active', $qb->createNamedParameter($active ? 1 : 0, IQueryBuilder::PARAM_INT))
			->set('sort_order', $qb->createNamedParameter($sortOrder, IQueryBuilder::PARAM_INT))
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
			->executeStatement();

		return new DataResponse(['locations' => $this->getLocations(false)]);
	}

	public function delete(int $id): DataResponse {
		$qb = $this->db->getQueryBuilder();
		$qb->update('workplanner_locations')
			->set('active', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT))
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
			->executeStatement();

		return new DataResponse(['locations' => $this->getLocations(false)]);
	}

	private function getLocations(bool $activeOnly): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('workplanner_locations')
			->orderBy('sort_order', 'ASC')
			->addOrderBy('name', 'ASC');

		if ($activeOnly) {
			$qb->where($qb->expr()->eq('active', $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT)));
		}

		$result = $qb->executeQuery();
		$rows = $result->fetchAll();
		$result->closeCursor();

		return array_map(fn(array $row): array => [
			'id' => (int)$row['id'],
			'name' => $row['name'],
			'color' => $row['color'],
			'description' => $row['description'] ?? '',
			'active' => (bool)$row['active'],
			'sortOrder' => (int)$row['sort_order'],
		], $rows);
	}

	private function normalizeColor(string $color): string {
		$color = trim($color);
		return preg_match('/^#[0-9a-fA-F]{6}$/', $color) === 1 ? $color : '#2f6fdd';
	}
}
