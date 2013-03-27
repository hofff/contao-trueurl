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

  `bbit_turl_rootInherit` varchar(255) NOT NULL default '',
  `bbit_turl_defaultInherit` char(1) NOT NULL default '',
  
  `bbit_turl_inherit` char(1) NOT NULL default '',
  `bbit_turl_transparent` char(1) NOT NULL default '',
  `bbit_turl_ignoreRoot` char(1) NOT NULL default '',
  
  `bbit_turl_fragment` varbinary(128) NOT NULL default '',
  `bbit_turl_root` int(10) unsigned NOT NULL default '0',
  
  `bbit_turl_requestPattern` varchar(1022) NOT NULL default '',
  `bbit_turl_capturedParams` varchar(1022) NOT NULL default '',
  `bbit_turl_matchRequired` char(1) NOT NULL default '',

) ENGINE=MyISAM DEFAULT CHARSET=utf8;
