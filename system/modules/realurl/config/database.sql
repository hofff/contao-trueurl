-- **********************************************************
-- *                                                        *
-- * IMPORTANT NOTE                                         *
-- *                                                        *
-- * Do not import this file manually but use the TYPOlight *
-- * install tool to create and maintain database tables!   *
-- *                                                        *
-- **********************************************************

-- 
-- Table `tl_page`
-- 

CREATE TABLE `tl_page` (
  `folderAlias` char(1) NOT NULL default '',
  `subAlias` char(1) NOT NULL default '',
  `realurl_overwrite` char(1) NOT NULL default '',
  `realurl_root` int(10) unsigned NOT NULL default '0',
  `realurl_rootAlias` varchar(255) NOT NULL default '',
  `realurl_basealias` text NULL,
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
