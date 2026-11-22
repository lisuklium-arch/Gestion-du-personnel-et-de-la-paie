<?php
/** Calcul des jours ouvrables (lun–ven, hors jours fériés). */
class JoursOuvrablesService
{
    private PDO $pdo;
    /** @var array<string,true>|null */
    private ?array $feries = null;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function compter(string $debut, string $fin): float
    {
        $d = DateTimeImmutable::createFromFormat('!Y-m-d', $debut);
        $f = DateTimeImmutable::createFromFormat('!Y-m-d', $fin);
        if (!$d || !$f || $f < $d) {
            return 0.0;
        }
        $this->chargerFeries();
        $n = 0.0;
        for ($c = $d; $c <= $f; $c = $c->modify('+1 day')) {
            $w = (int) $c->format('N');
            if ($w >= 6) {
                continue;
            }
            $key = $c->format('Y-m-d');
            $md = $c->format('m-d');
            if (isset($this->feries[$key]) || isset($this->feries['R:' . $md])) {
                continue;
            }
            $n += 1.0;
        }
        return $n;
    }

    private function chargerFeries(): void
    {
        if ($this->feries !== null) {
            return;
        }
        $this->feries = [];
        foreach ($this->pdo->query('SELECT date_ferie, recurrent FROM jour_ferie') as $row) {
            $date = (string) $row['date_ferie'];
            $this->feries[$date] = true;
            if ((int) $row['recurrent'] === 1) {
                $this->feries['R:' . substr($date, 5)] = true;
            }
        }
    }
}
