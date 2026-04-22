<?php
class Category
{
    private DatabaseManager $db;
    private array $data;

    /**
     * @throws Exception
     */
    public function __construct(string $idCategory)
    {
        $this->db = new DatabaseManager();
        $rows = $this->db->query("SELECT * FROM `chart__categories` WHERE `idCategory` = ?", [$idCategory]);
        if (!isset($rows[0])) {
            throw new Exception("Invalid Category");
        }
        $this->data = $rows[0];
    }

    /**
     * Getters
     */
    public function getIdCategory(): string
    {
        return $this->data['idCategory'];
    }

    public function getCategoryName(): string
    {
        return $this->data['categoryName'];
    }

    public function getCategoryColour(): ?string
    {
        return $this->data['categoryColour'] ?: null;
    }

    /**
     * @throws Exception
     */
    public function UpdateCategory(string $categoryName, ?string $categoryColour): void
    {
        $this->db->query(
            "UPDATE `chart__categories` SET `categoryName` = ?, `categoryColour` = ? WHERE `idCategory` = ?",
            [$categoryName, $categoryColour ?: null, $this->data['idCategory']]
        );
        $this->data['categoryName']  = $categoryName;
        $this->data['categoryColour'] = $categoryColour;
    }

    /**
     * @throws Exception
     */
    public function DeleteCategory(): void
    {
        $this->db->query("DELETE FROM `chart__categories` WHERE `idCategory` = ?", [$this->data['idCategory']]);
    }

    /**
     * Link this category to a chart.
     * @throws Exception
     */
    public function linkToChart(string $idChart): void
    {
        $this->db->query(
            "INSERT IGNORE INTO `link__chart_category` (`idChart`, `idCategory`) VALUES (?, ?)",
            [$idChart, $this->data['idCategory']]
        );
    }

    /**
     * Unlink this category from a chart.
     * @throws Exception
     */
    public function unlinkFromChart(string $idChart): void
    {
        $this->db->query(
            "DELETE FROM `link__chart_category` WHERE `idChart` = ? AND `idCategory` = ?",
            [$idChart, $this->data['idCategory']]
        );
    }

    /**
     * Get all charts linked to this category.
     * @throws Exception
     */
    public function getCharts(): array
    {
        $rows = $this->db->query(
            "SELECT c.idChart FROM `charts` c
             JOIN `link__chart_category` lcc ON lcc.idChart = c.idChart
             WHERE lcc.idCategory = ?
             ORDER BY c.chartName",
            [$this->data['idCategory']]
        );
        return array_map(fn($row) => new Chart($row['idChart']), $rows);
    }

    /**
     * @throws Exception
     */
    public static function CreateCategory(string $categoryName, ?string $categoryColour): Category
    {
        $db = new DatabaseManager();
        $idCategory = Helper::UUIDv4();
        try {
            $db->query(
                "INSERT INTO `chart__categories` (`idCategory`, `categoryName`, `categoryColour`) VALUES (?, ?, ?)",
                [$idCategory, $categoryName, $categoryColour ?: null]
            );
        } catch (Exception $e) {
            throw new Exception("Error creating Category: " . $e->getMessage());
        }
        return new Category($idCategory);
    }

    /**
     * @throws Exception
     */
    public static function GetAll(): array
    {
        $db = new DatabaseManager();
        $rows = $db->query("SELECT * FROM `chart__categories` ORDER BY `categoryName`");
        return array_map(fn($row) => new Category($row['idCategory']), $rows);
    }

    /**
     * Get all categories linked to a specific chart.
     * @throws Exception
     */
    public static function GetByChart(string $idChart): array
    {
        $db = new DatabaseManager();
        $rows = $db->query(
            "SELECT cc.idCategory FROM `chart__categories` cc
             JOIN `link__chart_category` lcc ON lcc.idCategory = cc.idCategory
             WHERE lcc.idChart = ?
             ORDER BY cc.categoryName",
            [$idChart]
        );
        return array_map(fn($row) => new Category($row['idCategory']), $rows);
    }

    /**
     * Get categories for multiple charts (returns map of chartId => categories).
     * @throws Exception
     */
    public static function GetByCharts(array $chartIds): array
    {
        if (empty($chartIds)) {
            return [];
        }
        $db = new DatabaseManager();
        $placeholders = implode(',', array_fill(0, count($chartIds), '?'));
        $rows = $db->query(
            "SELECT lcc.idChart, cc.idCategory, cc.categoryName, cc.categoryColour
             FROM `link__chart_category` lcc
             JOIN `chart__categories` cc ON cc.idCategory = lcc.idCategory
             WHERE lcc.idChart IN ($placeholders)
             ORDER BY lcc.idChart, cc.categoryName",
            $chartIds
        );
        $result = [];
        foreach ($rows as $row) {
            $idChart = $row['idChart'];
            if (!isset($result[$idChart])) {
                $result[$idChart] = [];
            }
            $result[$idChart][] = [
                'idCategory'    => $row['idCategory'],
                'categoryName'   => $row['categoryName'],
                'categoryColour'=> $row['categoryColour'],
            ];
        }
        return $result;
    }
}