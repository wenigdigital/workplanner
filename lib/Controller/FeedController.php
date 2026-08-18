<?php

declare(strict_types=1);

namespace OCA\Workplanner\Controller;

use DateTimeImmutable;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\Response;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\IURLGenerator;

class FeedController extends Controller {
	private const TOKEN_KEY = 'team_feed_token';

	public function __construct(
		string $appName,
		IRequest $request,
		private IDBConnection $db,
		private IConfig $config,
		private IURLGenerator $urlGenerator,
	) {
		parent::__construct($appName, $request);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function info(): DataResponse {
		$token = $this->getOrCreateToken();

		return new DataResponse([
			'url' => $this->urlGenerator->linkToRouteAbsolute('workplanner.feed.show', ['token' => $token]),
		]);
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function show(string $token): Response {
		if (!hash_equals($this->getOrCreateToken(), $token)) {
			return new NotFoundResponse();
		}

		$response = new DataDisplayResponse($this->buildCalendar());
		$response->addHeader('Content-Type', 'text/calendar; charset=utf-8');
		$response->addHeader('Content-Disposition', 'inline; filename="workplanner.ics"');
		$response->addHeader('Cache-Control', 'no-cache, no-store, must-revalidate');

		return $response;
	}

	private function getOrCreateToken(): string {
		$token = $this->config->getAppValue('workplanner', self::TOKEN_KEY, '');
		if ($token === '') {
			$token = bin2hex(random_bytes(24));
			$this->config->setAppValue('workplanner', self::TOKEN_KEY, $token);
		}

		return $token;
	}

	private function buildCalendar(): string {
		$lines = [
			'BEGIN:VCALENDAR',
			'VERSION:2.0',
			'PRODID:-//Workplanner//Nextcloud Workplanner//EN',
			'CALSCALE:GREGORIAN',
			'METHOD:PUBLISH',
			'X-WR-CALNAME:' . $this->escapeText('Workplanner'),
			'X-WR-CALDESC:' . $this->escapeText('Read-only team work location planning'),
			'REFRESH-INTERVAL;VALUE=DURATION:PT1H',
			'X-PUBLISHED-TTL:PT1H',
		];

		foreach ($this->getPlans() as $plan) {
			$lines = array_merge($lines, $this->buildEvent($plan));
		}

		$lines[] = 'END:VCALENDAR';

		return implode("\r\n", $this->foldLines($lines)) . "\r\n";
	}

	private function getPlans(): array {
		$start = (new DateTimeImmutable('-1 month'))->format('Y-m-d');
		$end = (new DateTimeImmutable('+18 months'))->format('Y-m-d');

		$qb = $this->db->getQueryBuilder();
		$qb->select('p.id', 'p.user_id', 'p.day', 'p.note', 'p.note_text', 'p.time_value', 'p.time_from', 'p.time_to', 'l.name')
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

		return $rows;
	}

	private function buildEvent(array $plan): array {
		$day = (string)$plan['day'];
		$timeFrom = (string)($plan['time_from'] ?? '');
		$timeTo = (string)($plan['time_to'] ?? '');
		$location = (string)($plan['name'] ?? 'Standort');
		$userId = (string)$plan['user_id'];
		$note = (string)((($plan['note_text'] ?? '') !== '') ? $plan['note_text'] : ($plan['note'] ?? ''));
		$summary = trim($location . ' - ' . $userId, ' -');
		$description = trim(implode("\n", array_filter([
			'Benutzer: ' . $userId,
			'Standort: ' . $location,
			$this->formatTimeRange($timeFrom, $timeTo) !== '' ? 'Zeit: ' . $this->formatTimeRange($timeFrom, $timeTo) : '',
			$note,
		])));

		$lines = [
			'BEGIN:VEVENT',
			'UID:workplanner-' . (int)$plan['id'] . '@nextcloud',
			'DTSTAMP:' . gmdate('Ymd\THis\Z'),
			'SUMMARY:' . $this->escapeText($summary),
			'LOCATION:' . $this->escapeText($location),
			'DESCRIPTION:' . $this->escapeText($description),
		];

		if ($timeFrom !== '' && $timeTo !== '') {
			$lines[] = 'DTSTART:' . str_replace('-', '', $day) . 'T' . str_replace(':', '', $timeFrom) . '00';
			$lines[] = 'DTEND:' . str_replace('-', '', $day) . 'T' . str_replace(':', '', $timeTo) . '00';
		} else {
			$lines[] = 'DTSTART;VALUE=DATE:' . str_replace('-', '', $day);
			$lines[] = 'DTEND;VALUE=DATE:' . (new DateTimeImmutable($day))->modify('+1 day')->format('Ymd');
		}

		$lines[] = 'TRANSP:TRANSPARENT';
		$lines[] = 'END:VEVENT';

		return $lines;
	}

	private function formatTimeRange(string $timeFrom, string $timeTo): string {
		if ($timeFrom !== '' && $timeTo !== '') {
			return $timeFrom . ' - ' . $timeTo;
		}

		return $timeFrom !== '' ? $timeFrom : $timeTo;
	}

	private function escapeText(string $text): string {
		return str_replace(
			["\\", "\r\n", "\n", "\r", ',', ';'],
			["\\\\", "\\n", "\\n", "\\n", "\\,", "\\;"],
			$text
		);
	}

	private function foldLines(array $lines): array {
		$folded = [];
		foreach ($lines as $line) {
			while (strlen($line) > 73) {
				$folded[] = substr($line, 0, 73);
				$line = ' ' . substr($line, 73);
			}
			$folded[] = $line;
		}

		return $folded;
	}
}
