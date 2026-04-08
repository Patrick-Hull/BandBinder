<?php
class Chart
{
    private DatabaseManager $db;
    private array $data;

    /**
     * @throws Exception
     */
    public function __construct(string $idChart)
    {
        $this->db = new DatabaseManager();
        $rows = $this->db->query("SELECT * FROM `charts` WHERE `idChart` = ?", [$idChart]);
        if (!isset($rows[0])) {
            throw new Exception("Invalid Chart");
        }
        $this->data = $rows[0];
    }

    /**
     * Getters
     */
    public function getIdChart(): string
    {
        return $this->data['idChart'];
    }

    public function getChartName(): string
    {
        return $this->data['chartName'];
    }

    public function getRawIdArtist(): ?string
    {
        return $this->data['idArtist'] ?: null;
    }

    public function getRawIdArranger(): ?string
    {
        return $this->data['idArranger'] ?: null;
    }

    public function getBpm(): ?int
    {
        return isset($this->data['bpm']) && $this->data['bpm'] !== null ? (int)$this->data['bpm'] : null;
    }

    public function getDuration(): ?int
    {
        return isset($this->data['duration']) && $this->data['duration'] !== null ? (int)$this->data['duration'] : null;
    }

    public function getChartKey(): ?string
    {
        return $this->data['chartKey'] ?: null;
    }

    public function getNotes(): ?string
    {
        return $this->data['notes'] ?: null;
    }

    public function getPdfPath(): ?string
    {
        return $this->data['pdfPath'] ?: null;
    }

    public function getAudioPath(): ?string
    {
        return $this->data['audioPath'] ?: null;
    }

    public function getCreatedAt(): string
    {
        return $this->data['created_at'];
    }

    /**
     * @throws Exception
     */
    public function getArtist(): ?Artist
    {
        return $this->data['idArtist'] ? new Artist($this->data['idArtist']) : null;
    }

    /**
     * @throws Exception
     */
    public function getArranger(): ?Arranger
    {
        return $this->data['idArranger'] ? new Arranger($this->data['idArranger']) : null;
    }

    /**
     * @throws Exception
     */
    public function UpdateChart(
        string  $chartName,
        ?string $idArtist,
        ?string $idArranger,
        ?int    $bpm,
        ?int    $duration,
        ?string $chartKey,
        ?string $notes
    ): void {
        $this->db->query(
            "UPDATE `charts` SET `chartName`=?, `idArtist`=?, `idArranger`=?, `bpm`=?, `duration`=?, `chartKey`=?, `notes`=? WHERE `idChart`=?",
            [$chartName, $idArtist ?: null, $idArranger ?: null, $bpm, $duration, $chartKey ?: null, $notes ?: null, $this->data['idChart']]
        );
        $this->data['chartName']  = $chartName;
        $this->data['idArtist']   = $idArtist;
        $this->data['idArranger'] = $idArranger;
        $this->data['bpm']        = $bpm;
        $this->data['duration']   = $duration;
        $this->data['chartKey']   = $chartKey;
        $this->data['notes']      = $notes;
    }

    /**
     * @throws Exception
     */
    public function SetPdfPath(string $pdfPath): void
    {
        $this->db->query("UPDATE `charts` SET `pdfPath` = ? WHERE `idChart` = ?", [$pdfPath, $this->data['idChart']]);
        $this->data['pdfPath'] = $pdfPath;
    }

    /**
     * @throws Exception
     */
    public function SetAudioPath(?string $audioPath): void
    {
        $this->db->query("UPDATE `charts` SET `audioPath` = ? WHERE `idChart` = ?", [$audioPath, $this->data['idChart']]);
        $this->data['audioPath'] = $audioPath;
    }

    /**
     * @throws Exception
     */
    public function DeleteChart(): void
    {
        $this->db->query("DELETE FROM `charts` WHERE `idChart` = ?", [$this->data['idChart']]);
    }

    /**
     * Returns all PDF parts assigned to this chart.
     * @throws Exception
     */
    public function getPdfParts(): array
    {
        return $this->db->query(
            "SELECT cpp.*, it.instrumentName
             FROM `chart__pdf_parts` cpp
             LEFT JOIN `instrument__types` it ON it.idInstrument = cpp.idInstrument
             WHERE cpp.idChart = ?
             ORDER BY it.sortOrder, it.instrumentName",
            [$this->data['idChart']]
        );
    }

    /**
     * Assign or replace a PDF part for a specific instrument.
     * @throws Exception
     */
    public function setPdfPart(string $idInstrument, string $pdfPath, ?array $pages = null): void
    {
        $pagesJson = $pages !== null ? json_encode(array_values($pages)) : null;
        $existing = $this->db->query(
            "SELECT `idChartPdfPart` FROM `chart__pdf_parts` WHERE `idChart` = ? AND `idInstrument` = ?",
            [$this->data['idChart'], $idInstrument]
        );
        if (isset($existing[0])) {
            $this->db->query(
                "UPDATE `chart__pdf_parts` SET `pdfPath` = ?, `pages` = ? WHERE `idChartPdfPart` = ?",
                [$pdfPath, $pagesJson, $existing[0]['idChartPdfPart']]
            );
        } else {
            $this->db->query(
                "INSERT INTO `chart__pdf_parts` (`idChartPdfPart`, `idChart`, `idInstrument`, `pdfPath`, `pages`) VALUES (?, ?, ?, ?, ?)",
                [Helper::UUIDv4(), $this->data['idChart'], $idInstrument, $pdfPath, $pagesJson]
            );
        }
    }

    /**
     * Remove a PDF part assignment for a specific instrument.
     * @throws Exception
     */
    public function removePdfPart(string $idInstrument): void
    {
        $this->db->query(
            "DELETE FROM `chart__pdf_parts` WHERE `idChart` = ? AND `idInstrument` = ?",
            [$this->data['idChart'], $idInstrument]
        );
    }

    /**
     * Get this user's personal fields for this chart.
     * @throws Exception
     */
    public function getUserFields(string $idUser): ?array
    {
        $rows = $this->db->query(
            "SELECT * FROM `chart__user_fields` WHERE `idChart` = ? AND `idUser` = ?",
            [$this->data['idChart'], $idUser]
        );
        return $rows[0] ?? null;
    }

    /**
     * Save personal fields for a user on this chart (upsert).
     * @throws Exception
     */
    public function setUserFields(
        string  $idUser,
        ?int    $starRating,
        ?string $privateNotes,
        ?string $instrumentNotes,
        ?string $familyNotes
    ): void {
        $existing = $this->getUserFields($idUser);
        if ($existing) {
            $this->db->query(
                "UPDATE `chart__user_fields` SET `starRating`=?, `privateNotes`=?, `instrumentNotes`=?, `familyNotes`=?
                 WHERE `idChart`=? AND `idUser`=?",
                [$starRating, $privateNotes ?: null, $instrumentNotes ?: null, $familyNotes ?: null, $this->data['idChart'], $idUser]
            );
        } else {
            $this->db->query(
                "INSERT INTO `chart__user_fields` (`idChartUserField`, `idChart`, `idUser`, `starRating`, `privateNotes`, `instrumentNotes`, `familyNotes`)
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [Helper::UUIDv4(), $this->data['idChart'], $idUser, $starRating, $privateNotes ?: null, $instrumentNotes ?: null, $familyNotes ?: null]
            );
        }
    }

    /**
     * Get instrument notes left by all users who share any of the given instrument IDs.
     * @throws Exception
     */
    public function getInstrumentNotesForInstruments(array $instrumentIds): array
    {
        if (empty($instrumentIds)) {
            return [];
        }
        // Build placeholders
        $placeholders = implode(',', array_fill(0, count($instrumentIds), '?'));
        $params = array_merge([$this->data['idChart']], $instrumentIds);
        return $this->db->query(
            "SELECT cuf.instrumentNotes, u.nameShort, u.username
             FROM `chart__user_fields` cuf
             JOIN `users` u ON u.id = cuf.idUser
             WHERE cuf.idChart = ?
               AND cuf.instrumentNotes IS NOT NULL
               AND cuf.instrumentNotes != ''
               AND EXISTS (
                   SELECT 1 FROM `link__user_instrument` lui
                   WHERE lui.idUser = cuf.idUser AND lui.idInstrument IN ($placeholders)
               )
             ORDER BY u.nameShort, u.username",
            $params
        );
    }

    /**
     * Get family notes left by all users who share any of the given instrument family IDs.
     * @throws Exception
     */
    public function getFamilyNotesForFamilies(array $familyIds): array
    {
        if (empty($familyIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($familyIds), '?'));
        $params = array_merge([$this->data['idChart']], $familyIds);
        return $this->db->query(
            "SELECT cuf.familyNotes, u.nameShort, u.username
             FROM `chart__user_fields` cuf
             JOIN `users` u ON u.id = cuf.idUser
             WHERE cuf.idChart = ?
               AND cuf.familyNotes IS NOT NULL
               AND cuf.familyNotes != ''
               AND EXISTS (
                   SELECT 1 FROM `link__user_instrument` lui
                   JOIN `instrument__types` it ON it.idInstrument = lui.idInstrument
                   WHERE lui.idUser = cuf.idUser AND it.idInstrumentFamily IN ($placeholders)
               )
             ORDER BY u.nameShort, u.username",
            $params
        );
    }

    /**
     * @throws Exception
     */
    public static function CreateChart(
        string  $chartName,
        ?string $idArtist,
        ?string $idArranger,
        ?int    $bpm,
        ?int    $duration,
        ?string $chartKey,
        ?string $notes
    ): Chart {
        $db = new DatabaseManager();
        $idChart = Helper::UUIDv4();
        try {
            $db->query(
                "INSERT INTO `charts` (`idChart`, `chartName`, `idArtist`, `idArranger`, `bpm`, `duration`, `chartKey`, `notes`)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [$idChart, $chartName, $idArtist ?: null, $idArranger ?: null, $bpm, $duration, $chartKey ?: null, $notes ?: null]
            );
        } catch (Exception $e) {
            throw new Exception("Error creating Chart: " . $e->getMessage());
        }
        return new Chart($idChart);
    }

    /**
     * Returns all charts ordered by name.
     * @throws Exception
     */
    public static function GetAll(): array
    {
        $db = new DatabaseManager();
        $rows = $db->query("SELECT * FROM `charts` ORDER BY `chartName`");
        return array_map(fn($row) => new Chart($row['idChart']), $rows);
    }

    /**
     * Returns charts visible to a specific user based on their instrument assignments.
     * Shows charts with no PDF parts (master PDF only) and charts where the user's
     * instrument has been assigned a PDF part.
     * @throws Exception
     */
    public static function GetAllForUser(string $idUser): array
    {
        $db = new DatabaseManager();
        $rows = $db->query(
            "SELECT DISTINCT c.*
             FROM `charts` c
             WHERE
               -- Chart has no instrument parts yet (accessible to all)
               NOT EXISTS (SELECT 1 FROM `chart__pdf_parts` cpp WHERE cpp.idChart = c.idChart)
               OR
               -- Chart has a part assigned to one of the user's instruments
               EXISTS (
                   SELECT 1
                   FROM `chart__pdf_parts` cpp
                   JOIN `link__user_instrument` lui ON lui.idInstrument = cpp.idInstrument
                   WHERE cpp.idChart = c.idChart AND lui.idUser = ?
               )
             ORDER BY c.chartName",
            [$idUser]
        );
        return array_map(fn($row) => new Chart($row['idChart']), $rows);
    }
}
