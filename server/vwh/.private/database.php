<?php

date_default_timezone_set("UTC");

abstract class UpdateRequest
{

	public readonly int $update_id_known; # when creating the request

	public function __construct(int $update_id_known)
	{
		$this->update_id_known = $update_id_known;
	}

	public final function dispatch(PDO $pdo): true|string
	{
		try {
			if ($pdo === null) {
				throw new Exception("no connection to the database");
			}

			if ($pdo->inTransaction() === false) {
				throw new Exception("connection to the database not in transaction");
			}

			$this->process($pdo);

			return true;
		} catch (Throwable $t) {
			return $t->getMessage();
		}
	}

	public function when_dispatch_fails(): void
	{
		// cleanup stuff that happened anywhere like consturctor but not at 'process'
		// PRACTICALLY used for image resources trashing in case of failure when dispaching
	}

	/**
	 * Assumes the PDO given has connected and selected the database while in a transaction wihout interuptions.
	 * 
	 * The fucntion should not handle exceptions involving the PDO and should throw exceptions when cannot complete the request because of the data given. The message of the exception should be the reason.
	 */
	protected abstract function process(PDO $pdo): void;

}

class SecretaryAddInterviewee extends UpdateRequest
{

	private readonly string $iwee_email;

	public function __construct(int $update_id_known, string $iwee_email)
	{
		parent::__construct($update_id_known);

		$email = filter_var(trim($iwee_email), FILTER_SANITIZE_EMAIL);
		$email = filter_var($email, FILTER_VALIDATE_EMAIL);

		if ($email === false || $email !== $iwee_email) {
			throw new InvalidArgumentException("invalid email address provided");
		}

		$this->iwee_email = $email;
	}

	public function process(PDO $pdo): void
	{
		$statement = $pdo->query("INSERT
			INTO interviewee (email, active, available)
			VALUES ('{$this->iwee_email}', true, true)
			ON CONFLICT (email) DO NOTHING;
		");

		if ($statement === false) {
			throw new Exception("failed to execute query");
		}
	}

}

class SecretaryDeleteInterviewee extends UpdateRequest
{

	private readonly int $iwee_id;

	public function __construct(int $update_id_known, int $iwee_id)
	{
		parent::__construct($update_id_known);

		$this->iwee_id = $iwee_id;
	}

	public function process(PDO $pdo): void
	{
		$statement = $pdo->query("DELETE
			FROM interviewee
			WHERE id = {$this->iwee_id}
			AND available = true;
		");
		# TODO probably break it into two queries so the exception can be more spesific

		if ($statement === false) {
			throw new Exception("failed to execute query");
		}

		if ($statement->rowCount() === 0) {
			throw new Exception("interviewee still unavailable at the moment, can be deleted only when available");
		}
	}

}

class SecretaryEnqueueDequeue extends UpdateRequest
{

	private readonly int $iwee_id;
	private readonly array $iwer_ids_to_enqueue;

	public function __construct(int $update_id_known, int $iwee_id, int ...$iwer_ids_to_enqueue)
	{
		parent::__construct($update_id_known);

		$this->iwee_id = $iwee_id;
		$this->iwer_ids_to_enqueue = $iwer_ids_to_enqueue;
	}

	protected function process(PDO $pdo): void
	{
		$timestamp_enqueuing = $pdo->query("SELECT NOW();")->fetch()['now'];

		if (count($this->iwer_ids_to_enqueue) > 0) {
			$insert = "INSERT INTO interview (id_interviewer, id_interviewee, state_, state_timestamp) VALUES ";

			$values = [];

			foreach ($this->iwer_ids_to_enqueue as $iwer_id) {
				array_push($values, "({$iwer_id}, {$this->iwee_id}, 'ENQUEUED', '{$timestamp_enqueuing}')");
			}

			$insert .= implode(", ", $values);
			$insert .= " ON CONFLICT ON CONSTRAINT pair_interviewer_interviewee DO ";
			$insert .= "UPDATE
				SET state_timestamp = EXCLUDED.state_timestamp
				WHERE interview.state_ = EXCLUDED.state_
			;";

			if ($pdo->query($insert) === false) {
				throw new Exception("failed to execute query");
			}
		}

		$statement = $pdo->query("DELETE
			FROM interview
			WHERE id_interviewee = {$this->iwee_id}
			AND state_ = 'ENQUEUED'
			AND state_timestamp < '{$timestamp_enqueuing}'
		;");

		if ($statement === false) {
			throw new Exception("failed to execute query");
		}
	}

}

class CompanyNoShowCandidate extends UpdateRequest
{

	private readonly int $iwer_id;
	private readonly int $iwee_id;

	public function __construct(int $update_id_known, int $iwer_id, int $iwee_id)
	{
		parent::__construct($update_id_known);

		$this->iwer_id = $iwer_id;
		$this->iwee_id = $iwee_id;
	}

	protected function process(PDO $pdo): void
	{
		$statement = $pdo->query("DELETE
			FROM interview
			WHERE id_interviewer = {$this->iwer_id}
			AND id_interviewee = {$this->iwee_id}
			AND state_ IN ('CALLING', 'DECISION')
		;");

		if ($statement === false) {
			throw new Exception("failed to execute query");
		}
	}

}
;

class SecretaryActiveInactiveFlipInterviewee extends UpdateRequest
{

	private readonly int $interviewee_id;

	public function __construct(int $update_id_known, int $interviewee_id)
	{
		parent::__construct($update_id_known);

		$this->interviewee_id = $interviewee_id;
	}

	protected function process(PDO $pdo): void
	{
		$statement = $pdo->query("UPDATE interviewee
			SET
				active = NOT active
			WHERE
				id = {$this->interviewee_id}
			RETURNING active;
		");

		if ($statement === false) {
			throw new Exception("failed to execute query");
		}

		if ($statement->fetch()['active'] === false) {
			$statement = $pdo->query("UPDATE interview
				SET
					state_ = 'ENQUEUED',
					state_timestamp = CURRENT_TIMESTAMP
				WHERE
					id_interviewee = {$this->interviewee_id}
					AND state_ IN ('CALLING', 'DECISION', 'HAPPENING');
			");

			if ($statement === false) {
				throw new Exception("failed to execute query");
			}
		}
	}

}

class CandidateToggleActiveState extends UpdateRequest
{
	private int $interviewee_id;

	public function __construct(int $update_id_known, int $interviewee_id)
	{
		parent::__construct($update_id_known);
		$this->interviewee_id = $interviewee_id;
	}

	protected function process(PDO $pdo): void
	{
		$statement = $pdo->query("UPDATE interviewee
			SET
				active = NOT active
			WHERE
				id = {$this->interviewee_id}
			RETURNING active;
		");

		if ($statement === false) {
			throw new Exception("failed to execute query");
		}

		if ($statement->fetch()['active'] === false) {
			$statement = $pdo->query("UPDATE interview
				SET
					state_ = 'ENQUEUED',
					state_timestamp = CURRENT_TIMESTAMP
				WHERE
					id_interviewee = {$this->interviewee_id}
					AND state_ IN ('CALLING', 'DECISION', 'HAPPENING');
			");

			if ($statement === false) {
				throw new Exception("failed to execute query");
			}
		}
	}
}

class SecretaryAddInterviewer extends UpdateRequest
{

	protected static string $iwer_image_resource_url_base = '/resources/images/interviewer/';
	protected static string $iwer_image_resource_url_placeholder = '/resources/images/interviewer/placeholder.svg';

	public static function iwerImageResourceUrlPlaceholder(): string
	{
		return SecretaryAddInterviewer::$iwer_image_resource_url_placeholder;
	}

	protected readonly string $iwer_name;
	protected readonly string $iwer_table;
	protected readonly string $iwer_image_resource_url;

	public function __construct(
		int $update_id_known,
		string $iwer_name,
		string $iwer_table,
		?array $iwer_image_file,
	) {
		parent::__construct($update_id_known);

		$iwer_name = $name = trim($iwer_name);
		# TODO sanitazation and validation of "name"
		if ($name !== $iwer_name) {
			throw new InvalidArgumentException("invalid name provided");
		}

		$this->iwer_name = $name;

		// ===

		$iwer_table = $table = trim($iwer_table);
		# TODO sanitazation and validation of "table"
		if ($table !== $iwer_table) {
			throw new InvalidArgumentException("invalid table provided");
		}

		$this->iwer_table = $table;

		// ===

		if ($iwer_image_file !== null && $iwer_image_file['error'] !== UPLOAD_ERR_NO_FILE) {
			if ($iwer_image_file['error'] !== UPLOAD_ERR_OK) {
				throw new InvalidArgumentException("invalid image provided, probably too big (error code " . $iwer_image_file['error'] . ")");
			}

			if ($iwer_image_file['size'] > 5 * 1024 * 1024) {
				throw new InvalidArgumentException("invalid image provided, file size exceeds 5MB limit");
			}

			$this->iwer_image_resource_url = $url = SecretaryAddInterviewer::$iwer_image_resource_url_base . uniqid("i");

			# TODO change ownership of images directory to user www-data
			if (move_uploaded_file($iwer_image_file['tmp_name'], $_SERVER['DOCUMENT_ROOT'] . $url) === false) {
				throw new Exception("unable to store the image permanently");
			}
		} else {
			$this->iwer_image_resource_url = SecretaryAddInterviewer::$iwer_image_resource_url_placeholder;
		}
	}

	protected function process(PDO $pdo): void
	{
		$statement = $pdo->query("INSERT
			INTO interviewer (name, image_resource_url, table_number, active, available)
			VALUES ('{$this->iwer_name}', '{$this->iwer_image_resource_url}', '{$this->iwer_table}', true, true);
		");

		if ($statement === false) {
			throw new Exception("failed to execute query");
		}
	}

	public function when_dispatch_fails(): void
	{
		if ($this->iwer_image_resource_url === SecretaryAddInterviewer::$iwer_image_resource_url_placeholder) {
			return;
		}

		if (file_exists($_SERVER['DOCUMENT_ROOT'] . $this->iwer_image_resource_url)) {
			unlink($_SERVER['DOCUMENT_ROOT'] . $this->iwer_image_resource_url);
		}
	}

}
;

class SecretaryEditInterviewer extends SecretaryAddInterviewer
{

	private readonly int $iwer_id;

	public function __construct(
		int $update_id_known,
		int $iwer_id,
		string $iwer_name,
		string $iwer_table,
		?array $iwer_image_file,
	) {
		parent::__construct($update_id_known, $iwer_name, $iwer_table, $iwer_image_file);

		$this->iwer_id = $iwer_id;
	}

	protected function process(PDO $pdo): void
	{
		$statement = $pdo->query("SELECT image_resource_url FROM interviewer WHERE id = {$this->iwer_id};");

		if ($statement === false) {
			throw new Exception("failed to execute query");
		}

		$image_url_new = $this->iwer_image_resource_url;
		$image_url_old = $statement->fetch()['image_resource_url'];

		$image_url_update = $image_url_old;

		if ($image_url_new !== SecretaryAddInterviewer::$iwer_image_resource_url_placeholder) {
			if ($image_url_old !== SecretaryAddInterviewer::$iwer_image_resource_url_placeholder) {
				copy($_SERVER['DOCUMENT_ROOT'] . $image_url_new, $_SERVER['DOCUMENT_ROOT'] . $image_url_old);
				unlink($_SERVER['DOCUMENT_ROOT'] . $image_url_new);
			} else {
				$image_url_update = $image_url_new;
			}
		}

		$statement = $pdo->query("UPDATE interviewer
			SET
				name = '{$this->iwer_name}',
				image_resource_url = '{$image_url_update}',
				table_number = '{$this->iwer_table}'
			WHERE
				id = {$this->iwer_id}
			;
		");

		if ($statement === false) {
			throw new Exception("failed to execute query");
		}
	}

}
;

class SecretaryDeleteInterviewer extends UpdateRequest
{

	private readonly int $iwer_id;

	public function __construct(int $update_id_known, int $iwer_id)
	{
		parent::__construct($update_id_known);

		$this->iwer_id = $iwer_id;
	}

	protected function process(PDO $pdo): void
	{
		$statement = $pdo->query("DELETE
			FROM interviewer
			WHERE id = {$this->iwer_id}
			AND available = true
			RETURNING image_resource_url;
		");
		# TODO probably break it into two queries so the exception can be more specific

		if ($statement === false) {
			throw new Exception("failed to execute query");
		}

		if ($statement->rowCount() === 0) {
			throw new Exception("interviewer still unavailable at the moment, can be deleted only when available");
		}

		if (($iru = $statement->fetch()['image_resource_url']) !== SecretaryAddInterviewer::iwerImageResourceUrlPlaceholder()) {
			if (file_exists($_SERVER['DOCUMENT_ROOT'] . $iru)) {
				unlink($_SERVER['DOCUMENT_ROOT'] . $iru);
			}
		}
	}

}
;

class SystemCallingToDecision extends UpdateRequest
{

	private readonly int $after_seconds;

	public function __construct(int $update_id_known, int $after_seconds)
	{
		parent::__construct($update_id_known);

		$this->after_seconds = $after_seconds;
	}

	protected function process(PDO $pdo): void
	{
		$statement = $pdo->query("UPDATE interview
			SET
				state_ = 'DECISION',
				state_timestamp = CURRENT_TIMESTAMP
			WHERE
				state_ = 'CALLING' AND
				CURRENT_TIMESTAMP - state_timestamp > INTERVAL '{$this->after_seconds} seconds';
		");

		if ($statement === false) {
			throw new Exception("failed to execute query");
		}

		if ($statement->rowCount() > 0) {
			$this->changes_happened = true;
		}
	}

	public bool $changes_happened = false;

}
;

class GatekeeperActiveInactiveFlipInterviewer extends UpdateRequest
{

	private readonly int $interviewer_id;

	public function __construct(int $update_id_known, int $interviewer_id)
	{
		parent::__construct($update_id_known);

		$this->interviewer_id = $interviewer_id;
	}

	protected function process(PDO $pdo): void
	{
		$statement = $pdo->query("UPDATE interviewer
			SET
				active = NOT active
			WHERE
				id = {$this->interviewer_id}
			RETURNING active;
		");

		if ($statement === false) {
			throw new Exception("failed to execute query");
		}

		if ($statement->fetch()['active'] === false) {
			$statement = $pdo->query("UPDATE interview
				SET
					state_ = 'ENQUEUED',
					state_timestamp = CURRENT_TIMESTAMP
				WHERE
					id_interviewer = {$this->interviewer_id}
					AND state_ IN ('CALLING', 'DECISION', 'HAPPENING');
			");

			if ($statement === false) {
				throw new Exception("failed to execute query");
			}
		}
	}

}
;

class GatekeeperCallingOrDecisionToHappening extends UpdateRequest
{

	private readonly int $interview_id;

	public function __construct(int $update_id_known, int $interview_id)
	{
		parent::__construct($update_id_known);

		$this->interview_id = $interview_id;
	}

	protected function process(PDO $pdo): void
	{
		$statement = $pdo->query("UPDATE interview
			SET
				state_ = 'HAPPENING',
				state_timestamp = CURRENT_TIMESTAMP
			WHERE
				interview.id = {$this->interview_id}
				AND state_ in ('CALLING', 'DECISION')
				;
		");

		if ($statement === false) {
			throw new Exception("failed to execute query");
		}
	}

}
;

class GatekeeperCallingOrDecisionOrHappeningToDequeued extends UpdateRequest
{

	private readonly int $interview_id;

	public function __construct(int $update_id_known, int $interview_id)
	{
		parent::__construct($update_id_known);

		$this->interview_id = $interview_id;
	}

	protected function process(PDO $pdo): void
	{
		$statement = $pdo->query("DELETE
			FROM interview
			WHERE id = {$this->interview_id}
			AND state_ in ('CALLING','DECISION','HAPPENING');
		");

		// TODO may confuse in practice, will see, same in other classes
		// if($statement->rowCount() === 0) {
		// 	throw new Exception("has already changed state");
		// }

		if ($statement === false) {
			throw new Exception("failed to execute query");
		}
	}

}
;

class GatekeeperHappeningToCompleted extends UpdateRequest
{

	private readonly int $interview_id;

	public function __construct(int $update_id_known, int $interview_id)
	{
		parent::__construct($update_id_known);

		$this->interview_id = $interview_id;
	}

	protected function process(PDO $pdo): void
	{
		$statement = $pdo->query("UPDATE interview
			SET
				state_ = 'COMPLETED',
				state_timestamp = CURRENT_TIMESTAMP
			WHERE
				interview.id = {$this->interview_id}
				AND state_ = 'HAPPENING'
				;
		");

		if ($statement === false) {
			throw new Exception("failed to execute query");
		}
	}

}
;

class GatekeeperHappeningToCompletedAndPause extends UpdateRequest
{

	private readonly int $interview_id;
	private readonly int $interviewer_id;

	public function __construct(int $update_id_known, int $interview_id, int $interviewer_id)
	{
		parent::__construct($update_id_known);

		$this->interview_id = $interview_id;
		$this->interviewer_id = $interviewer_id;
	}

	protected function process(PDO $pdo): void
	{
		// 1. Complete the interview
		$statement = $pdo->query("UPDATE interview
			SET
				state_ = 'COMPLETED',
				state_timestamp = CURRENT_TIMESTAMP
			WHERE
				interview.id = {$this->interview_id}
				AND state_ = 'HAPPENING'
				;
		");

		if ($statement === false || $statement->rowCount() === 0) {
			throw new Exception("failed to execute completion query (interview not found or not happening)");
		}

		// 2. Pause the interviewer (set active to false; available is recalculated by the routine)
		$statement = $pdo->query("UPDATE interviewer
			SET
				active = false
			WHERE
				id = {$this->interviewer_id}
				;
		");

		if ($statement === false || $statement->rowCount() === 0) {
			throw new Exception("failed to execute pause query (interviewer not found)");
		}
	}

}
;

class SecretaryGenerateInterviewerToken extends UpdateRequest
{

	private readonly int $interviewer_id;
	private readonly string $token;

	public function __construct(int $update_id_known, int $interviewer_id, string $token)
	{
		parent::__construct($update_id_known);

		$this->interviewer_id = $interviewer_id;
		$this->token = $token;
	}

	protected function process(PDO $pdo): void
	{
		$statement = $pdo->prepare("UPDATE interviewer SET token = :token, token_expires_at = CURRENT_TIMESTAMP + INTERVAL '10 minutes' WHERE id = :id");
		$statement->bindValue(':token', $this->token);
		$statement->bindValue(':id', $this->interviewer_id, PDO::PARAM_INT);

		if ($statement->execute() === false) {
			throw new Exception("failed to generate token");
		}
	}

}
;

interface Database
{

	/**
	 * @return string of the operator type when the password matches
	 * @return false when the password has no match
	 */
	public function operator_mapping(string $password, string &$timestamp): string|false;

	public function operator_still_alive(string $timestamp): bool;

	public function company_mapping(string $token): int|false;

	public function company_still_alive(int $interviewer_id): bool;

	/**
	 * @return true on success
	 * @return string on failure with the reason
	 */
	public function update_handle(UpdateRequest $update_request): true|string;

	/**
	 * @return int id
	 */
	public function update_happened_recent(): int;

	public function retrieve(string ...$from_table): array; # TODO rework to utilize views

	public function retrieve_gatekeeper_view(): array;

	public function retrieve_company_view(): array;

	public function retrieve_queues_view(): array;
	public function retrieve_companies_view(): array;

	/**
	 * Lookup a candidate by google_sub. Returns the interviewee row or false.
	 */
	public function candidate_by_google_sub(string $google_sub): array|false;

	/**
	 * Lookup a candidate by email. Returns the interviewee row or false.
	 */
	public function candidate_by_email(string $email): array|false;

	/**
	 * Retrieves all interviewers (companies) and the candidate's current queue entries.
	 * Returns ['interviewers' => [...], 'interviews' => [...], 'update' => int]
	 */
	public function candidate_dashboard_view(int $interviewee_id): array;
}

interface DatabaseAdmin
{
	public function create();
	public function drop();
	public function operator_entries(): array;
	public function operator_add(string $type, string $password, string $reminder): bool;
	public function operator_remove(int|bool $id): bool;
	public function operator_update_password(int $id, string $new_password): bool;
}

interface DatabaseJobPositions
{
	public function retrieve_jobs_of(int $interviewer_id, bool $untagged_only = false): array|false;

	public function insert_job(string $title, string $description, int $interviewer_id): bool;

	public function delete_job(int $id): bool;

	public function update_jobs_tags(array $jobs_id_tag): bool;

	public function retrieve_interviewers_and_jobs_with_tags(array $tags): array|false;
}

class UpdateHandleExpectedException extends Exception
{
}

class UpdateHandleUnexpectedException extends Exception
{
}

/**
 * Self-registration by a candidate (student).
 * 
 * This goes through update_handle() like all writes, so it acquires
 * EXCLUSIVE LOCK on interview and respects update_id_known — meaning
 * concurrent Secretary or Student operations cannot conflict.
 * 
 * Atomically:
 *   1. Upserts the interviewee record (email + profile fields)
 *   2. Enrolls in selected companies (same logic as SecretaryEnqueueDequeue)
 *   3. Removes old ENQUEUED entries not in the new list
 * 
 * If the DB insert fails after CV file was uploaded,
 * when_dispatch_fails() cleans up the file.
 */
class CandidateSelfRegister extends UpdateRequest
{

	private static string $cv_base = '/resources/cv/';

	private readonly string $email;
	private readonly string $google_sub;
	private readonly string $display_name;
	private readonly string $avatar_url;
	private readonly string $department;
	private readonly string $masters;
	private readonly string $interests; // comma-separated
	private readonly ?string $cv_resource_url;
	private readonly array $interviewer_ids; // companies to enqueue
	private readonly ?array $cv_file_tmp;

	public function __construct(
		int $update_id_known,
		string $email,
		string $google_sub,
		string $display_name,
		string $avatar_url,
		string $department,
		string $masters,
		string $interests,
		?array $cv_file,
		array $interviewer_ids
	) {
		parent::__construct($update_id_known);

		$email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			throw new InvalidArgumentException('invalid email address');
		}
		$this->email = $email;
		$this->google_sub = trim($google_sub);
		$this->display_name = trim($display_name);
		$this->avatar_url = trim($avatar_url);
		$this->department = trim($department);
		$this->masters = trim($masters);
		$this->interests = trim($interests);
		$this->interviewer_ids = array_map('intval', $interviewer_ids);

		$this->cv_resource_url = null;

		if ($cv_file !== null && ($cv_file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
			if ($cv_file['type'] !== 'application/pdf') {
				throw new InvalidArgumentException('CV must be a PDF file');
			}
			$this->cv_file_tmp = $cv_file;
		} else {
			$this->cv_file_tmp = null;
		}
	}

	protected function process(PDO $pdo): void
	{
		if (count($this->interviewer_ids) > 5) {
			throw new UpdateHandleExpectedException('You can only join up to 5 company queues at the same time. Please unselect some companies.');
		}

		// 1. Upsert interviewee (creates if new, updates profile if existing)
		$cv_sql = $this->cv_resource_url !== null
			? "'{$this->cv_resource_url}'"
			: "COALESCE((SELECT cv_resource_url FROM interviewee WHERE email = '{$this->email}'), NULL)";

		$stmt = $pdo->prepare(
			"INSERT INTO interviewee
				(email, google_sub, display_name, avatar_url, department, masters, interests, cv_resource_url, active, available)
			VALUES
				(:email, :sub, :name, :avatar, :dept, :masters, :interests, {$cv_sql}, true, true)
			ON CONFLICT (email) DO UPDATE SET
				google_sub      = EXCLUDED.google_sub,
				display_name    = EXCLUDED.display_name,
				avatar_url      = EXCLUDED.avatar_url,
				department      = EXCLUDED.department,
				masters         = EXCLUDED.masters,
				interests       = EXCLUDED.interests,
				cv_resource_url = CASE WHEN EXCLUDED.cv_resource_url IS NOT NULL
									 THEN EXCLUDED.cv_resource_url
									 ELSE interviewee.cv_resource_url END
			RETURNING id;"
		);
		$stmt->bindValue(':email', $this->email);
		$stmt->bindValue(':sub', $this->google_sub);
		$stmt->bindValue(':name', $this->display_name);
		$stmt->bindValue(':avatar', $this->avatar_url);
		$stmt->bindValue(':dept', $this->department);
		$stmt->bindValue(':masters', $this->masters);
		$stmt->bindValue(':interests', $this->interests);

		if ($stmt->execute() === false) {
			throw new Exception('failed to upsert interviewee');
		}

		$iwee_id = (int) $stmt->fetch()['id'];

		if ($this->cv_file_tmp !== null) {
			$url = self::$cv_base . 'cv_' . $iwee_id . '.pdf';
			if (!move_uploaded_file($this->cv_file_tmp['tmp_name'], $_SERVER['DOCUMENT_ROOT'] . $url)) {
				throw new Exception('unable to store CV file');
			}

			// Update the record with the URL now that it's moved
			if ($pdo->query("UPDATE interviewee SET cv_resource_url = '{$url}' WHERE id = {$iwee_id}") === false) {
				throw new Exception('failed to set cv_resource_url');
			}
		}

		// 2. Enqueue for selected companies (same logic as SecretaryEnqueueDequeue)
		$ts = $pdo->query('SELECT NOW();')->fetch()['now'];

		if (count($this->interviewer_ids) > 0) {
			$values = implode(', ', array_map(
				fn($id) => "({$id}, {$iwee_id}, 'ENQUEUED', '{$ts}')",
				$this->interviewer_ids
			));
			$insert = "INSERT INTO interview (id_interviewer, id_interviewee, state_, state_timestamp)
					   VALUES {$values}
					   ON CONFLICT ON CONSTRAINT pair_interviewer_interviewee DO
					   UPDATE SET state_timestamp = EXCLUDED.state_timestamp
					   WHERE interview.state_ = EXCLUDED.state_;";
			if ($pdo->query($insert) === false) {
				throw new Exception('failed to enqueue for companies');
			}
		}

		// 3. Remove ENQUEUED entries for companies the student de-selected
		if (count($this->interviewer_ids) > 0) {
			$ids_list = implode(',', $this->interviewer_ids);
			$dequeue_sql = "DELETE FROM interview
				WHERE id_interviewee = {$iwee_id}
				AND state_ = 'ENQUEUED'
				AND state_timestamp < '{$ts}'
				AND id_interviewer NOT IN ({$ids_list});";
		} else {
			$dequeue_sql = "DELETE FROM interview
				WHERE id_interviewee = {$iwee_id}
				AND state_ = 'ENQUEUED'
				AND state_timestamp < '{$ts}';";
		}
		if ($pdo->query($dequeue_sql) === false) {
			throw new Exception('failed to dequeue old companies');
		}
	}

	public function when_dispatch_fails(): void
	{
		if ($this->cv_resource_url !== null) {
			$path = $_SERVER['DOCUMENT_ROOT'] . $this->cv_resource_url;
			if (file_exists($path)) {
				unlink($path);
			}
		}
	}

}

/**
 * Update CV for an existing candidate.
 */
class CandidateUpdateCV extends UpdateRequest
{
	private static string $cv_base = '/resources/cv/';
	private readonly int $iwee_id;
	private readonly string $cv_resource_url;

	public function __construct(int $update_id_known, int $iwee_id, array $cv_file)
	{
		parent::__construct($update_id_known);
		$this->iwee_id = $iwee_id;

		if (($cv_file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
			throw new InvalidArgumentException('No file uploaded or upload error');
		}
		if ($cv_file['type'] !== 'application/pdf') {
			throw new InvalidArgumentException('CV must be a PDF file');
		}

		$url = self::$cv_base . 'cv_' . $iwee_id . '.pdf';
		if (!move_uploaded_file($cv_file['tmp_name'], $_SERVER['DOCUMENT_ROOT'] . $url)) {
			throw new Exception('unable to store CV file');
		}
		$this->cv_resource_url = $url;
	}

	protected function process(PDO $pdo): void
	{
		// Fetch the old CV URL
		$stmt_old = $pdo->prepare("SELECT cv_resource_url FROM interviewee WHERE id = :id");
		$stmt_old->execute([':id' => $this->iwee_id]);
		$old_cv = $stmt_old->fetchColumn();

		// Update the CV URL in the database
		$stmt = $pdo->prepare("UPDATE interviewee SET cv_resource_url = :cv WHERE id = :id");
		$stmt->bindValue(':cv', $this->cv_resource_url);
		$stmt->bindValue(':id', $this->iwee_id, PDO::PARAM_INT);

		if ($stmt->execute() === false) {
			throw new Exception('failed to update CV resource URL');
		}

		// If successful, delete the old file
		if ($old_cv && file_exists($_SERVER['DOCUMENT_ROOT'] . $old_cv)) {
			unlink($_SERVER['DOCUMENT_ROOT'] . $old_cv);
		}
	}

	public function when_dispatch_fails(): void
	{
		if ($this->cv_resource_url !== null) {
			$path = $_SERVER['DOCUMENT_ROOT'] . $this->cv_resource_url;
			if (file_exists($path)) {
				unlink($path);
			}
		}
	}
}

/**
 * Candidate leaves (dequeues themselves from) a single ENQUEUED company.
 * Refuses if the state is no longer ENQUEUED (CALLING/HAPPENING/etc.).
 */
class CandidateLeaveQueue extends UpdateRequest
{

	private readonly int $iwee_id;
	private readonly int $iwer_id;

	public function __construct(int $update_id_known, int $iwee_id, int $iwer_id)
	{
		parent::__construct($update_id_known);
		$this->iwee_id = $iwee_id;
		$this->iwer_id = $iwer_id;
	}

	protected function process(PDO $pdo): void
	{
		$stmt = $pdo->query(
			"DELETE FROM interview
			WHERE id_interviewee = {$this->iwee_id}
			AND id_interviewer   = {$this->iwer_id}
			AND state_ = 'ENQUEUED';"
		);
		if ($stmt === false) {
			throw new Exception('failed to execute query');
		}
		if ($stmt->rowCount() === 0) {
			throw new UpdateHandleExpectedException(
				'Cannot leave: your interview is already in progress or completed.'
			);
		}
	}

}

/**
 * Candidate joins a single additional company queue.
 */
class CandidateJoinQueue extends UpdateRequest
{

	private readonly int $iwee_id;
	private readonly int $iwer_id;

	public function __construct(int $update_id_known, int $iwee_id, int $iwer_id)
	{
		parent::__construct($update_id_known);
		$this->iwee_id = $iwee_id;
		$this->iwer_id = $iwer_id;
	}

	protected function process(PDO $pdo): void
	{
		$stmt_count = $pdo->query("SELECT COUNT(*) FROM interview WHERE id_interviewee = {$this->iwee_id} AND state_ = 'ENQUEUED'");
		if ($stmt_count && $stmt_count->fetchColumn() >= 5) {
			throw new UpdateHandleExpectedException('You can only join up to 5 company queues at the same time. Please complete or leave an existing queue first.');
		}

		$ts = $pdo->query('SELECT NOW();')->fetch()['now'];
		$stmt = $pdo->query(
			"INSERT INTO interview (id_interviewer, id_interviewee, state_, state_timestamp)
			VALUES ({$this->iwer_id}, {$this->iwee_id}, 'ENQUEUED', '{$ts}')
			ON CONFLICT ON CONSTRAINT pair_interviewer_interviewee DO NOTHING;"
		);
		if ($stmt === false) {
			throw new Exception('failed to execute query');
		}
	}

}

/**
 * Candidate updates their profile fields (department, masters, interests).
 */
class CandidateUpdateProfile extends UpdateRequest
{
	private readonly int $iwee_id;
	private readonly string $department;
	private readonly string $masters;
	private readonly string $interests;

	public function __construct(
		int $update_id_known,
		int $iwee_id,
		string $department,
		string $masters,
		string $interests
	) {
		parent::__construct($update_id_known);
		$this->iwee_id = $iwee_id;
		$this->department = $department;
		$this->masters = $masters;
		$this->interests = $interests;
	}

	protected function process(PDO $pdo): void
	{
		$stmt = $pdo->prepare("UPDATE interviewee 
			SET department = :dept,
			    masters = :masters,
			    interests = :ints
			WHERE id = :id");

		$stmt->bindValue(':dept', $this->department);
		$stmt->bindValue(':masters', $this->masters);
		$stmt->bindValue(':ints', $this->interests);
		$stmt->bindValue(':id', $this->iwee_id, PDO::PARAM_INT);

		if ($stmt->execute() === false) {
			throw new Exception('failed to execute profile update query');
		}
	}
}

class Postgres implements Database, DatabaseAdmin, DatabaseJobPositions
{

	private array $conf;
	private ?PDO $pdo;

	public function __construct(array $configuration)
	{
		$this->conf = $configuration['dbms'];
	}

	private function connect(bool $with_database_selection, ?callable $on_success = null)
	{
		$dsn = "pgsql:host={$this->conf['host']}";

		if ($with_database_selection) {
			$dsn .= ";dbname={$this->conf['dbname']}";
		}

		$options = [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			PDO::ATTR_EMULATE_PREPARES => false,
		];

		try {
			$this->pdo = new PDO($dsn, $this->conf['user'], $this->conf['password'], $options);

			if ($on_success !== null) {
				$result = $on_success();
			}

			$this->pdo = null;

			if (isset($result)) {
				return $result;
			}

		} catch (PDOException $e) {
			throw new Exception("Connection failed: " . $e->getMessage());
		}
	}

	// ||
	// \/ methods of Database interface

	public function operator_mapping(string $password, string &$timestamp): string|false
	{
		return $this->connect(true, function () use ($password, &$timestamp) {
			$result = $this->pdo->query("SELECT type, pass, ts FROM operator;");

			if ($result === false || empty($entries = $result->fetchAll())) {
				return false;
			}

			foreach ($entries as $entry) {
				if (password_verify($password, $entry['pass'])) {
					$timestamp = $entry['ts'];
					return $entry['type'];
				}
			}

			return false;
		});
	}

	public function operator_still_alive(string $timestamp): bool
	{
		return $this->connect(true, function () use ($timestamp) {
			$result = $this->pdo->query("SELECT 1 FROM operator WHERE ts = '{$timestamp}';");

			return $result !== false && !empty($result->fetchAll());
		});
	}

	public function company_mapping(string $token): int|false
	{
		return $this->connect(true, function () use ($token) {
			$statement = $this->pdo->prepare("SELECT id FROM interviewer WHERE token = :token AND token_expires_at > CURRENT_TIMESTAMP");
			$statement->bindParam(':token', $token);
			$statement->execute();
			$res = $statement->fetch();
			return $res ? $res['id'] : false;
		});
	}

	public function company_still_alive(int $interviewer_id): bool
	{
		return $this->connect(true, function () use ($interviewer_id) {
			$statement = $this->pdo->prepare("SELECT 1 FROM interviewer WHERE id = :id");
			$statement->bindParam(':id', $interviewer_id, PDO::PARAM_INT);
			$statement->execute();
			return (bool) $statement->fetch();
		});
	}

	public function update_handle(UpdateRequest $update_request): true|string
	{
		return $this->connect(
			true,
			function () use ($update_request): true|string {
				$result = true;

				try {
					if ($this->pdo->beginTransaction() === false) {
						throw new UpdateHandleUnexpectedException("unable to begin transaction");
					}

					// TODO (haha) no need to do it for all updates, make it better
					if ($this->pdo->query('LOCK TABLE interview IN EXCLUSIVE MODE;') === false) {
						throw new UpdateHandleUnexpectedException("unable to acquire lock for shared data");
					}

					$urid = $this->pdo->query("SELECT * FROM update_recent_id;");

					if ($urid === false) {
						throw new UpdateHandleUnexpectedException("unable to retrieve recent update");
					}

					if ($urid->fetch()['recent'] !== $update_request->update_id_known) {
						throw new UpdateHandleExpectedException("some updates happened before your submission, they should have been send to you by now or soon");
					}

					$updated_or_reason = $update_request->dispatch($this->pdo);

					if ($updated_or_reason === true) {
						$this->update_handled_routine(
							($update_request instanceof SystemCallingToDecision) ? $update_request->changes_happened : true
						);
					} else {
						throw new UpdateHandleExpectedException($updated_or_reason);
					}

					if ($this->pdo->commit() === false) {
						throw new UpdateHandleUnexpectedException("unable to commit transaction");
					}
				} catch (UpdateHandleUnexpectedException $e) {
					$result = "(should not happen) " . $e->getMessage();
				} catch (UpdateHandleExpectedException $e) {
					$result = $e->getMessage();
				} catch (Throwable $th) {
					$result = "(did not know that will happen) " . $th->getMessage();
				} finally {
					if ($this->pdo->inTransaction()) {
						$this->pdo->rollBack();
					}
				}

				if ($result !== true) { // amazing last minute fix
					$update_request->when_dispatch_fails();
				}

				return $result;
			}
		);
	}

	private function update_handled_routine(bool $forced_update = false)
	{
		$changes_happened = $forced_update;

		# TODO (haha) can be more efficient if we do only the needed avaialability fixes after each update request, more complex since the logic is spread out then

		$statement = $this->pdo->query("UPDATE interviewee
			SET available = (
				(
					NOT EXISTS (
						SELECT id FROM interview
						WHERE interview.id_interviewee = interviewee.id
						AND state_ NOT IN ('ENQUEUED', 'COMPLETED')
						LIMIT 1
					)
				)
				AND
				interviewee.active
			)
			WHERE available != (
				(
					NOT EXISTS (
						SELECT id FROM interview
						WHERE interview.id_interviewee = interviewee.id
						AND state_ NOT IN ('ENQUEUED', 'COMPLETED')
						LIMIT 1
					)
				)
				AND
				interviewee.active
			)
		;");
		if ($statement && $statement->rowCount() > 0)
			$changes_happened = true;

		$statement = $this->pdo->query("UPDATE interviewer
			SET available = (
				(
					NOT EXISTS (
						SELECT id FROM interview
						WHERE interview.id_interviewer = interviewer.id
						AND state_ NOT IN ('ENQUEUED', 'COMPLETED')
						LIMIT 1
					)
				)
				AND
				interviewer.active
			)
			WHERE available != (
				(
					NOT EXISTS (
						SELECT id FROM interview
						WHERE interview.id_interviewer = interviewer.id
						AND state_ NOT IN ('ENQUEUED', 'COMPLETED')
						LIMIT 1
					)
				)
				AND
				interviewer.active
			)
		;");
		if ($statement && $statement->rowCount() > 0)
			$changes_happened = true;

		# ---
		# ENQUEUED interviews to CALLING with respect order via ID

		$query_next_interview_to_calling = "SELECT
			interview.id as id_iw,
			interview.id_interviewee as id_iwee,
			interview.id_interviewer as id_iwer

			FROM interview, interviewee, interviewer
			WHERE	interviewee.id = interview.id_interviewee
			AND		interviewer.id = interview.id_interviewer

			AND interview.state_ = 'ENQUEUED'
			AND interviewee.available = TRUE
			AND interviewer.available = TRUE

			ORDER BY id_iw ASC
			LIMIT 1;
		";

		do {
			$loop_change = false;
			$statement = $this->pdo->query($query_next_interview_to_calling);

			if ($statement === false) {
				throw new Exception("failed to execute query");
			}

			if ($statement->rowCount() === 1) {

				$interview = $statement->fetch();

				if (
					$this->pdo->query("UPDATE interview
					SET state_ = 'CALLING', state_timestamp = CURRENT_TIMESTAMP
					WHERE id = {$interview['id_iw']};
				") === false
				) {
					throw new Exception("failed to execute query");
				}

				if (
					$this->pdo->query("UPDATE interviewee
					SET available = FALSE
					WHERE id = {$interview['id_iwee']};
				") === false
				) {
					throw new Exception("failed to execute query");
				}

				if (
					$this->pdo->query("UPDATE interviewer
					SET available = FALSE
					WHERE id = {$interview['id_iwer']};
				") === false
				) {
					throw new Exception("failed to execute query");
				}
				$changes_happened = true;
				$loop_change = true;
			}

		} while ($loop_change);

		# ---

		if ($changes_happened) {
			if ($this->pdo->query("INSERT INTO updates (happened) VALUES (CURRENT_TIMESTAMP);") === false) {
				throw new UpdateHandleUnexpectedException("unable to insert update timestamp");
			}
		}
	}

	public function update_happened_recent(): int
	{
		return $this->connect(true, function () {
			$statement = $this->pdo->query("SELECT * FROM update_recent_id;");
			return $statement === false ? 0 : $statement->fetch()['recent'];
		});
	}

	public function retrieve(string ...$from_table): array
	{
		return $this->connect(true, function () use ($from_table) {
			$retrieved = [];

			$this->pdo->beginTransaction();

			try {
				foreach ($from_table as $table) {
					$retrieved[$table] = $this->pdo->query("SELECT * FROM {$table};")->fetchAll();
				}

				$statement = $this->pdo->query("SELECT * FROM update_recent_id;");
				$retrieved['update'] = $statement === false ? 0 : $statement->fetch()['recent'];
			} catch (Throwable $th) {
				$this->pdo->rollBack();
			}

			$this->pdo->commit();

			return $retrieved;
		});
	}

	public function retrieve_gatekeeper_view(): array
	{
		return $this->connect(true, function () {
			try {
				$this->pdo->beginTransaction();

				$statement = $this->pdo->query("SELECT * FROM view_gatekeeper_iwers ORDER BY name ASC;");

				if ($statement === false) {
					throw new Exception('failed execute to query');
				}

				$retrieved['interviewers'] = $statement->fetchAll();

				# ---

				$statement = $this->pdo->query("SELECT * FROM view_gatekeeper_iwees;");

				if ($statement === false) {
					throw new Exception('failed execute to query');
				}

				$retrieved['interviewees'] = $statement->fetchAll();

				# ---

				$statement = $this->pdo->query("SELECT * FROM view_gatekeeper_iws;");

				if ($statement === false) {
					throw new Exception('failed execute to query');
				}

				$retrieved['interviews'] = $statement->fetchAll();

				# --- All interviews for queue display

				$statement = $this->pdo->query("SELECT * FROM interview WHERE state_ IN ('ENQUEUED', 'CALLING', 'DECISION', 'HAPPENING', 'COMPLETED') ORDER BY id ASC;");

				if ($statement === false) {
					throw new Exception('failed execute to query for all interviews');
				}

				$retrieved['all_interviews'] = $statement->fetchAll();

				# ---

				$statement = $this->pdo->query("SELECT * FROM update_recent_id;");
				$retrieved['update'] = $statement === false ? 0 : $statement->fetch()['recent'];

				$this->pdo->commit();

				return $retrieved;
			} catch (Throwable $th) {
				if ($this->pdo->inTransaction()) {
					$this->pdo->rollBack();
				}
			}

			return [];
		});
	}

	public function retrieve_company_view(): array
	{
		return $this->connect(true, function () {
			try {
				$this->pdo->beginTransaction();

				$statement = $this->pdo->query("SELECT * FROM view_gatekeeper_iwers ORDER BY name ASC;");
				if ($statement === false)
					throw new Exception('failed interviewers query');
				$retrieved['interviewers'] = $statement->fetchAll();

				# Fetch all profile fields for interviewees
				$statement = $this->pdo->query(
					"SELECT id, email, display_name, avatar_url, department, masters, interests, cv_resource_url, active, available FROM interviewee ORDER BY id ASC;"
				);
				if ($statement === false)
					throw new Exception('failed interviewees query');
				$retrieved['interviewees'] = $statement->fetchAll();

				$statement = $this->pdo->query("SELECT * FROM view_gatekeeper_iws;");
				if ($statement === false)
					throw new Exception('failed iws query');
				$retrieved['interviews'] = $statement->fetchAll();

				$statement = $this->pdo->query("SELECT * FROM interview WHERE state_ IN ('ENQUEUED', 'CALLING', 'DECISION', 'HAPPENING', 'COMPLETED') ORDER BY id ASC;");
				if ($statement === false)
					throw new Exception('failed all_interviews query');
				$retrieved['all_interviews'] = $statement->fetchAll();

				$statement = $this->pdo->query("SELECT * FROM update_recent_id;");
				$retrieved['update'] = $statement === false ? 0 : $statement->fetch()['recent'];

				$this->pdo->commit();
				return $retrieved;
			} catch (Throwable $th) {
				if ($this->pdo->inTransaction())
					$this->pdo->rollBack();
			}
			return [];
		});
	}

	public function retrieve_queues_view(): array
	{
		return $this->connect(true, function () {
			try {
				$this->pdo->beginTransaction();

				$statement = $this->pdo->query("SELECT * FROM view_queues_iwers ORDER BY name ASC;");

				if ($statement === false) {
					throw new Exception('failed execute to query');
				}

				$retrieved['interviewers'] = $statement->fetchAll();

				# ---

				$statement = $this->pdo->query("SELECT * FROM view_queues_iwees;");

				if ($statement === false) {
					throw new Exception('failed execute to query');
				}

				$retrieved['interviewees'] = $statement->fetchAll();

				# ---

				$statement = $this->pdo->query("SELECT * FROM view_queues_iws;");

				if ($statement === false) {
					throw new Exception('failed execute to query');
				}

				$retrieved['interviews'] = $statement->fetchAll();

				# ---

				$statement = $this->pdo->query("SELECT * FROM view_queues_iws_current;");

				if ($statement === false) {
					throw new Exception('failed execute to query');
				}

				$retrieved['interviews_current'] = $statement->fetchAll();

				# ---

				$statement = $this->pdo->query("SELECT * FROM update_recent_id;");
				$retrieved['update'] = $statement === false ? 0 : $statement->fetch()['recent'];

				$this->pdo->commit();

				return $retrieved;
			} catch (Throwable $th) {
				if ($this->pdo->inTransaction()) {
					$this->pdo->rollBack();
				}
			}

			return [];
		});
	}

	public function candidate_by_google_sub(string $google_sub): array|false
	{
		return $this->connect(true, function () use ($google_sub) {
			$stmt = $this->pdo->prepare("SELECT * FROM interviewee WHERE google_sub = :sub LIMIT 1;");
			$stmt->bindValue(':sub', $google_sub);
			$stmt->execute();
			$row = $stmt->fetch();
			return $row ?: false;
		});
	}

	public function candidate_by_email(string $email): array|false
	{
		return $this->connect(true, function () use ($email) {
			$stmt = $this->pdo->prepare("SELECT * FROM interviewee WHERE email = :email LIMIT 1;");
			$stmt->bindValue(':email', $email);
			$stmt->execute();
			$row = $stmt->fetch();
			return $row ?: false;
		});
	}

	public function candidate_dashboard_view(int $interviewee_id): array
	{
		return $this->connect(true, function () use ($interviewee_id) {
			try {
				$this->pdo->beginTransaction();

				$stmt = $this->pdo->query("SELECT id, name, image_resource_url, table_number, active FROM interviewer ORDER BY name ASC;");
				if ($stmt === false)
					throw new Exception('query failed');
				$retrieved['interviewers'] = $stmt->fetchAll();

				$stmt = $this->pdo->prepare(
					"SELECT interview.*, interviewer.name AS company_name
					FROM interview
					JOIN interviewer ON interviewer.id = interview.id_interviewer
					WHERE interview.id_interviewee = :iwee_id
					ORDER BY interview.id ASC;"
				);
				$stmt->bindValue(':iwee_id', $interviewee_id, PDO::PARAM_INT);
				$stmt->execute();
				$retrieved['interviews'] = $stmt->fetchAll();

				$stmt = $this->pdo->query("SELECT * FROM update_recent_id;");
				$retrieved['update'] = $stmt === false ? 0 : $stmt->fetch()['recent'];

				$this->pdo->commit();
				return $retrieved;
			} catch (Throwable $th) {
				if ($this->pdo->inTransaction())
					$this->pdo->rollBack();
			}
			return ['interviewers' => [], 'interviews' => [], 'update' => 0];
		});
	}

	// ||
	// \/ methods of DatabaseAdmin interface

	public function create()
	{
		$result = $this->connect(false, function () {
			$exists = $this->pdo->query("SELECT 1 FROM pg_database WHERE datname = '{$this->conf['dbname']}'")->fetchColumn();

			if ($exists !== false) {
				return false;
			}

			return $this->pdo->query("CREATE DATABASE {$this->conf['dbname']};") !== false;
		});

		if ($result === false) {
			echo "Database already exists.\n";
			return;
		}

		echo "Database created.\n";

		$result = $this->connect(true, function () {
			$tables = [ // creation queries

				"CREATE TABLE IF NOT EXISTS operator (
					id SERIAL PRIMARY KEY,
					
					pass VARCHAR(255) NOT NULL,
					type VARCHAR(255) NOT NULL,
					reminder VARCHAR(255) NOT NULL,

					ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP -- should by something unique-random, it does the job for now
				);",

				# ---

				"CREATE TABLE IF NOT EXISTS interviewee (
					id SERIAL PRIMARY KEY,

					email VARCHAR(255) UNIQUE NOT NULL,

					-- Candidate self-registration fields (nullable for secretary-added interviewees)
					google_sub TEXT UNIQUE,
					display_name TEXT,
					avatar_url TEXT,
					department TEXT,
					masters TEXT,
					interests TEXT,
					cv_resource_url TEXT,

					active BOOLEAN NOT NULL,
					available BOOLEAN NOT NULL
				);",

				"CREATE TABLE IF NOT EXISTS interviewer /* or company */ (
				id SERIAL PRIMARY KEY,

				name VARCHAR(255) NOT NULL,
				image_resource_url VARCHAR(255) NOT NULL,
				table_number VARCHAR(255),

				token VARCHAR(6),
				token_expires_at TIMESTAMP,

				active BOOLEAN NOT NULL,
				available BOOLEAN NOT NULL
				);",

				"CREATE TABLE IF NOT EXISTS job (
					id SERIAL PRIMARY KEY,
					
					title TEXT,
					description TEXT,

					tag VARCHAR(64) DEFAULT NULL,

					id_interviewer INTEGER NOT NULL REFERENCES interviewer(id) ON DELETE CASCADE
				);",

				"CREATE TABLE IF NOT EXISTS interview (
					id SERIAL PRIMARY KEY,

					id_interviewer INTEGER NOT NULL REFERENCES interviewer(id) ON DELETE CASCADE,
					id_interviewee INTEGER NOT NULL REFERENCES interviewee(id) ON DELETE CASCADE,
					CONSTRAINT pair_interviewer_interviewee UNIQUE (id_interviewer, id_interviewee),

					state_ VARCHAR(255) CHECK (state_ IN (
						'ENQUEUED',
						'CALLING',
						'DECISION',
						'HAPPENING',
						'COMPLETED'
					)),
					state_timestamp TIMESTAMP NOT NULL
				);",

				# ---

				"CREATE TABLE IF NOT EXISTS updates (
					id SERIAL,
					happened TIMESTAMP NOT NULL -- in UTC
				);",

				# ---

				"CREATE VIEW update_recent_id AS SELECT COALESCE(MAX(id),0) AS recent FROM updates;",

				"CREATE VIEW view_gatekeeper_iwers AS
					SELECT
						id,
						name,
						image_resource_url,
						table_number,
						active,
						available,
						token,
						token_expires_at
					FROM interviewer
					ORDER BY name;
				",

				"CREATE VIEW view_gatekeeper_iwees AS
					SELECT
						id,
						email,
						active,
						available
					FROM interviewee
					ORDER BY id;
				",

				"CREATE VIEW view_gatekeeper_iws AS
					SELECT DISTINCT ON (i.id_interviewer) i.*
					FROM interview i
					WHERE i.state_ in ('CALLING', 'DECISION', 'HAPPENING')
					ORDER BY i.id_interviewer ASC, i.state_timestamp DESC;
				",

				"CREATE VIEW view_queues_iwers AS
					SELECT
						id,
						name,
						image_resource_url,
						table_number,
						active,
						token,
						token_expires_at
					FROM interviewer
					ORDER BY name;
				",

				"CREATE VIEW view_queues_iwees AS
					SELECT id, available, active FROM interviewee;
				",

				"CREATE VIEW view_queues_iws AS
					SELECT * FROM interview ORDER BY id ASC;
				",

				"CREATE VIEW view_queues_iws_current AS
					SELECT DISTINCT ON (i.id_interviewer) i.*
					FROM interview i
					WHERE i.state_ in ('CALLING', 'DECISION', 'HAPPENING')
					ORDER BY i.id_interviewer ASC, i.state_timestamp DESC;
				",

				// // keep it in case of need can be removed in later comments
				// "CREATE VIEW update_recent_ms AS
				// 	SELECT COALESCE(
				// 		FLOOR( MAX(EXTRACT(EPOCH FROM happened)) * 1000 ),
				// 		0
				// 	) AS recent
				// 	FROM update_timestamps
				// ;",

			];

			foreach ($tables as $t) {
				if ($this->pdo->query($t) === false)
					return false;
			}

			return true;
		});

		if ($result === false) {
			echo "Tables creation failed. Dropping database.\n";
			$this->drop();
			return;
		}

		echo "Tables created.\n";
	}

	public function drop()
	{
		$result = $this->connect(false, function () {
			if ($this->pdo->query("DROP DATABASE IF EXISTS {$this->conf['dbname']};") === false) {
				return false;
			}

			return true;
		});

		if ($result === false) {
			echo "Database did not drop.\n";
			return;
		}

		echo "Database dropped.\n";
	}

	public function operator_entries(): array
	{
		return $this->connect(true, function () {
			$query = "SELECT * FROM operator;";
			$result = $this->pdo->query($query);
			return $result === false ? [] : $result->fetchAll();
		});
	}

	public function operator_add(string $type, string $password, string $reminder): bool
	{
		$entries = $this->operator_entries();

		return $this->connect(true, function () use ($entries, $type, $password, $reminder) {

			foreach ($entries as $entry) {
				if (password_verify($password, $entry['pass'])) {
					return false;
				}
			}

			$pass = password_hash($password, PASSWORD_BCRYPT);

			return $this->pdo->query("INSERT INTO operator (pass, type, reminder) VALUES ('{$pass}', '{$type}', '{$reminder}');") !== false;
		});
	}

	public function operator_remove(bool|int $id_or_all): bool
	{
		if ($id_or_all === false) {
			return false;
		}

		return $this->connect(true, function () use ($id_or_all) {

			if ($id_or_all === true) {
				$query = "TRUNCATE operator RESTART IDENTITY;";
			} else {
				$query = "DELETE FROM operator WHERE id = {$id_or_all};";
			}

			return $this->pdo->query($query) !== false;
		});
	}

	public function operator_update_password(int $id, string $new_password): bool
	{
		return $this->connect(true, function () use ($id, $new_password) {
			$pass = password_hash($new_password, PASSWORD_BCRYPT);
			$statement = $this->pdo->prepare("UPDATE operator SET pass = :pass WHERE id = :id;");
			return $statement->execute([':pass' => $pass, ':id' => $id]);
		});
	}

	// ||
	// \/ methods of DatabaseJobPositions interface

	public function retrieve_jobs_of(int $interviewer_id, bool $untagged_only = false): array|false
	{
		return $this->connect(true, function () use ($interviewer_id, $untagged_only) {
			try {
				$this->pdo->beginTransaction();

				$statement = $this->pdo->query("SELECT id, name, table_number FROM interviewer WHERE id = {$interviewer_id};");

				if ($statement === false) {
					throw new Exception('failed execute to query');
				}

				$retrieved['info'] = $statement->fetch();

				# ---

				$statement = $this->pdo->query("SELECT j.*
					FROM interviewer as i, job as j
					WHERE i.id = j.id_interviewer
					AND i.id = {$interviewer_id}
					" . ($untagged_only === true ? 'AND j.tag IS NULL' : '') . "
					ORDER BY j.id
				;");

				if ($statement === false) {
					throw new Exception('failed execute to query');
				}

				$retrieved['jobs'] = $statement->fetchAll();

				# ---

				$this->pdo->commit();

				return $retrieved;
			} catch (Throwable $th) {
				if ($this->pdo->inTransaction()) {
					$this->pdo->rollBack();
				}
			}

			return false;
		});
	}

	public function insert_job(string $title, string $description, int $interviewer_id): bool
	{
		return $this->connect(true, function () use ($title, $description, $interviewer_id) {
			try {
				$this->pdo->beginTransaction();

				$statement = $this->pdo->prepare("INSERT INTO job (title, description, id_interviewer) VALUES (:title, :description, :interviewer_id)");
				$statement->bindParam(':title', $title, PDO::PARAM_STR);
				$statement->bindParam(':description', $description, PDO::PARAM_STR);
				$statement->bindParam(':interviewer_id', $interviewer_id, PDO::PARAM_INT);

				if ($statement->execute() === false) {
					throw new Exception('failed execute to query');
				}

				$this->pdo->commit();

				return true;
			} catch (Throwable $th) {
				if ($this->pdo->inTransaction()) {
					$this->pdo->rollBack();
				}
			}

			return false;
		});
	}

	public function delete_job(int $id): bool
	{
		return $this->connect(true, function () use ($id) {
			try {
				$this->pdo->beginTransaction();

				$statement = $this->pdo->query("DELETE FROM job WHERE id = {$id};");

				if ($statement->execute() === false) {
					throw new Exception('failed execute to query');
				}

				$this->pdo->commit();

				return true;
			} catch (Throwable $th) {
				if ($this->pdo->inTransaction()) {
					$this->pdo->rollBack();
				}
			}

			return false;
		});
	}

	public function update_jobs_tags(array $tag_job_ids): bool
	{
		return $this->connect(true, function () use ($tag_job_ids) {
			try {
				$this->pdo->beginTransaction();

				foreach ($tag_job_ids as $tag => $job_ids) {
					$job_ids = implode(", ", $job_ids);

					$statement = $this->pdo->query("UPDATE job
						SET tag = '{$tag}' WHERE id IN ({$job_ids})
					;");

					if ($statement->execute() === false) {
						throw new Exception('failed execute to query');
					}
				}

				$this->pdo->commit();

				return true;
			} catch (Throwable $th) {
				if ($this->pdo->inTransaction()) {
					$this->pdo->rollBack();
				}
			}

			return false;
		});
	}

	public function retrieve_interviewers_and_jobs_with_tags(array $tags): array|false
	{
		return $this->connect(true, function () use ($tags) {
			try {
				$this->pdo->beginTransaction();

				$tags = "'" . implode("', '", $tags) . "'";

				$statement = $this->pdo->query("SELECT
						i.id, i.name, j.title
					FROM
						interviewer as i, job as j
					WHERE
						i.id = j.id_interviewer
						AND j.tag IN ({$tags})
					ORDER BY i.name, i.id, j.id
				;");

				if ($statement === false) {
					throw new Exception('failed execute to query');
				}

				$arr = $statement->fetchAll();

				$this->pdo->commit();

				return $arr;
			} catch (Throwable $th) {
				if ($this->pdo->inTransaction()) {
					$this->pdo->rollBack();
				}
			}

			return false;
		});
	}

	public function retrieve_companies_view(): array
	{
		return $this->connect(true, function () {
			try {
				$this->pdo->beginTransaction();

				// Get all active interviewers
				$statement = $this->pdo->query("SELECT id, name, table_number, image_resource_url FROM interviewer WHERE active = true ORDER BY name ASC;");
				if ($statement === false) {
					throw new Exception('failed execute to query for interviewers');
				}
				$interviewers = $statement->fetchAll();

				// Get all jobs
				$statement = $this->pdo->query("SELECT * FROM job ORDER BY id_interviewer, id ASC;");
				if ($statement === false) {
					throw new Exception('failed execute to query for jobs');
				}
				$jobs = $statement->fetchAll();

				$this->pdo->commit();

				// Group jobs by interviewer
				$grouped_jobs = [];
				foreach ($jobs as $job) {
					$grouped_jobs[$job['id_interviewer']][] = $job;
				}

				foreach ($interviewers as &$iwer) {
					$iwer['jobs'] = $grouped_jobs[$iwer['id']] ?? [];
				}

				return $interviewers;
			} catch (Throwable $th) {
				if ($this->pdo->inTransaction()) {
					$this->pdo->rollBack();
				}
			}

			return [];
		});
	}
}

function database(): Database
{
	return new Postgres(require __DIR__ . '/config.php');
}

function database_admin(): DatabaseAdmin
{
	return new Postgres(require __DIR__ . '/config.php');
}

function database_jobpositions(): DatabaseJobPositions
{
	return new Postgres(require __DIR__ . '/config.php');
}
