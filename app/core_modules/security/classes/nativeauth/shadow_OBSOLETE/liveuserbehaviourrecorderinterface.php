<?php
/**
 * Records a redacted, deterministic snapshot of observable authentication state.
 *
 * The recorder must never alter authentication, authorisation, session state,
 * database state, or the current response.
 */
interface LiveUserBehaviourRecorderInterface
{
    /**
     * @param array $state Raw authentication/session state supplied by a caller.
     * @param array $metadata Non-secret diagnostic metadata.
     * @return array Canonical redacted snapshot.
     */
    public function createSnapshot(
        array $state,
        array $metadata = array()
    );

    /**
     * @param array $snapshot Canonical snapshot from createSnapshot().
     * @param string $target Absolute output filename.
     * @return bool
     */
    public function writeSnapshot(array $snapshot, $target);
}
