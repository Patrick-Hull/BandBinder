<?php
class InstrumentFamily
{
    private DatabaseManager $db;
    private array $data;

    /**
     * @throws Exception
     */
    public function __construct($idInstrumentFamily)
    {
        $this->db = New DatabaseManager();
        $sql = "SELECT * FROM `instrument__families` WHERE idInstrumentFamily = ?";
        $args = [$idInstrumentFamily];
        $rows = $this->db->query($sql, $args);
        if(!isset($rows[0])){
            throw new Exception("Invalid InstrumentFamily");
        }
        $this->data = $rows[0];
    }

    /**
     * Getters
     */
    public function getIdInstrumentFamily()
    {
        return $this->data['idInstrumentFamily'];
    }
    public function getInstrumentFamilyName()
    {
        return $this->data['instrumentFamilyName'];
    }

    /**
     * @throws Exception
     */
    public static function CreateInstrumentFamily(string $instrumentFamilyName): InstrumentFamily
    {
        $db = new DatabaseManager();
        $idInstrumentFamily = Helper::UUIDv4();
        $sql = "INSERT INTO `instrument__families` (`idInstrumentFamily`, `instrumentFamilyName`) VALUES (?, ?)";
        $args = [$idInstrumentFamily, $instrumentFamilyName];
        try {
            $db->query($sql, $args);
        } catch (Exception $e) {
            throw new Exception("Error creating Instrument Family: " . $e->getMessage());
        }
        return New InstrumentFamily($idInstrumentFamily);
    }

    /**
     * @throws Exception
     */
    public static function GetAll(): array
    {
        $db = new DatabaseManager();
        $sql = "SELECT * FROM `instrument__families` ORDER BY `instrumentFamilyName`";
        $rows = $db->query($sql);
        $response = [];
        foreach ($rows as $row) {
            $response[] = [
                'value' => $row['idInstrumentFamily'],
                'text'  => $row['instrumentFamilyName'],
            ];
        }
        return $response;
    }
}