<?php
/**
 * Shop access module - the schema change.
 * -----------------------------------------------------------------------------
 * Turns shopusers from plain membership into per-shop access:
 *
 *   shopusers.UserRoles_URID   the user's role IN THAT SHOP (was: one role everywhere)
 *   shopusers.is_active        access on/off - revoking keeps the row and the history
 *   uq_shopusers_shop_user     one assignment per user per shop
 *
 * Every existing assignment gets the role its user holds today, so nobody's rights change.
 *
 * ADDITIVE ONLY and idempotent: every step is skipped when it is already in place.
 * Run through db/shop_access_install.php (the tests call it directly).
 */
class ShopAccessMigration
{
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

        $this->step('shopusers.UserRoles_URID column',
            !$this->hasColumn('shopusers', 'UserRoles_URID'),
            "ALTER TABLE shopusers ADD COLUMN UserRoles_URID INT(11) NULL COMMENT 'role in this shop' AFTER user_USID;");

        $this->step('shopusers.is_active column',
            !$this->hasColumn('shopusers', 'is_active'),
            "ALTER TABLE shopusers ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = may enter the shop' AFTER UserRoles_URID;");

        if ($this->isNullable('shopusers', 'UserRoles_URID')) {
            $copied = $this->pdo->exec("UPDATE shopusers
                INNER JOIN user ON user.USID = shopusers.user_USID
                SET shopusers.UserRoles_URID = user.UserRoles_URID
                WHERE shopusers.UserRoles_URID IS NULL;");
            $this->say('ok', 'shop roles copied from each user\'s role: ' . $copied . ' assignment(s)');

            $orphans = $this->pdo->query("SELECT SUID, shop_SHID, user_USID FROM shopusers
                WHERE UserRoles_URID IS NULL ORDER BY SUID;")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($orphans as $row) {
                $this->say('warn', 'assignment ' . $row['SUID'] . ' (shop ' . $row['shop_SHID'] . ') belongs to user '
                    . $row['user_USID'] . ', who no longer exists: revoked');
            }//each orphan
            $this->pdo->exec("UPDATE shopusers SET UserRoles_URID = 0, is_active = 0 WHERE UserRoles_URID IS NULL;");
        }//backfill

        $this->step('shopusers.UserRoles_URID required',
            $this->isNullable('shopusers', 'UserRoles_URID'),
            "ALTER TABLE shopusers MODIFY UserRoles_URID INT(11) NOT NULL COMMENT 'role in this shop';");

        $this->mergeDuplicates();

        $this->step('unique key uq_shopusers_shop_user',
            !$this->hasIndex('shopusers', 'uq_shopusers_shop_user'),
            "ALTER TABLE shopusers ADD UNIQUE KEY uq_shopusers_shop_user (shop_SHID, user_USID);");

        $this->step('index idx_shopusers_role',
            !$this->hasIndex('shopusers', 'idx_shopusers_role'),
            "ALTER TABLE shopusers ADD KEY idx_shopusers_role (UserRoles_URID);");

        return $this->report;
    }//run

    //one assignment per user per shop: keep the oldest row, active if any copy was
    private function mergeDuplicates()
    {
        $groups = $this->pdo->query("SELECT shop_SHID, user_USID, MIN(SUID) AS keep_suid,
            MAX(is_active) AS any_active, COUNT(*) AS copies
            FROM shopusers GROUP BY shop_SHID, user_USID HAVING COUNT(*) > 1;")->fetchAll(PDO::FETCH_ASSOC);
        if (empty($groups)) {
            $this->say('skip', 'duplicate assignments - none');
            return;
        }//nothing to merge

        $keep = $this->pdo->prepare("UPDATE shopusers SET is_active = ? WHERE SUID = ?;");
        $drop = $this->pdo->prepare("DELETE FROM shopusers WHERE shop_SHID = ? AND user_USID = ? AND SUID <> ?;");
        foreach ($groups as $group) {
            $keep->execute(array($group['any_active'], $group['keep_suid']));
            $drop->execute(array($group['shop_SHID'], $group['user_USID'], $group['keep_suid']));
            $this->say('ok', 'user ' . $group['user_USID'] . ' in shop ' . $group['shop_SHID'] . ': '
                . ($group['copies'] - 1) . ' duplicate assignment(s) merged into ' . $group['keep_suid']);
        }//each duplicate group
    }//mergeDuplicates

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
        return $this->columnInfo($table, $column) !== false;
    }//hasColumn

    private function isNullable($table, $column)
    {
        $info = $this->columnInfo($table, $column);
        return $info !== false && $info['IS_NULLABLE'] === 'YES';
    }//isNullable

    private function columnInfo($table, $column)
    {
        $stmt = $this->pdo->prepare("SELECT IS_NULLABLE FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?;");
        $stmt->execute(array($table, $column));
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }//columnInfo

    private function hasIndex($table, $index)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?;");
        $stmt->execute(array($table, $index));
        return (int) $stmt->fetchColumn() > 0;
    }//hasIndex
}//ShopAccessMigration
