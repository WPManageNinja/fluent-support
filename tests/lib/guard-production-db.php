<?php
/**
 * Refuses to let a destructive runner touch anything but a throwaway database.
 *
 * Include this at the very top of any runner that drops/recreates tables or
 * databases (Codeception WPLoader bootstrap, lab provisioners, mutation
 * harnesses). Convention is not protection — the day a config file points at
 * the real site, this is the only thing standing between the suite and the
 * production data it promised to protect.
 *
 * Adapt the two patterns to your suite's naming and nothing else.
 */

function suite_guard_against_production_db(string $dbName, string $tablePrefix): void
{
    // The throwaway DB and prefix your suite provisions — e.g. Codeception's
    // WPLoader database. Anything that doesn't match is treated as real.
    $allowedDbPattern = '/^[a-z0-9_]*test[a-z0-9_]*$/i'; // e.g. fftest, wp_test
    $allowedPrefixPattern = '/^[a-z0-9]+test_/i';        // e.g. fftest_

    $violations = [];
    if (!preg_match($allowedDbPattern, $dbName)) {
        $violations[] = "database name '{$dbName}' does not match the throwaway pattern";
    }
    if (!preg_match($allowedPrefixPattern, $tablePrefix)) {
        $violations[] = "table prefix '{$tablePrefix}' does not match the throwaway pattern";
    }

    if ($violations) {
        fwrite(STDERR, "\nREFUSING TO RUN — this runner mutates its database.\n");
        foreach ($violations as $violation) {
            fwrite(STDERR, "  - {$violation}\n");
        }
        fwrite(STDERR, "Point the suite at its own throwaway database, never a real site's.\n\n");
        exit(3);
    }
}
