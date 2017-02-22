-- Create tables --

CREATE TABLE IF NOT EXISTS `call_treatment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(80) NOT NULL,
  `extension` varchar(10) NOT NULL,
  `caller_name` varchar(80) NOT NULL,
  `caller_num` varchar(80) NOT NULL,
  `action` varchar(45) NOT NULL DEFAULT 'quiet',
  PRIMARY KEY (`id`),
  KEY `idx_call_treatment_extension` (`extension`),
  KEY `idx_call_treatment_caller_name` (`caller_name`),
  KEY `idx_call_treatment_caller_num` (`caller_num`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `cdr_routes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  `cost` float NOT NULL DEFAULT '0',
  `min` int(11) NOT NULL DEFAULT '1',
  `increment` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `phonebook` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `number` varchar(45) NOT NULL,
  `name` varchar(15) NOT NULL,
  `notes` varchar(120) NOT NULL,
  `extension` varchar(10) NOT NULL,
  `speed_dial` varchar(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `user_config` (
  `user` varchar(45) NOT NULL,
  `keyname` varchar(45) NOT NULL,
  `value` varchar(120) NOT NULL,
  PRIMARY KEY (`user`,`keyname`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `cdr` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `calldate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `clid` varchar(80) NOT NULL DEFAULT '',
  `src` varchar(80) NOT NULL DEFAULT '',
  `dst` varchar(80) NOT NULL DEFAULT '',
  `dcontext` varchar(80) NOT NULL DEFAULT '',
  `channel` varchar(80) NOT NULL DEFAULT '',
  `dstchannel` varchar(80) NOT NULL DEFAULT '',
  `lastapp` varchar(80) NOT NULL DEFAULT '',
  `lastdata` varchar(80) NOT NULL DEFAULT '',
  `duration` int(11) NOT NULL DEFAULT '0',
  `billsec` int(11) NOT NULL DEFAULT '0',
  `disposition` varchar(45) NOT NULL DEFAULT '',
  `amaflags` int(11) NOT NULL DEFAULT '0',
  `accountcode` varchar(20) NOT NULL DEFAULT '',
  `uniqueid` varchar(32) NOT NULL DEFAULT '',
  `userfield` varchar(255) NOT NULL DEFAULT '',
  `cost` float NOT NULL DEFAULT '0',
  `route_name` varchar(45) NOT NULL DEFAULT 'unknown',
  `call_type` varchar(45) NOT NULL DEFAULT 'unknown',
  PRIMARY KEY (`id`),
  KEY `idx_cdr_src` (`src`),
  KEY `idx_cdr_dst` (`dst`),
  KEY `idx_cdr_calldate` (`calldate`),
  KEY `idx_cdr_clid` (`clid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `voicemessages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `msgnum` int(11) NOT NULL DEFAULT '0',
  `dir` varchar(80) DEFAULT '',
  `context` varchar(80) DEFAULT '',
  `macrocontext` varchar(80) DEFAULT '',
  `callerid` varchar(40) DEFAULT '',
  `origtime` varchar(40) DEFAULT '',
  `duration` varchar(20) DEFAULT '',
  `mailboxuser` varchar(80) DEFAULT '',
  `mailboxcontext` varchar(80) DEFAULT '',
  `recording` longblob,
  `flag` varchar(128) DEFAULT '',
  `msg_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dir` (`dir`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `users` (
  `extension` int(11) NOT NULL,
  `user` varchar(45) NOT NULL,
  `fullname` varchar(80) DEFAULT NULL,
  `cid_name` varchar(15) DEFAULT NULL,
  `pwhash` varchar(80) NOT NULL,
  `pgroups` varchar(250) NOT NULL DEFAULT 'cdr_view',
  `vbox_context` varchar(45) NOT NULL,
  `vbox_user` varchar(45) NOT NULL,
  `dial_string` varchar(120) NOT NULL,
  `vm_delay` int(11) NOT NULL DEFAULT '20',
  `did` varchar(40) NOT NULL,
  `last_caller` varchar(40) NOT NULL,
  PRIMARY KEY (`extension`),
  UNIQUE KEY `extension_UNIQUE` (`extension`),
  UNIQUE KEY `user_UNIQUE` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Create trigger for cdr insert --
DELIMITER $$

DROP TRIGGER IF EXISTS asterisk.cdr_update_cost$$

CREATE DEFINER=`root`@`localhost` TRIGGER `asterisk`.`cdr_update_cost` BEFORE INSERT ON `cdr` FOR EACH ROW
BEGIN

declare _min INTEGER;
declare _cost FLOAT;
declare _inc INTEGER;

DECLARE EXIT HANDLER FOR NOT FOUND BEGIN
    SET NEW.cost=0;
END;

SELECT
    cost, min, increment
INTO _cost , _min , _inc FROM
    cdr_routes
WHERE
    name = NEW.route_name;

SET _inc = GREATEST(_inc,1);

IF (NEW.billsec > 0) THEN
    SET NEW.billsec = GREATEST(_min, ceil(NEW.billsec / _inc) * _inc);
END IF;

SET NEW.cost = (NEW.billsec/60) * _cost;

END$$

DELIMITER ;

-- Add default admin user --
INSERT INTO 'users'
    ('extension', 'user', 'fullname', 'cid_name', 'pwhash', 'pgroups', 'vbox_context', 'vbox_user', 'dial_string', 'vm_delay', 'did')
VALUES
    ('100', 'admin', 'Administrator', 'Admin', '2bb80d537b1da3e38bd30361aa855686bde0eacd7162fef6a25fe97bf527a25b', '', 'default', '100', '', '20', '');
