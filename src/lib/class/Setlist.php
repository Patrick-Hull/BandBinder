<?php
class Setlist
{
    private DatabaseManager $db;
    private array $data;

    /**
     * @throws Exception
     */
    public function __construct(string $idSetlist)
    {
        $this->db = new DatabaseManager();
        $rows = $this->db->query("SELECT * FROM `setlists` WHERE `idSetlist` = ?", [$idSetlist]);
        if (!isset($rows[0])) {
            throw new Exception("Invalid Setlist");
        }
        $this->data = $rows[0];
    }

    // ── Getters ──────────────────────────────────────────────────────────────

    public function getIdSetlist(): string
    {
        return $this->data['idSetlist'];
    }

    public function getSetlistName(): string
    {
        return $this->data['setlistName'];
    }

    public function getPerformedAt(): ?string
    {
        return $this->data['performedAt'] ?: null;
    }

    public function getNotes(): ?string
    {
        return $this->data['notes'] ?: null;
    }

    public function getCreatedAt(): string
    {
        return $this->data['created_at'];
    }

    // ── Mutations ─────────────────────────────────────────────────────────────

    /**
     * @throws Exception
     */
    public function UpdateSetlist(string $setlistName, ?string $performedAt, ?string $notes): void
    {
        $this->db->query(
            "UPDATE `setlists` SET `setlistName`=?, `performedAt`=?, `notes`=? WHERE `idSetlist`=?",
            [$setlistName, $performedAt ?: null, $notes ?: null, $this->data['idSetlist']]
        );
        $this->data['setlistName'] = $setlistName;
        $this->data['performedAt'] = $performedAt;
        $this->data['notes']       = $notes;
    }

    /**
     * @throws Exception
     */
    public function DeleteSetlist(): void
    {
        $this->db->query("DELETE FROM `setlists` WHERE `idSetlist` = ?", [$this->data['idSetlist']]);
    }

    // ── Sets & Charts ─────────────────────────────────────────────────────────

    /**
     * Returns all sets with their ordered charts (including chart metadata).
     * @throws Exception
     */
    public function getSetsWithCharts(): array
    {
        $sets = $this->db->query(
            "SELECT * FROM `setlist__sets`
             WHERE `idSetlist` = ?
             ORDER BY `sortOrder` IS NULL, `sortOrder`",
            [$this->data['idSetlist']]
        );

        foreach ($sets as &$set) {
            $set['charts'] = $this->db->query(
                "SELECT ssc.idSetChart, ssc.idSet, ssc.idChart, ssc.sortOrder,
                        c.chartName, c.bpm, c.duration, c.chartKey,
                        a.artistName, ar.arrangerName,
                        COALESCE(ar.arrangerName, a.artistName, '') AS displayName
                 FROM `setlist__set_charts` ssc
                 JOIN `charts` c ON c.idChart = ssc.idChart
                 LEFT JOIN `artists`   a  ON a.idArtist   = c.idArtist
                 LEFT JOIN `arrangers` ar ON ar.idArranger = c.idArranger
                 WHERE ssc.idSet = ?
                 ORDER BY ssc.sortOrder IS NULL, ssc.sortOrder",
                [$set['idSet']]
            );
        }
        unset($set);

        return $sets;
    }

    /**
     * Save the complete set/chart layout from the editor.
     * Handles creates, updates, and deletions for sets and set-charts.
     *
     * @param array $sets [{idSet: "uuid|new", setName: "...", charts: ["idChart", ...]}, ...]
     * @throws Exception
     */
    public function saveLayout(array $sets): void
    {
        // Determine which existing sets to keep
        $existing    = $this->db->query(
            "SELECT `idSet` FROM `setlist__sets` WHERE `idSetlist` = ?",
            [$this->data['idSetlist']]
        );
        $existingIds = array_column($existing, 'idSet');
        $keepIds     = array_filter(
            array_column($sets, 'idSet'),
            fn($id) => $id !== 'new'
        );

        // Delete removed sets
        foreach ($existingIds as $eid) {
            if (!in_array($eid, $keepIds)) {
                $this->db->query("DELETE FROM `setlist__sets` WHERE `idSet` = ?", [$eid]);
            }
        }

        // Upsert sets and rebuild their chart lists
        foreach ($sets as $i => $set) {
            $isNew   = ($set['idSet'] === 'new');
            $idSet   = $isNew ? Helper::UUIDv4() : $set['idSet'];
            $setName = trim($set['setName'] ?? ('Set ' . ($i + 1)));

            if ($isNew) {
                $this->db->query(
                    "INSERT INTO `setlist__sets` (`idSet`, `idSetlist`, `setName`, `sortOrder`) VALUES (?, ?, ?, ?)",
                    [$idSet, $this->data['idSetlist'], $setName, $i + 1]
                );
            } else {
                $this->db->query(
                    "UPDATE `setlist__sets` SET `setName` = ?, `sortOrder` = ? WHERE `idSet` = ?",
                    [$setName, $i + 1, $idSet]
                );
            }

            // Rebuild chart list for this set
            $this->db->query("DELETE FROM `setlist__set_charts` WHERE `idSet` = ?", [$idSet]);
            foreach (($set['charts'] ?? []) as $j => $idChart) {
                $this->db->query(
                    "INSERT INTO `setlist__set_charts` (`idSetChart`, `idSet`, `idChart`, `sortOrder`) VALUES (?, ?, ?, ?)",
                    [Helper::UUIDv4(), $idSet, $idChart, $j + 1]
                );
            }
        }
    }

    // ── Statics ───────────────────────────────────────────────────────────────

    /**
     * @throws Exception
     */
    public static function CreateSetlist(string $setlistName, ?string $performedAt, ?string $notes): Setlist
    {
        $db        = new DatabaseManager();
        $idSetlist = Helper::UUIDv4();

        try {
            $db->query(
                "INSERT INTO `setlists` (`idSetlist`, `setlistName`, `performedAt`, `notes`) VALUES (?, ?, ?, ?)",
                [$idSetlist, $setlistName, $performedAt ?: null, $notes ?: null]
            );
        } catch (Exception $e) {
            throw new Exception("Error creating Setlist: " . $e->getMessage());
        }

        // Create a default first set
        $db->query(
            "INSERT INTO `setlist__sets` (`idSet`, `idSetlist`, `setName`, `sortOrder`) VALUES (?, ?, ?, ?)",
            [Helper::UUIDv4(), $idSetlist, 'Set 1', 1]
        );

        return new Setlist($idSetlist);
    }

    /**
     * @throws Exception
     */
    public static function GetAll(): array
    {
        $db   = new DatabaseManager();
        $rows = $db->query(
            "SELECT * FROM `setlists` ORDER BY `performedAt` DESC, `performedAt` IS NULL, `setlistName`"
        );
        return array_map(fn($row) => new Setlist($row['idSetlist']), $rows);
    }
}
