<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/services/CalculateurJoursOuvrables.php';

use App\Services\CalculateurJoursOuvrables;
use InvalidArgumentException;

$cases = [
    ['5.0', '2026-10-26', '2026-10-30', [], 'MATIN', 'APRES_MIDI'],
    ['4.0', '2026-10-26', '2026-10-30', ['2026-10-27'], 'MATIN', 'APRES_MIDI'],
    ['0.5', '2026-10-26', '2026-10-26', [], 'MATIN', 'MATIN'],
    ['0.5', '2026-10-26', '2026-10-26', [], 'APRES_MIDI', 'APRES_MIDI'],
    ['1.0', '2026-10-26', '2026-10-27', [], 'APRES_MIDI', 'MATIN'],
    ['1.0', '2026-10-30', '2026-11-02', [], 'MATIN', 'APRES_MIDI'],
];

foreach ($cases as [$expected, $start, $end, $holidays, $startPeriod, $endPeriod]) {
    $actual = CalculateurJoursOuvrables::calculer($start, $end, $holidays, $startPeriod, $endPeriod);
    if ($actual !== $expected) {
        throw new RuntimeException("Expected $expected, got $actual for $start to $end.");
    }
}

$invalidCases = [
    ['2026-12-31', '2027-01-01', [], 'MATIN', 'APRES_MIDI'],
    ['2026-10-27', '2026-10-26', [], 'MATIN', 'APRES_MIDI'],
    ['2026-02-31', '2026-03-02', [], 'MATIN', 'APRES_MIDI'],
    ['2026-10-26', '2026-10-26', [], 'APRES_MIDI', 'MATIN'],
    ['2026-10-26', '2026-10-26', ['2026-10-26'], 'MATIN', 'APRES_MIDI'],
];

foreach ($invalidCases as [$start, $end, $holidays, $startPeriod, $endPeriod]) {
    try {
        CalculateurJoursOuvrables::calculer($start, $end, $holidays, $startPeriod, $endPeriod);
    } catch (InvalidArgumentException) {
        continue;
    }

    throw new RuntimeException("Expected invalid period to be rejected: $start to $end.");
}

echo count($cases) . ' valid cases and ' . count($invalidCases) . " rejection cases passed.\n";
