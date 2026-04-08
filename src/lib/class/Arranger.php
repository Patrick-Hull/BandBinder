<?php
class Arranger
{
    private DatabaseManager $db;
    private array $data;

    /**
     * @throws Exception
     */
    public function __construct(string $idArranger)
    {
        $this->db = new DatabaseManager();
        $rows = $this->db->query("SELECT * FROM `arrangers` WHERE `idArranger` = ?", [$idArranger]);
        if (!isset($rows[0])) {
            throw new Exception("Invalid Arranger");
        }
        $this->data = $rows[0];
    }

    /**
     * Getters
     */
    public function getIdArranger(): string
    {
        return $this->data['idArranger'];
    }

    public function getArrangerName(): string
    {
        return $this->data['arrangerName'];
    }

    /**
     * @throws Exception
     */
    public function UpdateArranger(string $arrangerName): void
    {
        $this->db->query(
            "UPDATE `arrangers` SET `arrangerName` = ? WHERE `idArranger` = ?",
            [$arrangerName, $this->data['idArranger']]
        );
        $this->data['arrangerName'] = $arrangerName;
    }

    /**
     * @throws Exception
     */
    public function DeleteArranger(): void
    {
        $this->db->query("DELETE FROM `arrangers` WHERE `idArranger` = ?", [$this->data['idArranger']]);
    }

    /**
     * @throws Exception
     */
    public static function CreateArranger(string $arrangerName): Arranger
    {
        $db = new DatabaseManager();
        $idArranger = Helper::UUIDv4();
        try {
            $db->query(
                "INSERT INTO `arrangers` (`idArranger`, `arrangerName`) VALUES (?, ?)",
                [$idArranger, $arrangerName]
            );
        } catch (Exception $e) {
            throw new Exception("Error creating Arranger: " . $e->getMessage());
        }
        return new Arranger($idArranger);
    }

    /**
     * @throws Exception
     */
    public static function GetAll(): array
    {
        $db = new DatabaseManager();
        $rows = $db->query("SELECT * FROM `arrangers` ORDER BY `arrangerName`");
        return array_map(fn($row) => new Arranger($row['idArranger']), $rows);
    }
}
