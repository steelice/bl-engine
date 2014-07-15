CREATE TABLE IF NOT EXISTS `pages_comments` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `owner_id` int(10) unsigned NOT NULL default '0',
  `entry_id` int(10) unsigned NOT NULL default '0',
  `datepost` int(10) unsigned NOT NULL default '0',
  `sort` int(11) NOT NULL default '0',
  `level` int(11) NOT NULL default '0',
  `parent_id` int(10) unsigned NOT NULL default '0',
  `deleted` tinyint(4) NOT NULL default '0',
  `blocked` tinyint(4) NOT NULL default '0',
  `ip` varchar(15) NOT NULL default '',
  `text` text NOT NULL,
  `approved` int(10) unsigned NOT NULL default '0',
  `email` varchar(255) NOT NULL default '',
  `username` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `entry_id` (`entry_id`),
  KEY `sort` (`sort`),
  KEY `level` (`level`),
  FULLTEXT KEY `text` (`text`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;