#
# Table structure for table 'tt_products'
#
CREATE TABLE tt_products (
  uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
  pid int(11) unsigned DEFAULT '0' NOT NULL,
  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,  
  hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
  starttime int(11) unsigned DEFAULT '0' NOT NULL,
  endtime int(11) unsigned DEFAULT '0' NOT NULL,
  sorting int(11) unsigned DEFAULT '0' NOT NULL,
  title tinytext NOT NULL,
  note text NOT NULL,
  unit varchar(20) DEFAULT '' NOT NULL,  
  unit_factor varchar(6) DEFAULT '' NOT NULL,   
  price varchar(20) DEFAULT '' NOT NULL,
  price2 varchar(20) DEFAULT '' NOT NULL,
  price2_qty varchar(20) DEFAULT '' NOT NULL,
  price_factor varchar(20) DEFAULT '' NOT NULL,
  weight varchar(20) DEFAULT '' NOT NULL,  
  image tinyblob NOT NULL,
  datasheet tinyblob NOT NULL,
  www varchar(80) DEFAULT '' NOT NULL,
  itemnumber varchar(40) DEFAULT '' NOT NULL,
  category int(10) unsigned DEFAULT '0' NOT NULL,
  inStock int(11) DEFAULT '0' NOT NULL,
  inStock_low int(11) DEFAULT '0' NOT NULL,
  on_demand int(11) DEFAULT '0' NOT NULL,
  deleted tinyint(3) unsigned DEFAULT '0' NOT NULL,
  ordered int(10) unsigned DEFAULT '0' NOT NULL,
  fe_group int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (uid),
  KEY parent (pid)
);

#
# Table structure for table 'tt_products_language_overlay'
#
CREATE TABLE tt_products_language_overlay (
  uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
  pid int(11) unsigned DEFAULT '0' NOT NULL,
  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,
  hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
  prd_uid int(11) unsigned DEFAULT '0' NOT NULL,
  sys_language_uid int(11) unsigned DEFAULT '0' NOT NULL,
  title tinytext NOT NULL,
  note text NOT NULL,
  unit varchar(20) DEFAULT '' NOT NULL,  
  datasheet tinyblob NOT NULL,  
  www varchar(80) DEFAULT '' NOT NULL,
  deleted tinyint(3) unsigned DEFAULT '0' NOT NULL,
  PRIMARY KEY (uid),
  KEY parent (pid)
);

CREATE TABLE tt_products_post_rates (
  uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
  pid int(11) unsigned DEFAULT '0' NOT NULL,
  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,  
  weight_limit decimal(5,2) DEFAULT '0' NOT NULL,  
  local_rate varchar(20) DEFAULT '' NOT NULL,  
  a1_rate varchar(20) DEFAULT '' NOT NULL, 
  a2_rate varchar(20) DEFAULT '' NOT NULL, 
  a3_rate varchar(20) DEFAULT '' NOT NULL, 
  a4_rate varchar(20) DEFAULT '' NOT NULL, 
  a5_rate varchar(20) DEFAULT '' NOT NULL, 
  b_rate varchar(20) DEFAULT '' NOT NULL, 
  c_rate varchar(20) DEFAULT '' NOT NULL, 
  d_rate varchar(20) DEFAULT '' NOT NULL,   
  PRIMARY KEY (uid),
  KEY parent (pid)
);


#
# Table structure for table 'tt_products_cat'
#
CREATE TABLE tt_products_cat (
  uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
  pid int(11) unsigned DEFAULT '0' NOT NULL,
  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,
  hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
  deleted tinyint(3) unsigned DEFAULT '0' NOT NULL,  
  title tinytext NOT NULL,
  note text NOT NULL,
  image tinyblob NOT NULL,
  parent_cat blob NOT NULL,  

  PRIMARY KEY (uid),
  KEY parent (pid)
);

#
# Table structure for table 'tt_products_cat_language_overlay'
#
CREATE TABLE tt_products_cat_language_overlay (
  uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
  pid int(11) unsigned DEFAULT '0' NOT NULL,
  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,
  hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
  deleted tinyint(3) unsigned DEFAULT '0' NOT NULL,  
  title tinytext NOT NULL,
  note text NOT NULL,  
  cat_uid int(11) unsigned DEFAULT '0' NOT NULL,
  sys_language_uid int(11) unsigned DEFAULT '0' NOT NULL,
  PRIMARY KEY (uid),
  KEY parent (pid)
);

#
# Table structure for table 'tt_products_card_payments'
#
CREATE TABLE tt_products_card_payments (
  uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
  pid int(11) unsigned DEFAULT '0' NOT NULL,
  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,
  deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
  ord_uid int(11) unsigned DEFAULT '0' NOT NULL,
  order_id varchar(20) DEFAULT '' NOT NULL,
  session_id varchar(30) DEFAULT '' NOT NULL,  
  amount_num int(10) DEFAULT '0' NOT NULL,
  response_code char(3) DEFAULT '' NOT NULL,   
  cc_number_hash1 varchar(255) DEFAULT '' NOT NULL,
  cc_number_hash2 varchar(255) DEFAULT '' NOT NULL,  
  card_type varchar(20) DEFAULT '' NOT NULL,
  address_ok char(1) DEFAULT '' NOT NULL, 
  test char(1) DEFAULT '' NOT NULL,   
  auth_code varchar(16) DEFAULT '' NOT NULL,
  bin int(6) unsigned DEFAULT '0' NOT NULL,
  fraud tinyint(1) unsigned DEFAULT '0' NOT NULL,  
  sequence int(6) unsigned DEFAULT '0' NOT NULL,                   
  PRIMARY KEY (uid),
  KEY parent (ord_uid)
);

#
# Table structure for table 'sys_products_orders'
#
CREATE TABLE sys_products_orders (
  uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
  pid int(11) unsigned DEFAULT '0' NOT NULL,
  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,
  deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
  note text NOT NULL,
  forename varchar(80) DEFAULT '' NOT NULL,
  name varchar(80) DEFAULT '' NOT NULL,
  company varchar(80) DEFAULT '' NOT NULL,  
  vat_id varchar(20) DEFAULT '' NOT NULL,  
  telephone varchar(20) DEFAULT '' NOT NULL,
  email varchar(80) DEFAULT '' NOT NULL,
  payment varchar(80) DEFAULT '' NOT NULL,
  shipping varchar(80) DEFAULT '' NOT NULL,
  amount varchar(20) DEFAULT '' NOT NULL,
  email_notify tinyint(4) unsigned DEFAULT '0' NOT NULL,
  tracking_code varchar(20) DEFAULT '' NOT NULL,
  status tinyint(4) unsigned DEFAULT '0' NOT NULL,
  status_log blob NOT NULL,
  orderData mediumblob NOT NULL,
  session_id varchar(30) DEFAULT '' NOT NULL,
  amount_num int(10) unsigned DEFAULT '0' NOT NULL,
  street varchar(40) DEFAULT '' NOT NULL,
  street_n1 varchar(40) DEFAULT '' NOT NULL,
  street_n2 varchar(10) DEFAULT '' NOT NULL,
  city varchar(40) DEFAULT '' NOT NULL,
  zip varchar(10) DEFAULT '' NOT NULL,
  country_code char(3) DEFAULT '' NOT NULL,
  client_ip varchar(15) DEFAULT '' NOT NULL,
  PRIMARY KEY (uid),
  KEY parent (pid),
  KEY tracking (tracking_code),
  KEY status (status),
  KEY uid (uid,amount)
);

#
# Table structure for table 'sys_products_orders_mm_tt_products'
#
CREATE TABLE sys_products_orders_mm_tt_products (
  sys_products_orders_uid int(11) unsigned DEFAULT '0' NOT NULL,
  sys_products_orders_qty int(11) unsigned DEFAULT '0' NOT NULL,
  tt_products_uid int(11) unsigned DEFAULT '0' NOT NULL,
  KEY tt_products_uid (tt_products_uid),
  KEY sys_products_orders_uid (sys_products_orders_uid)
);
