-- Per-shop permissions (db/SHOP_PERMISSIONS_MODULE.md): the schema half of
-- db/shop_permissions_install.php, for a manual install (MariaDB).
--
-- The installer is the one to run: it also copies each role's ticks into every shop and
-- removes the shared rows, which plain SQL cannot do safely on its own. This file only adds
-- the columns, so an upgrade done this way leaves every role ticked for shop 0 (= nowhere)
-- until the installer is run.

ALTER TABLE `userroleaccess`
  ADD COLUMN IF NOT EXISTS `shop_SHID` INT(11) NOT NULL DEFAULT 0
  COMMENT 'the shop these rights apply in' AFTER `UserRolls_URID`;

ALTER TABLE `usermoduleaccess`
  ADD COLUMN IF NOT EXISTS `shop_SHID` INT(11) NOT NULL DEFAULT 0
  COMMENT 'the shop these rights apply in' AFTER `UserRoles_URID`;

-- one row per role, shop and feature/module (the installer merges older duplicates first)
ALTER TABLE `userroleaccess`
  ADD UNIQUE KEY IF NOT EXISTS `uq_roleaccess_role_shop_feature` (`UserRolls_URID`, `shop_SHID`, `SysFeatures_SFID`);

ALTER TABLE `usermoduleaccess`
  ADD UNIQUE KEY IF NOT EXISTS `uq_moduleaccess_role_shop_module` (`UserRoles_URID`, `shop_SHID`, `SysModules_SMID`);
