<?php
/** Calcul des jours ouvrables + détail pédagogique (calendaire, WE, fériés). */
class JoursOuvrablesService
{
    private PDO $pdo;
    /** @var array<string,true>|null */
    private ?array $feries = null;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function compter(string $debut, string $fin, string $periodeDebut = 'MATIN', string $periodeFin = 'APRES_MIDI'): float
    {
        $d = $this->detail($debut, $fin, $periodeDebut, $periodeFin);
        return $d['jours_decomptes'];
    }

    /**
     * @return array{
     *   jours_calendaires:int, week_ends:int, feries_ouvres:int,
     *   jours_decomptes:float, date_reprise:?string, detail_jours:list<array>
     * }
     */
    public function detail(string $debut, string $fin, string $periodeDebut = 'MATIN', string $periodeFin = 'APRES_MIDI'): array
    {
        $d0 = DateTimeImmutable::createFromFormat('!Y-m-d', $debut);
        $d1 = DateTimeImmutable::createFromFormat('!Y-m-d', $fin);
        if (!$d0 || !$d1 || $d1 < $d0) {
            return [
                'jours_calendaires' => 0,
                'week_ends' => 0,
                'feries_ouvres' => 0,
                'jours_decomptes' => 0.0,
                'date_reprise' => null,
                'detail_jours' => [],
            ];
        }
        $this->chargerFeries();
        $calendaires = 0;
        $we = 0;
        $feries = 0;
        $decomptes = 0.0;
        $detail = [];
        for ($c = $d0; $c <= $d1; $c = $c->modify('+1 day')) {
            $calendaires++;
            $key = $c->format('Y-m-d');
            $w = (int) $c->format('N');
            $estWe = $w >= 6;
            $estFerie = isset($this->feries[$key]) || isset($this->feries['R:' . $c->format('m-d')]);
            $valeur = 0.0;
            $lib = 'ouvré';
            if ($estWe) {
                $we++;
                $lib = 'week-end';
            } elseif ($estFerie) {
                $feries++;
                $lib = 'férié';
            } else {
                $valeur = 1.0;
                if ($key === $debut && $periodeDebut === 'APRES_MIDI') {
                    $valeur = 0.5;
                }
                if ($key === $fin && $periodeFin === 'MATIN') {
                    $valeur = min($valeur, 0.5);
                }
                // même jour matin→matin ou aprem→aprem
                if ($debut === $fin) {
                    if ($periodeDebut === 'MATIN' && $periodeFin === 'MATIN') {
                        $valeur = 0.5;
                    } elseif ($periodeDebut === 'APRES_MIDI' && $periodeFin === 'APRES_MIDI') {
                        $valeur = 0.5;
                    } elseif ($periodeDebut === 'APRES_MIDI' && $periodeFin === 'MATIN') {
                        $valeur = 0.0;
                    }
                }
                $decomptes += $valeur;
            }
            $detail[] = ['date' => $key, 'libelle' => $lib, 'valeur' => $valeur];
        }
        $reprise = $this->prochainOuvrable($d1->modify('+1 day'));
        return [
            'jours_calendaires' => $calendaires,
            'week_ends' => $we,
            'feries_ouvres' => $feries,
            'jours_decomptes' => round($decomptes, 1),
            'date_reprise' => $reprise,
            'detail_jours' => $detail,
        ];
    }

    private function prochainOuvrable(DateTimeImmutable $from): string
    {
        $this->chargerFeries();
        $c = $from;
        for ($i = 0; $i < 30; $i++) {
            $w = (int) $c->format('N');
            $key = $c->format('Y-m-d');
            $ferie = isset($this->feries[$key]) || isset($this->feries['R:' . $c->format('m-d')]);
            if ($w < 6 && !$ferie) {
                return $key;
            }
            $c = $c->modify('+1 day');
        }
        return $from->format('Y-m-d');
    }

    private function chargerFeries(): void
    {
        if ($this->feries !== null) {
            return;
        }
        $this->feries = [];
        try {
            foreach ($this->pdo->query('SELECT date_ferie, recurrent FROM jour_ferie') as $row) {
                $date = (string) $row['date_ferie'];
                $this->feries[$date] = true;
                if ((int) $row['recurrent'] === 1) {
                    $this->feries['R:' . substr($date, 5)] = true;
                }
            }
        } catch (Throwable $e) {
            // table absente en environnement incomplet
        }
    }
}
