<?php
/**
 * Per-shop permissions - the schema change.
 * -----------------------------------------------------------------------------
 * Until now the ticks behind a role were shared: "Cashier" meant the same rights in every
 * shop, so ticking a feature for the showroom ticked it for the warehouse as well. They now
 * belong to ONE role IN ONE SHOP:
 *
 *   userroleaccess.shop_SHID    the shop these ticks apply in
 *   usermoduleaccess.shop_SHID  the shop this menu module is shown in
 *
 * Nobody's rights change on the day of the upgrade: every shared row is copied into every
 * shop, and from then on each shop is edited on its own (Public/edit-role.php works in the
 * shop you are signed into). A row still marked 0 (shared) is what an older page left
 * behind; the copies replace it.
 *
 * ADDITIVE ONLY and idempotent: every step is skipped when it is already in place.
 * Run through db/shop_permissions_install.php (the tests call it directly).
 * See db/SHOP_PERMISSIONS_MODULE.md.
 */
class ShopPermissionsMigration
{
    //the two tables, with the column naming the role and the column naming what is granted
    const TABLES = array(
        'userroleaccess' => array(
            'role' => 'UserRolls_URID', 'grant' => 'SysFeatures_SFID', 'id' => 'RAID',
            'columns' => array('is_create', 'is_edit', 'is_view', 'is_delete', 'is_verify', 'is_print'),
            'unique' => 'uq_roleaccess_role_shop_feature',
        ),
        'usermoduleaccess' => array(
            'role' => 'UserRoles_URID', 'grant' => 'SysModules_SMID', 'id' => 'MAID',
            'columns' => array(),
            'unique' => 'uq_moduleaccess_role_shop_module',
        ),
    );

    private $pdo;
    private $report = array();

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }//construct

    //apply every step; returns one report line per step
    public function run()
    {
        $this->report = array();

        foreach (self::TABLES as $table => $meta) {
            $this->step($table . '.shop_SHID column',
                !$this->hasColumn($table, 'shop_SHID'),
                "ALTER TABLE " . $table . " ADD COLUMN shop_SHID INT(11) NOT NULL DEFAULT 0"
                . " COMMENT 'the shop these rights apply in' AFTER " . $meta['role'] . ";");
        }//each table

        $shops = $this->pdo->query("SELECT SHID FROM shop ORDER BY SHID;")->fetchAll(PDO::FETCH_COLUMN);

        foreach (self::TABLES as $table => $meta) {
            $this->mergeDuplicates($table, $meta);
            $this->spreadOverShops($table, $meta, $shops);
        }//each table

        foreach (self::TABLES as $table => $meta) {
            $this->step('unique key ' . $meta['unique'],
                !$this->hasIndex($table, $meta['unique']),
                "ALTER TABLE " . $table . " ADD UNIQUE KEY " . $meta['unique']
                . " (" . $meta['role'] . ", shop_SHID, " . $meta['grant'] . ");");
        }//each table

        return $this->report;
    }//run

    //one row per role, shop and grant: keep the oldest, which is the row every permission
    //check reads today (they all take the first row they are given)
    private function mergeDuplicates($table, array $meta)
    {
        $removed = $this->pdo->exec("DELETE newer FROM " . $table . " AS newer"
            . " INNER JOIN " . $table . " AS older"
            . " ON newer." . $meta['role'] . " = older." . $meta['role']
            . " AND newer.shop_SHID = older.shop_SHID"
            . " AND newer." . $meta['grant'] . " = older." . $meta['grant']
            . " AND newer." . $meta['id'] . " > older." . $meta['id'] . ";");

        $this->say($removed > 0 ? 'ok' : 'skip',
            $table . ' duplicates - ' . ($removed > 0 ? $removed . ' removed' : 'none'));
    }//mergeDuplicates

    //copy every shared row (shop 0) into each shop, then drop the shared rows
    private function spreadOverShops($table, array $meta, array $shops)
    {
        $shared = $this->pdo->query("SELECT * FROM " . $table . " WHERE shop_SHID = 0"
            . " AND " . $meta['role'] . " IS NOT NULL ORDER BY " . $meta['id'] . ";")->fetchAll(PDO::FETCH_ASSOC);
        $orphans = (int) $this->pdo->query("SELECT COUNT(*) FROM " . $table
            . " WHERE shop_SHID = 0 AND " . $meta['role'] . " IS NULL;")->fetchColumn();

        if (empty($shared) && $orphans === 0) {
            $this->say('skip', $table . ' - every row already belongs to a shop');
            return;
        }//nothing shared left

        if (empty($shops)) {
            $this->say('warn', $table . ' - ' . count($shared)
                . ' shared row(s) left as they are: there are no shops to copy them into');
            return;
        }//no shop to copy into

        $columns = array_merge($meta['columns'], array($meta['role'], 'shop_SHID', $meta['grant']));
        $insert = $this->pdo->prepare("INSERT INTO " . $table . " (" . implode(', ', $columns) . ")"
            . " VALUES (" . implode(', ', array_fill(0, count($columns), '?')) . ");");
        $taken = $this->pdo->prepare("SELECT COUNT(*) FROM " . $table . " WHERE " . $meta['role'] . " = ?"
            . " AND shop_SHID = ? AND " . $meta['grant'] . " = ?;");

        $copied = 0;
        foreach ($shops as $shop) {
            foreach ($shared as $row) {
                $taken->execute(array($row[$meta['role']], $shop, $row[$meta['grant']]));
                if ((int) $taken->fetchColumn() > 0) {
                    continue;
                }//this shop has its own ticks already - never overwrite them

                $values = array();
                foreach ($meta['columns'] as $column) {
                    $values[] = $row[$column];
                }//the tick columns
                $values[] = $row[$meta['role']];
                $values[] = $shop;
                $values[] = $row[$meta['grant']];
                $insert->execute($values);
                $copied++;
            }//each shared row
        }//each shop

        $dropped = $this->pdo->exec("DELETE FROM " . $table . " WHERE shop_SHID = 0;");
        $this->say('ok', $table . ' - ' . count($shared) . ' shared row(s) now belong to a shop: '
            . $copied . ' copied into ' . count($shops) . ' shop(s), ' . $dropped . ' shared row(s) removed');

        if ($orphans > 0) {
            $this->say('warn', $table . ' - ' . $orphans . ' row(s) belonged to no role at all and were removed');
        }//junk rows
    }//spreadOverShops

    private function step($label, $needed, $sql)
    {
        if (!$needed) {
            $this->say('skip', $label . ' - already in place');
            return;
        }//nothing to do

        $this->pdo->exec($sql);
        $this->say('ok', $label);
    }//step

    private function say($status, $text)
    {
        $this->report[] = '[' . $status . '] ' . $text;
    }//say

    private function hasColumn($table, $column)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?;");
        $stmt->execute(array($table, $column));
        return (int) $stmt->fetchColumn() > 0;
    }//hasColumn

    private function hasIndex($table, $index)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?;");
        $stmt->execute(array($table, $index));
        return (int) $stmt->fetchColumn() > 0;
    }//hasIndex
}//ShopPermissionsMigration
