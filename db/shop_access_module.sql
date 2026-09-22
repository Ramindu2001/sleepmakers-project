-- Shop access module: the same schema change as db/shop_access_install.php, as plain SQL
-- (MariaDB 10.4+ / MySQL 8). Prefer the installer - it is idempotent and reports what it did.
-- Run once, BEFORE uploading the module's code. See db/SHOP_ACCESS_MODULE.md.

ALTER TABLE shopusers
  ADD COLUMN UserRoles_URID INT(11) NULL COMMENT 'role in this shop' AFTER user_USID,
  ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = may enter the shop' AFTER UserRoles_URID;

-- every existing assignment keeps the rights its user has today
UPDATE shopusers
INNER JOIN user ON user.USID = shopusers.user_USID
SET shopusers.UserRoles_URID = user.UserRoles_URID
WHERE shopusers.UserRoles_URID IS NULL;

-- assignments of users that no longer exist: no role, no access
UPDATE shopusers SET UserRoles_URID = 0, is_active = 0 WHERE UserRoles_URID IS NULL;

ALTER TABLE shopusers MODIFY UserRoles_URID INT(11) NOT NULL COMMENT 'role in this shop';

-- one assignment per user per shop: keep the oldest
DELETE copy FROM shopusers copy
INNER JOIN shopusers original
  ON original.shop_SHID = copy.shop_SHID
 AND original.user_USID = copy.user_USID
 AND original.SUID < copy.SUID;

ALTER TABLE shopusers
  ADD UNIQUE KEY uq_shopusers_shop_user (shop_SHID, user_USID),
  ADD KEY idx_shopusers_role (UserRoles_URID);
