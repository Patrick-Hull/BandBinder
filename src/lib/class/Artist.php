<?php
class Artist
{
    private DatabaseManager $db;
    private array $data;

    /**
     * @throws Exception
     */
    public function __construct(string $idArtist)
    {
        $this->db = new DatabaseManager();
        $rows = $this->db->query("SELECT * FROM `artists` WHERE `idArtist` = ?", [$idArtist]);
        if (!isset($rows[0])) {
            throw new Exception("Invalid Artist");
        }
        $this->data = $rows[0];
    }

    /**
     * Getters
     */
    public function getIdArtist(): string
    {
        return $this->data['idArtist'];
    }

    public function getArtistName(): string
    {
        return $this->data['artistName'];
    }

    /**
     * @throws Exception
     */
    public function UpdateArtist(string $artistName): void
    {
        $this->db->query(
            "UPDATE `artists` SET `artistName` = ? WHERE `idArtist` = ?",
            [$artistName, $this->data['idArtist']]
        );
        $this->data['artistName'] = $artistName;
    }

    /**
     * @throws Exception
     */
    public function DeleteArtist(): void
    {
        $this->db->query("DELETE FROM `artists` WHERE `idArtist` = ?", [$this->data['idArtist']]);
    }

    /**
     * @throws Exception
     */
    public static function CreateArtist(string $artistName): Artist
    {
        $db = new DatabaseManager();
        $idArtist = Helper::UUIDv4();
        try {
            $db->query(
                "INSERT INTO `artists` (`idArtist`, `artistName`) VALUES (?, ?)",
                [$idArtist, $artistName]
            );
        } catch (Exception $e) {
            throw new Exception("Error creating Artist: " . $e->getMessage());
        }
        return new Artist($idArtist);
    }

    /**
     * @throws Exception
     */
    public static function GetAll(): array
    {
        $db = new DatabaseManager();
        $rows = $db->query("SELECT * FROM `artists` ORDER BY `artistName`");
        return array_map(fn($row) => new Artist($row['idArtist']), $rows);
    }
}
