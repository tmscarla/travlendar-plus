<?php

namespace App\Http\Helpers;

/**
* QueryHelper class
*
* database utility queries 
*/
class QueryHelper{

    /** 
    * @var string $eventAddConflictQuery raw sql query that lists
    * conflict ids given an event information.
    */
	public static $eventAddConflictQuery = '
        SELECT events.id FROM events WHERE
        (
        	(
            	(events.id <> :newEventId)
            	OR
            	(:newEventId IS NULL)
            )
            AND
            (events."userId" = :currentUserId)
            AND
            (
                (
                    (events.id NOT IN (SELECT "eventId" FROM "flexibleEvents"))
                    AND
                    (
                        (
                            (events.start BETWEEN :newEventstart + 1 AND :newEventEnd - 1)
                            AND
                            (events.id NOT IN (SELECT "eventId" FROM travels))
                        )
                        OR
                        (events.start - (SELECT travels.duration FROM travels WHERE travels."eventId" = events.id) BETWEEN :newEventstart + 1 AND :newEventEnd - 1)
                        OR
                        (
                            (events.start - (SELECT travels.duration FROM travels WHERE travels."eventId" = events.id) < :newEventstart + 1)
                            AND
                            (events.end > :newEventEnd - 1)
                        )
                        OR
                        (events.end BETWEEN :newEventstart + 1 AND :newEventEnd - 1)
                        OR
                        (
                            (events.start < :newEventstart + 1)
                            AND
                            (events.end > :newEventEnd - 1)
                        )
                    )
                )
                OR
                (
                    (events.id IN (SELECT "eventId" FROM "flexibleEvents"))
                    AND
                    (
                        (
                            ((SELECT "lowerBound" FROM "flexibleEvents" WHERE "flexibleEvents"."eventId" = events.id) BETWEEN :newEventstart + 1 AND :newEventEnd - 1)
                            AND
                            (events.id NOT IN (SELECT "eventId" FROM travels))
                        )
                        OR
                        ((SELECT "lowerBound" FROM "flexibleEvents" WHERE "flexibleEvents"."eventId" = events.id) - (SELECT travels.duration FROM travels WHERE travels."eventId" = events.id) BETWEEN :newEventstart + 1 AND :newEventEnd - 1)
                        OR
                        (
                            ((SELECT "lowerBound" FROM "flexibleEvents" WHERE "flexibleEvents"."eventId" = events.id) - (SELECT travels.duration FROM travels WHERE travels."eventId" = events.id) < :newEventstart + 1)
                            AND
                            ((SELECT "upperBound" FROM "flexibleEvents" WHERE "flexibleEvents"."eventId" = events.id) > :newEventEnd - 1)
                        )
                        OR
                        ((SELECT "upperBound" FROM "flexibleEvents" WHERE "flexibleEvents"."eventId" = events.id) BETWEEN :newEventstart + 1 AND :newEventEnd - 1)
                        OR
                        (
                            ((SELECT "lowerBound" FROM "flexibleEvents" WHERE "flexibleEvents"."eventId" = events.id) < :newEventstart + 1)
                            AND
                            ((SELECT "upperBound" FROM "flexibleEvents" WHERE "flexibleEvents"."eventId" = events.id) > :newEventEnd - 1)
                        )
                    )
                )
            )
        )

    ';




}
