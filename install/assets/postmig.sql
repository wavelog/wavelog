-- -----------------------------------------------------
-- Write encrypted callbook credentials to the database
-- -----------------------------------------------------

INSERT INTO `options` VALUES (NULL, 'callbook_provider', '%%CALLBOOK_PROVIDER%%', 'yes');
INSERT INTO `options` VALUES (NULL, 'callbook_username', '%%CALLBOOK_USERNAME%%', 'yes');
INSERT INTO `options` VALUES (NULL, 'callbook_password', '%%CALLBOOK_PASSWORD%%', 'yes');
INSERT INTO `options` VALUES (NULL, 'callbook_fullname', '0', 'yes');