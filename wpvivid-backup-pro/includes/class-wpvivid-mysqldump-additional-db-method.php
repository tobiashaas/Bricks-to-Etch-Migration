<?php

if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}

include_once WPVIVID_PLUGIN_DIR . '/includes/class-wpvivid-mysqldump-method.php';

if(class_exists('WPvividTypeAdapterFactory'))
{
    class WPvividTypeAdapterAdditionalWpdb extends WPvividTypeAdapterFactory
    {

        private $dbHandler = null;

        // Numerical Mysql types
        public $mysqlTypes = array(
            'numerical' => array(
                'bit',
                'tinyint',
                'smallint',
                'mediumint',
                'int',
                'integer',
                'bigint',
                'real',
                'double',
                'float',
                'decimal',
                'numeric'
            ),
            'blob' => array(
                'tinyblob',
                'blob',
                'mediumblob',
                'longblob',
                'binary',
                'varbinary',
                'bit',
                'geometry', /* http://bugs.mysql.com/bug.php?id=43544 */
                'point',
                'linestring',
                'polygon',
                'multipoint',
                'multilinestring',
                'multipolygon',
                'geometrycollection',
            )
        );

        public function __construct ($dbHandler)
        {
            $this->dbHandler = $dbHandler;
        }

        public function connect($host,$dbname,$user,$pass,$init_commands=array())
        {
            $dbuser     = defined( 'DB_USER' ) ? $user : '';
            $dbpassword = defined( 'DB_PASSWORD' ) ? $pass : '';
            $dbname     = defined( 'DB_NAME' ) ? $dbname : '';
            $dbhost     = defined( 'DB_HOST' ) ? $host : '';

            $this->dbHandler = new wpdb( $dbuser, $dbpassword, $dbname, $dbhost );
        }

        public function databases()
        {
            $this->check_parameters(func_num_args(), $expected_num_args = 1, __METHOD__);
            $args = func_get_args();
            $databaseName = $args[0];

            $resultSet = $this->query("SHOW VARIABLES LIKE 'character_set_database';");
            $characterSet = $resultSet->fetchColumn(1);
            $resultSet->closeCursor();

            $resultSet = $this->query("SHOW VARIABLES LIKE 'collation_database';");
            $collationDb = $resultSet->fetchColumn(1);
            $resultSet->closeCursor();
            $ret = "";

            $ret .= "CREATE DATABASE /*!32312 IF NOT EXISTS*/ `${databaseName}`".
                " /*!40100 DEFAULT CHARACTER SET ${characterSet} " .
                " COLLATE ${collationDb} */;" . PHP_EOL . PHP_EOL .
                "USE `${databaseName}`;" . PHP_EOL . PHP_EOL;

            return $ret;
        }

        public function show_create_table($tableName)
        {
            return "SHOW CREATE TABLE `$tableName`";
        }

        public function show_create_view($viewName)
        {
            return "SHOW CREATE VIEW `$viewName`";
        }

        public function show_create_trigger($triggerName)
        {
            return "SHOW CREATE TRIGGER `$triggerName`";
        }

        public function show_create_procedure($procedureName)
        {
            return "SHOW CREATE PROCEDURE `$procedureName`";
        }

        public function show_create_event($eventName)
        {
            return "SHOW CREATE EVENT `$eventName`";
        }

        public function create_table( $row, $dumpSettings )
        {

            if ( !isset($row['Create Table']) ) {
                throw new Exception("Error getting table code, unknown output");
            }

            //$createTable = str_replace('\'0000-00-00 00:00:00\'','\'1999-01-01 00:00:00\'',$row['Create Table']);
            $createTable = $row['Create Table'];

            if ( $dumpSettings['reset-auto-increment'] ) {
                $match = "/AUTO_INCREMENT=[0-9]+/s";
                $replace = "";
                $createTable = preg_replace($match, $replace, $createTable);
            }

            $ret = "/*!40101 SET @saved_cs_client     = @@character_set_client */;" . PHP_EOL .
                "/*!40101 SET character_set_client = " . $dumpSettings['default-character-set'] . " */;" . PHP_EOL .
                $createTable . ";" . PHP_EOL .
                "/*!40101 SET character_set_client = @saved_cs_client */;" . PHP_EOL .
                PHP_EOL;
            return $ret;
        }

        public function create_view($row)
        {
            $ret = "";
            if (!isset($row['Create View'])) {
                throw new Exception("Error getting view structure, unknown output");
            }

            $triggerStmt = $row['Create View'];

            $triggerStmtReplaced1 = str_replace(
                "CREATE ALGORITHM",
                "/*!50001 CREATE ALGORITHM",
                $triggerStmt
            );
            $triggerStmtReplaced2 = str_replace(
                " DEFINER=",
                " */" . PHP_EOL . "/*!50013 DEFINER=",
                $triggerStmtReplaced1
            );
            $triggerStmtReplaced3 = str_replace(
                " VIEW ",
                " */" . PHP_EOL . "/*!50001 VIEW ",
                $triggerStmtReplaced2
            );
            if (false === $triggerStmtReplaced1 ||
                false === $triggerStmtReplaced2 ||
                false === $triggerStmtReplaced3) {
                $triggerStmtReplaced = $triggerStmt;
            } else {
                $triggerStmtReplaced = $triggerStmtReplaced3 . " */;";
            }

            $ret .= $triggerStmtReplaced . PHP_EOL . PHP_EOL;
            return $ret;
        }

        public function create_trigger($row)
        {
            $ret = "";
            if (!isset($row['SQL Original Statement'])) {
                throw new Exception("Error getting trigger code, unknown output");
            }

            $triggerStmt = $row['SQL Original Statement'];
            $triggerStmtReplaced = str_replace(
                "CREATE DEFINER",
                "/*!50003 CREATE*/ /*!50017 DEFINER",
                $triggerStmt
            );
            $triggerStmtReplaced = str_replace(
                " TRIGGER",
                "*/ /*!50003 TRIGGER",
                $triggerStmtReplaced
            );
            if ( false === $triggerStmtReplaced ) {
                $triggerStmtReplaced = $triggerStmt . " /* ";
            }

            $ret .= "DELIMITER ;;" . PHP_EOL .
                $triggerStmtReplaced . " */ ;;" . PHP_EOL .
                "DELIMITER ;" . PHP_EOL . PHP_EOL;
            return $ret;
        }

        public function create_procedure($row, $dumpSettings)
        {
            $ret = "";
            if (!isset($row['Create Procedure'])) {
                throw new Exception("Error getting procedure code, unknown output. " .
                    "Please check 'https://bugs.mysql.com/bug.php?id=14564'");
            }
            $procedureStmt = $row['Create Procedure'];

            $ret .= "/*!50003 DROP PROCEDURE IF EXISTS `" .
                $row['Procedure'] . "` */;" . PHP_EOL .
                "/*!40101 SET @saved_cs_client     = @@character_set_client */;" . PHP_EOL .
                "/*!40101 SET character_set_client = " . $dumpSettings['default-character-set'] . " */;" . PHP_EOL .
                "DELIMITER ;;" . PHP_EOL .
                $procedureStmt . " ;;" . PHP_EOL .
                "DELIMITER ;" . PHP_EOL .
                "/*!40101 SET character_set_client = @saved_cs_client */;" . PHP_EOL . PHP_EOL;

            return $ret;
        }

        public function create_event($row)
        {
            $ret = "";
            if ( !isset($row['Create Event']) ) {
                throw new Exception("Error getting event code, unknown output. " .
                    "Please check 'http://stackoverflow.com/questions/10853826/mysql-5-5-create-event-gives-syntax-error'");
            }
            $eventName = $row['Event'];
            $eventStmt = $row['Create Event'];
            $sqlMode = $row['sql_mode'];

            $eventStmtReplaced = str_replace(
                "CREATE DEFINER",
                "/*!50106 CREATE*/ /*!50117 DEFINER",
                $eventStmt
            );
            $eventStmtReplaced = str_replace(
                " EVENT ",
                "*/ /*!50106 EVENT ",
                $eventStmtReplaced
            );

            if ( false === $eventStmtReplaced ) {
                $eventStmtReplaced = $eventStmt . " /* ";
            }

            $ret .= "/*!50106 SET @save_time_zone= @@TIME_ZONE */ ;" . PHP_EOL .
                "/*!50106 DROP EVENT IF EXISTS `" . $eventName . "` */;" . PHP_EOL .
                "DELIMITER ;;" . PHP_EOL .
                "/*!50003 SET @saved_cs_client      = @@character_set_client */ ;;" . PHP_EOL .
                "/*!50003 SET @saved_cs_results     = @@character_set_results */ ;;" . PHP_EOL .
                "/*!50003 SET @saved_col_connection = @@collation_connection */ ;;" . PHP_EOL .
                "/*!50003 SET character_set_client  = utf8 */ ;;" . PHP_EOL .
                "/*!50003 SET character_set_results = utf8 */ ;;" . PHP_EOL .
                "/*!50003 SET collation_connection  = utf8_general_ci */ ;;" . PHP_EOL .
                "/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;;" . PHP_EOL .
                "/*!50003 SET sql_mode              = '" . $sqlMode . "' */ ;;" . PHP_EOL .
                "/*!50003 SET @saved_time_zone      = @@time_zone */ ;;" . PHP_EOL .
                "/*!50003 SET time_zone             = 'SYSTEM' */ ;;" . PHP_EOL .
                $eventStmtReplaced . " */ ;;" . PHP_EOL .
                "/*!50003 SET time_zone             = @saved_time_zone */ ;;" . PHP_EOL .
                "/*!50003 SET sql_mode              = @saved_sql_mode */ ;;" . PHP_EOL .
                "/*!50003 SET character_set_client  = @saved_cs_client */ ;;" . PHP_EOL .
                "/*!50003 SET character_set_results = @saved_cs_results */ ;;" . PHP_EOL .
                "/*!50003 SET collation_connection  = @saved_col_connection */ ;;" . PHP_EOL .
                "DELIMITER ;" . PHP_EOL .
                "/*!50106 SET TIME_ZONE= @save_time_zone */ ;" . PHP_EOL . PHP_EOL;
            // Commented because we are doing this in restore_parameters()
            // "/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;" . PHP_EOL . PHP_EOL;

            return $ret;
        }

        public function show_tables()
        {
            $this->check_parameters(func_num_args(), $expected_num_args = 1, __METHOD__);
            $args = func_get_args();
            return "SELECT TABLE_NAME AS tbl_name " .
                "FROM INFORMATION_SCHEMA.TABLES " .
                "WHERE TABLE_TYPE='BASE TABLE' AND TABLE_SCHEMA='${args[0]}'";
        }

        public function show_views()
        {
            $this->check_parameters(func_num_args(), $expected_num_args = 1, __METHOD__);
            $args = func_get_args();
            return "SELECT TABLE_NAME AS tbl_name " .
                "FROM INFORMATION_SCHEMA.TABLES " .
                "WHERE TABLE_TYPE='VIEW' AND TABLE_SCHEMA='${args[0]}'";
        }

        public function show_triggers()
        {
            $this->check_parameters(func_num_args(), $expected_num_args = 1, __METHOD__);
            $args = func_get_args();
            return "SHOW TRIGGERS FROM `${args[0]}`;";
        }

        public function show_columns()
        {
            $this->check_parameters(func_num_args(), $expected_num_args = 1, __METHOD__);
            $args = func_get_args();
            return "SHOW COLUMNS FROM `${args[0]}`;";
        }

        public function show_procedures()
        {
            $this->check_parameters(func_num_args(), $expected_num_args = 1, __METHOD__);
            $args = func_get_args();
            return "SELECT SPECIFIC_NAME AS procedure_name " .
                "FROM INFORMATION_SCHEMA.ROUTINES " .
                "WHERE ROUTINE_TYPE='PROCEDURE' AND ROUTINE_SCHEMA='${args[0]}'";
        }

        /**
         * Get query string to ask for names of events from current database.
         *
         * @param string Name of database
         * @return string
         */
        public function show_events()
        {
            $this->check_parameters(func_num_args(), $expected_num_args = 1, __METHOD__);
            $args = func_get_args();
            return "SELECT EVENT_NAME AS event_name " .
                "FROM INFORMATION_SCHEMA.EVENTS " .
                "WHERE EVENT_SCHEMA='${args[0]}'";
        }

        public function setup_transaction()
        {
            return "SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ";
        }

        public function start_transaction()
        {
            return "START TRANSACTION";
        }

        public function commit_transaction()
        {
            return "COMMIT";
        }

        public function lock_table()
        {
            $this->check_parameters(func_num_args(), $expected_num_args = 1, __METHOD__);
            $args = func_get_args();
            return $this->dbHandler->get_results("LOCK TABLES `${args[0]}` READ LOCAL",ARRAY_A);

        }

        public function unlock_table()
        {
            return $this->dbHandler->get_results("UNLOCK TABLES",ARRAY_A);
        }

        public function start_add_lock_table()
        {
            $this->check_parameters(func_num_args(), $expected_num_args = 1, __METHOD__);
            $args = func_get_args();
            return "LOCK TABLES `${args[0]}` WRITE;" . PHP_EOL;
        }

        public function end_add_lock_table()
        {
            return "UNLOCK TABLES;" . PHP_EOL;
        }

        public function start_add_disable_keys()
        {
            $this->check_parameters(func_num_args(), $expected_num_args = 1, __METHOD__);
            $args = func_get_args();
            return "/*!40000 ALTER TABLE `${args[0]}` DISABLE KEYS */;" .
                PHP_EOL;
        }

        public function end_add_disable_keys()
        {
            $this->check_parameters(func_num_args(), $expected_num_args = 1, __METHOD__);
            $args = func_get_args();
            return "/*!40000 ALTER TABLE `${args[0]}` ENABLE KEYS */;" .
                PHP_EOL;
        }

        public function start_disable_autocommit()
        {
            return "SET autocommit=0;" . PHP_EOL;
        }

        public function end_disable_autocommit()
        {
            return "COMMIT;" . PHP_EOL;
        }

        public function add_drop_database()
        {
            $this->check_parameters(func_num_args(), $expected_num_args = 1, __METHOD__);
            $args = func_get_args();
            return "/*!40000 DROP DATABASE IF EXISTS `${args[0]}`*/;" .
                PHP_EOL . PHP_EOL;
        }

        public function add_drop_trigger()
        {
            $this->check_parameters(func_num_args(), $expected_num_args = 1, __METHOD__);
            $args = func_get_args();
            return "DROP TRIGGER IF EXISTS `${args[0]}`;" . PHP_EOL;
        }

        public function drop_table()
        {
            $this->check_parameters(func_num_args(), $expected_num_args = 1, __METHOD__);
            $args = func_get_args();
            return "DROP TABLE IF EXISTS `${args[0]}`;" . PHP_EOL;
        }

        public function drop_view()
        {
            $this->check_parameters(func_num_args(), $expected_num_args = 1, __METHOD__);
            $args = func_get_args();
            return "DROP TABLE IF EXISTS `${args[0]}`;" . PHP_EOL .
                "/*!50001 DROP VIEW IF EXISTS `${args[0]}`*/;" . PHP_EOL;
        }

        public function getDatabaseHeader()
        {
            $this->check_parameters(func_num_args(), $expected_num_args = 1, __METHOD__);
            $args = func_get_args();
            return "--" . PHP_EOL .
                "-- Current Database: `${args[0]}`" . PHP_EOL .
                "--" . PHP_EOL . PHP_EOL;
        }

        /**
         * Decode column metadata and fill info structure.
         * type, is_numeric and is_blob will always be available.
         *
         * @param array $colType Array returned from "SHOW COLUMNS FROM tableName"
         * @return array
         */
        public function parseColumnType($colType)
        {
            $colInfo = array();
            $colParts = explode(" ", $colType['Type']);

            if($fparen = strpos($colParts[0], "("))
            {
                $colInfo['type'] = substr($colParts[0], 0, $fparen);
                $colInfo['length']  = str_replace(")", "", substr($colParts[0], $fparen+1));
                $colInfo['attributes'] = isset($colParts[1]) ? $colParts[1] : NULL;
            }
            else
            {
                $colInfo['type'] = $colParts[0];
            }
            $colInfo['is_numeric'] = in_array($colInfo['type'], $this->mysqlTypes['numerical']);
            $colInfo['is_blob'] = in_array($colInfo['type'], $this->mysqlTypes['blob']);
            // for virtual 'Extra' -> "STORED GENERATED"
            $colInfo['is_virtual'] = strpos($colType['Extra'], "STORED GENERATED") === false ? false : true;

            return $colInfo;
        }

        public function get_connection_charset($wpdb = null) {
            if (null === $wpdb) {
                global $wpdb;
            }

            $charset = (defined('DB_CHARSET') && DB_CHARSET) ? DB_CHARSET : 'utf8mb4';

            if (method_exists($wpdb, 'determine_charset')) {
                $charset_collate = $wpdb->determine_charset($charset, '');
                if (!empty($charset_collate['charset'])) $charset = $charset_collate['charset'];
            }

            return $charset;
        }

        public function backup_parameters()
        {
            global $wpdb;
            $this->check_parameters(func_num_args(), $expected_num_args = 1, __METHOD__);
            $args = func_get_args();
            $dumpSettings = $args[0];
            $ret = "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;" . PHP_EOL .
                "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;" . PHP_EOL .
                "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;" . PHP_EOL .
                "/*!40101 SET NAMES " . $this->get_connection_charset($wpdb) . " */;" . PHP_EOL;

            if (false === $dumpSettings['skip-tz-utc']) {
                $ret .= "/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;" . PHP_EOL .
                    "/*!40103 SET TIME_ZONE='+00:00' */;" . PHP_EOL;
            }

            $ret .= "/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;" . PHP_EOL .
                "/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;" . PHP_EOL .
                "/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;" . PHP_EOL .
                "/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;" . PHP_EOL .PHP_EOL;

            return $ret;
        }

        public function restore_parameters()
        {
            $this->check_parameters(func_num_args(), $expected_num_args = 1, __METHOD__);
            $args = func_get_args();
            $dumpSettings = $args[0];
            $ret = "";

            if (false === $dumpSettings['skip-tz-utc']) {
                $ret .= "/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;" . PHP_EOL;
            }

            $ret .= "/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;" . PHP_EOL .
                "/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;" . PHP_EOL .
                "/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;" . PHP_EOL .
                "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;" . PHP_EOL .
                "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;" . PHP_EOL .
                "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;" . PHP_EOL .
                "/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;" . PHP_EOL . PHP_EOL;

            return $ret;
        }

        /**
         * Check number of parameters passed to function, useful when inheriting.
         * Raise exception if unexpected.
         *
         * @param integer $num_args
         * @param integer $expected_num_args
         * @param string $method_name
         */
        private function check_parameters($num_args, $expected_num_args, $method_name)
        {
            if ( $num_args != $expected_num_args ) {
                throw new Exception("Unexpected parameter passed to $method_name");
            }
            return;
        }

        public function query($string)
        {
            return $this->dbHandler->get_results($string, ARRAY_A);
        }

        public function exec($string)
        {
            return $this->dbHandler->get_results($string, ARRAY_A);
        }

        public function quote($value)
        {
            $search = array("\x00", "\x0a", "\x0d", "\x1a");
            $replace = array('\0', '\n', '\r', '\Z');
            $value=str_replace('\\', '\\\\', $value);
            $value=str_replace('\'', '\\\'', $value);
            $value= "'" . str_replace($search, $replace, $value) . "'";
            return $value;
        }

        public function closeCursor($resultSet)
        {
            $this->dbHandler->flush();
        }
    }
}
else
{
    class WPvividTypeAdapterAdditionalWpdb extends TypeAdapterFactory
    {

        private $dbHandler = null;

        // Numerical Mysql types
        public $mysqlTypes = array(
            'numerical' => array(
                'bit',
                'tinyint',
                'smallint',
                'mediumint',
                'int',
                'integer',
                'bigint',
                'real',
                'double',
                'float',
                'decimal',
                'numeric'
            ),
            'blob' => array(
                'tinyblob',
                'blob',
                'mediumblob',
                'longblob',
                'binary',
                'varbinary',
                'bit',
                'geometry', /* http://bugs.mysql.com/bug.php?id=43544 */
                'point',
                'linestring',
                'polygon',
                'multipoint',
                'multilinestring',
                'multipolygon',
                'geometrycollection',
            )
        );

        public function __construct ($dbHandler)
        {
            $this->dbHandler = $dbHandler;
        }

        public function connect($host,$dbname,$user,$pass,$init_commands=array())
        {
            $dbuser     = defined( 'DB_USER' ) ? $user : '';
            $dbpassword = defined( 'DB_PASSWORD' ) ? $pass : '';
            $dbname     = defined( 'DB_NAME' ) ? $dbname : '';
            $dbhost     = defined( 'DB_HOST' ) ? $host : '';

            $this->dbHandler = new wpdb( $dbuser, $dbpassword, $dbname, $dbhost );
        }

        public function databases()
        {
            $this->check_parameters(func_num_args(), $expected_num_args = 1, __METHOD__);
            $args = func_get_args();
            $databaseName = $args[0];

            $resultSet = $this->query("SHOW VARIABLES LIKE 'character_set_database';");
            $characterSet = $resultSet->fetchColumn(1);
            $resultSet->closeCursor();

            $resultSet = $this->query("SHOW VARIABLES LIKE 'collation_database';");
            $collationDb = $resultSet->fetchColumn(1);
            $resultSet->closeCursor();
            $ret = "";

            $ret .= "CREATE DATABASE /*!32312 IF NOT EXISTS*/ `${databaseName}`".
                " /*!40100 DEFAULT CHARACTER SET ${characterSet} " .
                " COLLATE ${collationDb} */;" . PHP_EOL . PHP_EOL .
                "USE `${databaseName}`;" . PHP_EOL . PHP_EOL;

            return $ret;
        }

        public function show_create_table($tableName)
        {
            return "SHOW CREATE TABLE `$tableName`";
        }

        public function show_create_view($viewName)
        {
            return "SHOW CREATE VIEW `$viewName`";
        }

        public function show_create_trigger($triggerName)
        {
            return "SHOW CREATE TRIGGER `$triggerName`";
        }

        public function show_create_procedure($procedureName)
        {
            return "SHOW CREATE PROCEDURE `$procedureName`";
        }

        public function show_create_event($eventName)
        {
            return "SHOW CREATE EVENT `$eventName`";
        }

        public function create_table( $row, $dumpSettings )
        {

            if ( !isset($row['Create Table']) ) {
                throw new Exception("Error getting table code, unknown output");
            }

            //$createTable = str_replace('\'0000-00-00 00:00:00\'','\'1999-01-01 00:00:00\'',$row['Create Table']);
            $createTable = $row['Create Table'];

            if ( $dumpSettings['reset-auto-increment'] ) {
                $match = "/AUTO_INCREMENT=[0-9]+/s";
                $replace = "";
                $createTable = preg_replace($match, $replace, $createTable);
            }

            $ret = "/*!40101 SET @saved_cs_client     = @@character_set_client */;" . PHP_EOL .
                "/*!40101 SET character_set_client = " . $dumpSettings['default-character-set'] . " */;" . PHP_EOL .
                $createTable . ";" . PHP_EOL .
                "/*!40101 SET character_set_client = @saved_cs_client */;" . PHP_EOL .
                PHP_EOL;
            return $ret;
        }

        public function create_view($row)
        {
            $ret = "";
            if (!isset($row['Create View'])) {
                throw new Exception("Error getting view structure, unknown output");
            }

            $triggerStmt = $row['Create View'];

            $triggerStmtReplaced1 = str_replace(
                "CREATE ALGORITHM",
                "/*!50001 CREATE ALGORITHM",
                $triggerStmt
            );
            $triggerStmtReplaced2 = str_replace(
                " DEFINER=",
                " */" . PHP_EOL . "/*!50013 DEFINER=",
                $triggerStmtReplaced1
            );
            $triggerStmtReplaced3 = str_replace(
                " VIEW ",
                " */" . PHP_EOL . "/*!50001 VIEW ",
                $triggerStmtReplaced2
            );
            if (false === $triggerStmtReplaced1 ||
                false === $triggerStmtReplaced2 ||
                false === $triggerStmtReplaced3) {
                $triggerStmtReplaced = $triggerStmt;
            } else {
                $triggerStmtReplaced = $triggerStmtReplaced3 . " */;";
            }

            $ret .= $triggerStmtReplaced . PHP_EOL . PHP_EOL;
            return $ret;
        }

        public function create_trigger($row)
        {
            $ret = "";
            if (!isset($row['SQL Original Statement'])) {
                throw new Exception("Error getting trigger code, unknown output");
            }

            $triggerStmt = $row['SQL Original Statement'];
            $triggerStmtReplaced = str_replace(
                "CREATE DEFINER",
                "/*!50003 CREATE*/ /*!50017 DEFINER",
                $triggerStmt
            );
            $triggerStmtReplaced = str_replace(
                " TRIGGER",
                "*/ /*!50003 TRIGGER",
                $triggerStmtReplaced
            );
            if ( false === $triggerStmtReplaced ) {
                $triggerStmtReplaced = $triggerStmt . " /* ";
            }

            $ret .= "DELIMITER ;;" . PHP_EOL .
                $triggerStmtReplaced . " */ ;;" . PHP_EOL .
                "DELIMITER ;" . PHP_EOL . PHP_EOL;
            return $ret;
        }

        public function create_procedure($row, $dumpSettings)
        {
            $ret = "";
            if (!isset($row['Create Procedure'])) {
                throw new Exception("Error getting procedure code, unknown output. " .
                    "Please check 'https://bugs.mysql.com/bug.php?id=14564'");
            }
            $procedureStmt = $row['Create Procedure'];

            $ret .= "/*!50003 DROP PROCEDURE IF EXISTS `" .
                $row['Procedure'] . "` */;" . PHP_EOL .
                "/*!40101 SET @saved_cs_client     = @@character_set_client */;" . PHP_EOL .
                "/*!40101 SET character_set_client = " . $dumpSettings['default-character-set'] . " */;" . PHP_EOL .
                "DELIMITER ;;" . PHP_EOL .
                $procedureStmt . " ;;" . PHP_EOL .
                "DELIMITER ;" . PHP_EOL .
                "/*!40101 SET character_set_client = @saved_cs_client */;" . PHP_EOL . PHP_EOL;

            return $ret;
        }

        public function create_event($row)
        {
            $ret = "";
            if ( !isset($row['Create Event']) ) {
                throw new Exception("Error getting event code, unknown output. " .
                    "Please check 'http://stackoverflow.com/questions/10853826/mysql-5-5-create-event-gives-syntax-error'");
            }
            $eventName = $row['Event'];
            $eventStmt = $row['Create Event'];
            $sqlMode = $row['sql_mode'];

            $eventStmtReplaced = str_replace(
                "CREATE DEFINER",
                "/*!50106 CREATE*/ /*!50117 DEFINER",
                $eventStmt
            );
            $eventStmtReplaced = str_replace(
                " EVENT ",
                "*/ /*!50106 EVENT ",
                $eventStmtReplaced
            );

            if ( false === $eventStmtReplaced ) {
                $eventStmtReplaced = $eventStmt . " /* ";
            }

            $ret .= "/*!50106 SET @save_time_zone= @@TIME_ZONE */ ;" . PHP_EOL .
                "/*!50106 DROP EVENT IF EXISTS `" . $eventName . "` */;" . PHP_EOL .
                "DELIMITER ;;" . PHP_EOL .
                "/*!50003 SET @saved_cs_client      = @@character_set_client */ ;;" . PHP_EOL .
                "/*!50003 SET @saved_cs_results     = @@character_set_results */ ;;" . PHP_EOL .
                "/*!50003 SET @saved_col_connection = @@collation_connection */ ;;" . PHP_EOL .
                "/*!50003 SET character_set_client  = utf8 */ ;;" . PHP_EOL .
                "/*!50003 SET character_set_results = utf8 */ ;;" . PHP_EOL .
                "/*!50003 SET collation_connection  = utf8_general_ci */ ;;" . PHP_EOL .
                "/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;;" . PHP_EOL .
                "/*!50003 SET sql_mode              = '" . $sqlMode . "' */ ;;" . PHP_EOL .
                "/*!50003 SET @saved_time_zone      = @@time_zone */ ;;" . PHP_EOL .
                "/*!50003 SET time_zone             = 'SYSTEM' */ ;;" . PHP_EOL .
                $eventStmtReplaced . " */ ;;" . PHP_EOL .
                "/*!50003 SET time_zone             = @saved_time_zone */ ;;" . PHP_EOL .
                "/*!50003 SET sql_mode              = @saved_sql_mode */ ;;" . PHP_EOL .
                "/*!50003 SET character_set_client  = @saved_cs_client */ ;;" . PHP_EOL .
                "/*!50003 SET character_set_results = @saved_cs_results */ ;;" . PHP_EOL .
                "/*!50003 SET collation_connection  = @saved_col_connection */ ;;" . PHP_EOL .
                "DELIMITER ;" . PHP_EOL .
                "/*!50106 SET TIME_ZONE= @save_time_zone */ ;" . PHP_EOL . PHP_EOL;
            // Commented because we are doing this in restore_parameters()
            // "/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;" . PHP_EOL . PHP_EOL;

            return $ret;
        }

        public function show_tables()
        {
            $this->check_parameters(func_num_args(), $expected_num_args = 1, __METHOD__);
            $args = func_get_args();
            return "SELECT TABLE_NAME AS tbl_name " .
                "FROM INFORMATION_SCHEMA.TABLES " .
                "WHERE TABLE_TYPE='BASE TABLE' AND TABLE_SCHEMA='${args[0]}'";
        }

        public function show_views()
        {
            $this->check_parameters(func_num_args(), $expected_num_args = 1, __METHOD__);
            $args = func_get_args();
            return "SELECT TABLE_NAME AS tbl_name " .
                "FROM INFORMATION_SCHEMA.TABLES " .
                "WHERE TABLE_TYPE='VIEW' AND TABLE_SCHEMA='${args[0]}'";
        }

        public function show_triggers()
        {
            $this->check_parameters(func_num_args(), $expected_num_args = 1, __METHOD__);
            $args = func_get_args();
            return "SHOW TRIGGERS FROM `${args[0]}`;";
        }

        public function show_columns()
        {
            $this->check_parameters(func_num_args(), $expected_num_args = 1, __METHOD__);
            $args = func_get_args();
            return "SHOW COLUMNS FROM `${args[0]}`;";
        }

        public function show_procedures()
        {
            $this->check_parameters(func_num_args(), $expected_num_args = 1, __METHOD__);
            $args = func_get_args();
            return "SELECT SPECIFIC_NAME AS procedure_name " .
                "FROM INFORMATION_SCHEMA.ROUTINES " .
                "WHERE ROUTINE_TYPE='PROCEDURE' AND ROUTINE_SCHEMA='${args[0]}'";
        }

        /**
         * Get query string to ask for names of events from current database.
         *
         * @param string Name of database
         * @return string
         */
        public function show_events()
        {
            $this->check_parameters(func_num_args(), $expected_num_args = 1, __METHOD__);
            $args = func_get_args();
            return "SELECT EVENT_NAME AS event_name " .
                "FROM INFORMATION_SCHEMA.EVENTS " .
                "WHERE EVENT_SCHEMA='${args[0]}'";
        }

        public function setup_transaction()
        {
            return "SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ";
        }

        public function start_transaction()
        {
            return "START TRANSACTION";
        }

        public function commit_transaction()
        {
            return "COMMIT";
        }

        public function lock_table()
        {
            $this->check_parameters(func_num_args(), $expected_num_args = 1, __METHOD__);
            $args = func_get_args();
            return $this->dbHandler->get_results("LOCK TABLES `${args[0]}` READ LOCAL",ARRAY_A);

        }

        public function unlock_table()
        {
            return $this->dbHandler->get_results("UNLOCK TABLES",ARRAY_A);
        }

        public function start_add_lock_table()
        {
            $this->check_parameters(func_num_args(), $expected_num_args = 1, __METHOD__);
            $args = func_get_args();
            return "LOCK TABLES `${args[0]}` WRITE;" . PHP_EOL;
        }

        public function end_add_lock_table()
        {
            return "UNLOCK TABLES;" . PHP_EOL;
        }

        public function start_add_disable_keys()
        {
            $this->check_parameters(func_num_args(), $expected_num_args = 1, __METHOD__);
            $args = func_get_args();
            return "/*!40000 ALTER TABLE `${args[0]}` DISABLE KEYS */;" .
                PHP_EOL;
        }

        public function end_add_disable_keys()
        {
            $this->check_parameters(func_num_args(), $expected_num_args = 1, __METHOD__);
            $args = func_get_args();
            return "/*!40000 ALTER TABLE `${args[0]}` ENABLE KEYS */;" .
                PHP_EOL;
        }

        public function start_disable_autocommit()
        {
            return "SET autocommit=0;" . PHP_EOL;
        }

        public function end_disable_autocommit()
        {
            return "COMMIT;" . PHP_EOL;
        }

        public function add_drop_database()
        {
            $this->check_parameters(func_num_args(), $expected_num_args = 1, __METHOD__);
            $args = func_get_args();
            return "/*!40000 DROP DATABASE IF EXISTS `${args[0]}`*/;" .
                PHP_EOL . PHP_EOL;
        }

        public function add_drop_trigger()
        {
            $this->check_parameters(func_num_args(), $expected_num_args = 1, __METHOD__);
            $args = func_get_args();
            return "DROP TRIGGER IF EXISTS `${args[0]}`;" . PHP_EOL;
        }

        public function drop_table()
        {
            $this->check_parameters(func_num_args(), $expected_num_args = 1, __METHOD__);
            $args = func_get_args();
            return "DROP TABLE IF EXISTS `${args[0]}`;" . PHP_EOL;
        }

        public function drop_view()
        {
            $this->check_parameters(func_num_args(), $expected_num_args = 1, __METHOD__);
            $args = func_get_args();
            return "DROP TABLE IF EXISTS `${args[0]}`;" . PHP_EOL .
                "/*!50001 DROP VIEW IF EXISTS `${args[0]}`*/;" . PHP_EOL;
        }

        public function getDatabaseHeader()
        {
            $this->check_parameters(func_num_args(), $expected_num_args = 1, __METHOD__);
            $args = func_get_args();
            return "--" . PHP_EOL .
                "-- Current Database: `${args[0]}`" . PHP_EOL .
                "--" . PHP_EOL . PHP_EOL;
        }

        /**
         * Decode column metadata and fill info structure.
         * type, is_numeric and is_blob will always be available.
         *
         * @param array $colType Array returned from "SHOW COLUMNS FROM tableName"
         * @return array
         */
        public function parseColumnType($colType)
        {
            $colInfo = array();
            $colParts = explode(" ", $colType['Type']);

            if($fparen = strpos($colParts[0], "("))
            {
                $colInfo['type'] = substr($colParts[0], 0, $fparen);
                $colInfo['length']  = str_replace(")", "", substr($colParts[0], $fparen+1));
                $colInfo['attributes'] = isset($colParts[1]) ? $colParts[1] : NULL;
            }
            else
            {
                $colInfo['type'] = $colParts[0];
            }
            $colInfo['is_numeric'] = in_array($colInfo['type'], $this->mysqlTypes['numerical']);
            $colInfo['is_blob'] = in_array($colInfo['type'], $this->mysqlTypes['blob']);
            // for virtual 'Extra' -> "STORED GENERATED"
            $colInfo['is_virtual'] = strpos($colType['Extra'], "STORED GENERATED") === false ? false : true;

            return $colInfo;
        }

        public function get_connection_charset($wpdb = null) {
            if (null === $wpdb) {
                global $wpdb;
            }

            $charset = (defined('DB_CHARSET') && DB_CHARSET) ? DB_CHARSET : 'utf8mb4';

            if (method_exists($wpdb, 'determine_charset')) {
                $charset_collate = $wpdb->determine_charset($charset, '');
                if (!empty($charset_collate['charset'])) $charset = $charset_collate['charset'];
            }

            return $charset;
        }

        public function backup_parameters()
        {
            global $wpdb;
            $this->check_parameters(func_num_args(), $expected_num_args = 1, __METHOD__);
            $args = func_get_args();
            $dumpSettings = $args[0];
            $ret = "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;" . PHP_EOL .
                "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;" . PHP_EOL .
                "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;" . PHP_EOL .
                "/*!40101 SET NAMES " . $this->get_connection_charset($wpdb) . " */;" . PHP_EOL;

            if (false === $dumpSettings['skip-tz-utc']) {
                $ret .= "/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;" . PHP_EOL .
                    "/*!40103 SET TIME_ZONE='+00:00' */;" . PHP_EOL;
            }

            $ret .= "/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;" . PHP_EOL .
                "/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;" . PHP_EOL .
                "/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;" . PHP_EOL .
                "/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;" . PHP_EOL .PHP_EOL;

            return $ret;
        }

        public function restore_parameters()
        {
            $this->check_parameters(func_num_args(), $expected_num_args = 1, __METHOD__);
            $args = func_get_args();
            $dumpSettings = $args[0];
            $ret = "";

            if (false === $dumpSettings['skip-tz-utc']) {
                $ret .= "/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;" . PHP_EOL;
            }

            $ret .= "/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;" . PHP_EOL .
                "/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;" . PHP_EOL .
                "/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;" . PHP_EOL .
                "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;" . PHP_EOL .
                "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;" . PHP_EOL .
                "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;" . PHP_EOL .
                "/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;" . PHP_EOL . PHP_EOL;

            return $ret;
        }

        /**
         * Check number of parameters passed to function, useful when inheriting.
         * Raise exception if unexpected.
         *
         * @param integer $num_args
         * @param integer $expected_num_args
         * @param string $method_name
         */
        private function check_parameters($num_args, $expected_num_args, $method_name)
        {
            if ( $num_args != $expected_num_args ) {
                throw new Exception("Unexpected parameter passed to $method_name");
            }
            return;
        }

        public function query($string)
        {
            return $this->dbHandler->get_results($string, ARRAY_A);
        }

        public function exec($string)
        {
            return $this->dbHandler->get_results($string, ARRAY_A);
        }

        public function quote($value)
        {
            $search = array("\x00", "\x0a", "\x0d", "\x1a");
            $replace = array('\0', '\n', '\r', '\Z');
            $value=str_replace('\\', '\\\\', $value);
            $value=str_replace('\'', '\\\'', $value);
            $value= "'" . str_replace($search, $replace, $value) . "'";
            return $value;
        }

        public function closeCursor($resultSet)
        {
            $this->dbHandler->flush();
        }
    }
}