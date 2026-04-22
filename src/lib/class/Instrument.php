<?php
class Instrument
{
    private DatabaseManager $db;
    private array $data;

    /**
     * @throws Exception
     */
    public function __construct($idInstrument)
    {
        $this->db = New DatabaseManager();
        $sql = "SELECT * FROM `instrument__types` WHERE idInstrument = ?";
        $args = [$idInstrument];
        $rows = $this->db->query($sql, $args);
        if(!isset($rows[0])){
            throw new Exception("Invalid Instrument");
        }
        $this->data = $rows[0];
    }
    /**
     * Getters
     */
    public function getIdInstrument()
    {
        return $this->data['idInstrument'];
    }
    public function getInstrumentName()
    {
        return $this->data['instrumentName'];
    }

    public function getSortOrder()
    {
        return $this->data['sortOrder'] ?? null;
    }

    /**
     * @throws Exception
     */
    public function getIdInstrumentFamily(): InstrumentFamily
    {
        return new InstrumentFamily($this->data['idInstrumentFamily']);
    }

    public function getRawIdInstrumentFamily(): string
    {
        return $this->data['idInstrumentFamily'];
    }

    /**
     * @throws Exception
     */
    public function UpdateInstrument(string $instrumentName, InstrumentFamily $family): void
    {
        $sql = "UPDATE `instrument__types` SET `instrumentName` = ?, `idInstrumentFamily` = ? WHERE `idInstrument` = ?";
        $args = [$instrumentName, $family->getIdInstrumentFamily(), $this->data['idInstrument']];
        $this->db->query($sql, $args);
        $this->data['instrumentName'] = $instrumentName;
        $this->data['idInstrumentFamily'] = $family->getIdInstrumentFamily();
    }

    /**
     * @throws Exception
     */
    public function DeleteInstrument(): void
    {
        $this->db->query(
            "DELETE FROM `instrument__types` WHERE `idInstrument` = ?",
            [$this->data['idInstrument']]
        );

        $remaining = $this->db->query(
            "SELECT `idInstrument` FROM `instrument__types` ORDER BY `sortOrder` IS NULL, `sortOrder`, `instrumentName`"
        );
        $orderedIds = array_column($remaining, 'idInstrument');
        if (!empty($orderedIds)) {
            static::UpdateOrder($orderedIds);
        }
    }

    /**
     * @throws Exception
     */
    public static function UpdateOrder(array $orderedIds): void
    {
        $db = new DatabaseManager();
        foreach ($orderedIds as $position => $id) {
            $sql = "UPDATE `instrument__types` SET `sortOrder` = ? WHERE `idInstrument` = ?";
            $db->query($sql, [$position + 1, $id]);
        }
    }

    /**
     * @throws Exception
     */
    public static function CreateInstrument(string $instrumentName, InstrumentFamily $idInstrumentFamily, ?string $customId = null, ?int $sortOrder = null): Instrument
    {
        $db = new DatabaseManager();
        $idInstrument = $customId ?: Helper::UUIDv4();
        
        // Get next sort order if not provided
        if ($sortOrder === null) {
            $maxOrder = $db->query("SELECT MAX(sortOrder) as maxOrder FROM `instrument__types` WHERE `idInstrumentFamily` = ?", [$idInstrumentFamily->getIdInstrumentFamily()]);
            $sortOrder = ($maxOrder[0]['maxOrder'] ?? 0) + 1;
        }
        
        $sql = "INSERT INTO `instrument__types` (`idInstrument`, `idInstrumentFamily`, `instrumentName`, `sortOrder`) VALUES (?, ?, ?, ?)";
        $args = [$idInstrument, $idInstrumentFamily->getIdInstrumentFamily(), $instrumentName, $sortOrder];

        try {
            $db->query($sql, $args);
        } catch (Exception $e) {
            throw new Exception("Error creating Instrument Family: " . $e->getMessage());
        }
        return New Instrument($idInstrument);
    }

    /**
     * @throws Exception
     */
    public static function GetAll($dataTablesFormat = false): array
    {
        $response = [];
        $db = new DatabaseManager();
        $sql = "SELECT * FROM `instrument__types` ORDER BY `sortOrder` IS NULL, `sortOrder`, `instrumentName`";
        $rows = $db->query($sql);
        foreach($rows as $row){
            $response[] = New Instrument($row['idInstrument']);
        }
        return $response;
    }
}