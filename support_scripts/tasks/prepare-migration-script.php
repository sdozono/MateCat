<?php 

$root = realpath(dirname(__FILE__) . '/../../');
include_once $root . "/inc/Bootstrap.php";
Bootstrap::start();

$db = Database::obtain(INIT::$DB_SERVER, INIT::$DB_USER, INIT::$DB_PASS, INIT::$DB_DATABASE);
$db->debug = false;
$db->connect();

# Generate and insert a new enabled api keys pair for
# the give email, and print the result on screen .

function usage() {
  echo "Usage: \n
--migrations=[comma_separated_timestamps]
";

  exit; 
}

$options = getopt( 'h', array( 'migrations:') );

if (array_key_exists('h', $options))          usage() ;
if (empty($options['migrations']))            usage() ;

$migrations = explode(',', $options['migrations']);

# find corresponding classes
#
$filenames = array(); 
foreach($migrations as $migration) {
  $name = $root . "/migrations/$migration*" ; 
  $file = glob( $name ); 
  if (empty($file)) {
    echo "Migration for pattern $name does not exist"; 
    die();
  }

  $filenames[$migration] = $file[0];

}

include $root . '/vendor/autoload.php';

$createSchemaTable = <<<EOF
-- phinxlog table

CREATE TABLE IF NOT EXISTS `phinxlog` (
  `version` bigint(20) NOT NULL,
  `start_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `end_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

EOF;

echo $createSchemaTable ; 

foreach($filenames as $migration => $filename) {
  include $filename ; 

  list($skip, $name) = preg_split('/_/', $filename, 2);

  $name = str_replace('.php', '', $name); 

  $class = Phinx\Migration\Util::mapFileNameToClassName($name); 
  $instance = new $class($migration); 
  $start_date = $stop_date = date('Y-m-d H:m:s');

  echo "\n"; 
  echo "-- start migration number $migration \n";
  echo $instance->sql_up; 
  echo "\n";

  echo <<<EOF

INSERT INTO phinxlog VALUES ( $migration, '$start_date', '$stop_date'); 

EOF;

  echo "-- end migrations number $migration \n";
  
}


exit; 
