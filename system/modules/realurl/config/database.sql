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

  `realurl_defaultInherit` char(1) NOT NULL default '',
  `realurl_inherit` char(1) NOT NULL default '',
  `realurl_fragment` varbinary(128) NOT NULL default '',
  `realurl_root` int(10) unsigned NOT NULL default '0',

) ENGINE=MyISAM DEFAULT CHARSET=utf8;
