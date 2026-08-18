<?php

declare(strict_types=1);

namespace OCA\Workplanner\Controller;

use DateTimeImmutable;
use Exception;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IRequest;

class PlanController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private IDBConnection $db,
		?string $UserId,
	) {
		parent::__construct($appName, $request);
		$this->userId = $UserId;
	}

	private ?string $userId;

	#[NoAdminRequired]
	public function index(string $start, string $end): DataResponse {
		if (!$this->isValidDay($start) || !$this->isValidDay($end) || $start > $end) {
			return new DataResponse(['error' => 'Invalid date range.'], 400);
		}

		return new DataResponse([
			'today' => $this->today(),
			'userId' => $this->userId,
			'locations' => $this->getLocations(),
			'plans' => $this->getPlans($start, $end),
		]);
	}

	#[NoAdminRequired]
	public function save(string $day, ?int $locationId = null, string $note = '', string $timeFrom = '', string $timeTo = '', int $id = 0): DataResponse {
		if (!$this->userId) {
			return new DataResponse(['error' => 'Not logged in.'], 401);
		}
		if (!$this->isValidDay($day)) {
			return new DataResponse(['error' => 'Invalid date.'], 400);
		}
		if ($this->isPast($day)) {
			return new DataResponse(['error' => 'Past planning entries cannot be changed.'], 403);
		}
		if ($locationId !== null && !$this->locationExists($locationId)) {
			return new DataResponse(['error' => 'Unknown location.'], 400);
		}

		$now = time();
		$note = mb_substr(trim($note), 0, 1000);
		$notePreview = mb_substr($note, 0, 255);
		$timeFrom = $this->normalizeTimeValue($timeFrom);
		$timeTo = $this->normalizeTimeValue($timeTo);
		$timeValue = $this->formatTimeRange($timeFrom, $timeTo);

		if ($id > 0) {
			$existingPlan = $this->findOwnPlanById($id);
			if ($existingPlan === null) {
				return new DataResponse(['error' => 'Planning entry not found.'], 404);
			}
			if ($this->isPast($existingPlan['day'])) {
				return new DataResponse(['error' => 'Past planning entries cannot be changed.'], 403);
			}

			$qb = $this->db->getQueryBuilder();
			$qb->update('workplanner_plans')
				->set('day', $qb->createNamedParameter($day))
				->set('location_id', $qb->createNamedParameter($locationId, IQueryBuilder::PARAM_INT))
				->set('note', $qb->createNamedParameter($notePreview))
				->set('note_text', $qb->createNamedParameter($note))
				->set('time_value', $qb->createNamedParameter($timeValue))
				->set('time_from', $qb->createNamedParameter($timeFrom))
				->set('time_to', $qb->createNamedParameter($timeTo))
				->set('updated_at', $qb->createNamedParameter($now, IQueryBuilder::PARAM_INT))
				->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
				->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($this->userId)))
				->executeStatement();
		} else {
			$qb = $this->db->getQueryBuilder();
			$qb->insert('workplanner_plans')
				->values([
					'user_id' => $qb->createNamedParameter($this->userId),
					'day' => $qb->createNamedParameter($day),
					'location_id' => $qb->createNamedParameter($locationId, IQueryBuilder::PARAM_INT),
					'note' => $qb->createNamedParameter($notePreview),
					'note_text' => $qb->createNamedParameter($note),
					'time_value' => $qb->createNamedParameter($timeValue),
					'time_from' => $qb->createNamedParameter($timeFrom),
					'time_to' => $qb->createNamedParameter($timeTo),
					'created_at' => $qb->createNamedParameter($now, IQueryBuilder::PARAM_INT),
					'updated_at' => $qb->createNamedParameter($now, IQueryBuilder::PARAM_INT),
				])
				->executeStatement();
		}

		return new DataResponse(['ok' => true]);
	}

	#[NoAdminRequired]
	public function delete(int $id): DataResponse {
		if (!$this->userId) {
			return new DataResponse(['error' => 'Not logged in.'], 401);
		}
		$existingPlan = $this->findOwnPlanById($id);
		if ($existingPlan === null) {
			return new DataResponse(['error' => 'Planning entry not found.'], 404);
		}
		if ($this->isPast($existingPlan['day'])) {
			return new DataResponse(['error' => 'Past planning entries cannot be changed.'], 403);
		}

		$qb = $this->db->getQueryBuilder();
		$qb->delete('workplanner_plans')
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($this->userId)))
			->andWhere($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
			->executeStatement();

		return new DataResponse(['ok' => true]);
	}

	private function getLocations(): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('workplanner_locations')
			->where($qb->expr()->eq('active', $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT)))
			->orderBy('sort_order', 'ASC')
			->addOrderBy('name', 'ASC');
		$result = $qb->executeQuery();
		$rows = $result->fetchAll();
		$result->closeCursor();

		return array_map(fn(array $row): array => [
			'id' => (int)$row['id'],
			'name' => $row['name'],
			'color' => $row['color'],
			'description' => $row['description'] ?? '',
		], $rows);
	}

	private function getPlans(string $start, string $end): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('p.id', 'p.user_id', 'p.day', 'p.location_id', 'p.note', 'p.note_text', 'p.time_value', 'p.time_from', 'p.time_to', 'l.name', 'l.color')
			->from('workplanner_plans', 'p')
			->leftJoin('p', 'workplanner_locations', 'l', $qb->expr()->eq('p.location_id', 'l.id'))
			->where($qb->expr()->gte('p.day', $qb->createNamedParameter($start)))
			->andWhere($qb->expr()->lte('p.day', $qb->createNamedParameter($end)))
			->orderBy('p.day', 'ASC')
			->addOrderBy('p.time_from', 'ASC')
			->addOrderBy('p.time_to', 'ASC')
			->addOrderBy('p.user_id', 'ASC')
			->addOrderBy('p.id', 'ASC');

		$result = $qb->executeQuery();
		$rows = $result->fetchAll();
		$result->closeCursor();

		return array_map(fn(array $row): array => [
			'id' => (int)$row['id'],
			'userId' => $row['user_id'],
			'day' => $row['day'],
			'locationId' => $row['location_id'] !== null ? (int)$row['location_id'] : null,
			'locationName' => $row['name'] ?? '',
			'color' => $row['color'] ?? '#6b7280',
			'note' => ($row['note_text'] ?? '') !== '' ? $row['note_text'] : ($row['note'] ?? ''),
			'timeFrom' => $row['time_from'] ?? '',
			'timeTo' => $row['time_to'] ?? '',
			'timeValue' => $this->formatTimeRange($row['time_from'] ?? '', $row['time_to'] ?? '') ?: ($row['time_value'] ?? ''),
			'editable' => $row['user_id'] === $this->userId && !$this->isPast($row['day']),
		], $rows);
	}

	private function findOwnPlanById(int $id): ?array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('id', 'day')
			->from('workplanner_plans')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($this->userId)))
			->setMaxResults(1);
		$result = $qb->executeQuery();
		$row = $result->fetch();
		$result->closeCursor();

		return $row ?: null;
	}

	private function locationExists(int $locationId): bool {
		$qb = $this->db->getQueryBuilder();
		$qb->select('id')
			->from('workplanner_locations')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($locationId, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('active', $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT)))
			->setMaxResults(1);
		$result = $qb->executeQuery();
		$row = $result->fetch();
		$result->closeCursor();

		return (bool)$row;
	}

	private function isValidDay(string $day): bool {
		if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $day) !== 1) {
			return false;
		}

		try {
			return (new DateTimeImmutable($day))->format('Y-m-d') === $day;
		} catch (Exception) {
			return false;
		}
	}

	private function today(): string {
		return (new DateTimeImmutable('today'))->format('Y-m-d');
	}

	private function isPast(string $day): bool {
		return $day < $this->today();
	}

	private function normalizeTimeValue(string $timeValue): string {
		$timeValue = trim($timeValue);
		return preg_match('/^\d{2}:\d{2}$/', $timeValue) === 1 ? $timeValue : '';
	}

	private function formatTimeRange(string $timeFrom, string $timeTo): string {
		if ($timeFrom !== '' && $timeTo !== '') {
			return $timeFrom . ' - ' . $timeTo;
		}

		return $timeFrom !== '' ? $timeFrom : $timeTo;
	}
}
