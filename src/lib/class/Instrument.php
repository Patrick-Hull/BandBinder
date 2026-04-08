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

    /**
     * @throws Exception
     */
    public function getIdInstrumentFamily(): InstrumentFamily
    {
        return new InstrumentFamily($this->data['idInstrumentFamily']);
    }



    /**
     * @throws Exception
     */
    public static function CreateInstrument(string $instrumentName, InstrumentFamily $idInstrumentFamily): Instrument
    {
        $db = new DatabaseManager();
        $idInstrument = Helper::UUIDv4();
        $sql = "INSERT INTO `instrument__types` (`idInstrument`, `idInstrumentFamily`, `instrumentName`) VALUES (?, ?, ?)";
        $args = [$idInstrument, $idInstrumentFamily->getIdInstrumentFamily(), $instrumentName];

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
        $sql = "SELECT * FROM `instrument__types`";
        $rows = $db->query($sql);
        foreach($rows as $row){
            $response[] = New Instrument($row['idInstrument']);
        }
        return $response;
    }
}