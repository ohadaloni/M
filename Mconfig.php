<?php
/*------------------------------------------------------------*/
/**
  * M configuration
  *
  * define database access parameters
  *
  * M_USER - MySQL login name
  *
  * M_PASSWORD - password
  *
  * M_DBNAME - the name of the default database
  *
  * Quick installation instructions:
  * 1. Uncompress M.tar.gz somwhere under the document root of your server.
  * 2. Using mysql, create a new database called mdemo: create database mdemo default charset utf8
  * 3. In the file Mdemo/Mconfig.php, change M_USER and M_PASSWORD to your access credentials.
  * 4. The installation is complete. Point your browser to Mdemo.
  *
  * @package M
  * @author Ohad Aloni
  */
/*------------------------------------------------------------*/
/**
 * there is no need to change this file at all if these constants
 * are define before including the M files
 * this is useful when several applications with different
 * configurations use the same M source
 */

if ( ! defined('M_USER') )
	define('M_USER', 'msdb');
if ( ! defined('M_PASSWORD') )
	define('M_PASSWORD', 'msdb');
if ( ! defined('M_DBNAME') )
	define('M_DBNAME', 'mdemo');
/*------------------------------------------------------------*/
