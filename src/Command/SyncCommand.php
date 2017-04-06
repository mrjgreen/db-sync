<?php namespace DbSync\Command;

use Database\Connectors\ConnectionFactory;
use DbSync\ColumnConfiguration;
use DbSync\DbSync;
use DbSync\Hash\CrcHash;
use DbSync\Hash\HashAbstract;
use DbSync\Hash\Md5Hash;
use DbSync\Hash\ShaHash;
use DbSync\Table;
use DbSync\Transfer\Transfer;
use DbSync\WhereClause;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class SyncCommand extends Command
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var Questioner
     */
    private $questioner;

    protected function configure()
    {
        if(function_exists('posix_getpwuid'))
        {
            $currentUserInfo = posix_getpwuid(posix_geteuid());
            $currentUser = $currentUserInfo['name'];
        }else{
            $currentUser = null;
        }

        $this
            ->setName('db-sync')
            ->setDescription('Sync a mysql database table from one host to another using an efficient checksum algorithm to find differences.')
            ->addArgument('source', InputArgument::REQUIRED, 'The source host ip to use.')
            ->addArgument('target', InputArgument::REQUIRED, 'The target host ip to use.')
            ->addArgument('table', InputArgument::REQUIRED, 'The fully qualified database table to sync.')
            ->addOption('block-size','b', InputOption::VALUE_REQUIRED, 'The maximum block to use for when comparing.', 1024)
            ->addOption('charset',null, InputOption::VALUE_REQUIRED, 'The charset to use for database connections.', 'utf8')
            ->addOption('columns','c', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Columns to sync - all columns not "ignored" will be included if not specified. Primary key columns will be included automatically.')
            ->addOption('comparison','C', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Columns from the list of synced columns to use to create the hash - all columns not "ignored" will be included if not specified. Primary key columns will be included automatically.')
            ->addOption('config-file','f', InputOption::VALUE_REQUIRED, 'A path to a config.ini file from which to read values.', 'dbsync.ini')
            ->addOption('delete', null, InputOption::VALUE_NONE, 'Remove rows from the target table that do not exist in the source.')
            ->addOption('execute','e', InputOption::VALUE_NONE, 'Perform the data write on non-matching blocks. Without this option only a dry run will be performed.')
            ->addOption('hash','H', InputOption::VALUE_REQUIRED, 'Specify the hash algorithm used to generate the comparison hash.', 'md5')
            ->addOption('help','h', InputOption::VALUE_NONE, 'Show this usage information.')
            ->addOption('ignore-columns','i', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Columns to ignore. Will not be copied or used to create the hash.')
            ->addOption('ignore-comparison','I', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Columns to ignore from the hash. Columns will still be copied.')
            ->addOption('password','p', InputOption::VALUE_OPTIONAL, 'The password for the specified user. Will be solicited on the tty if not given.')
            ->addOption('user','u', InputOption::VALUE_REQUIRED, 'The name of the user to connect with.', $currentUser)
            ->addOption('transfer-size','s', InputOption::VALUE_REQUIRED, 'The maximum copy size to use for when comparing.', 8)
            ->addOption('target.user',null , InputOption::VALUE_REQUIRED, 'The name of the user to connect to the target host with if different to the source.')
            ->addOption('target.table',null , InputOption::VALUE_REQUIRED, 'The name of the table on the target host if different to the source.')
            ->addOption('target.password',null , InputOption::VALUE_REQUIRED, 'The password for the target host if the target user is specified. Will be solicited on the tty if not given.')
            ->addOption('where', null , InputOption::VALUE_REQUIRED, 'A where clause to apply to the tables.')
            ->addOption('verbose', 'v' , InputOption::VALUE_NONE, 'Enable verbose output.')
            ->addOption('quiet', 'q' , InputOption::VALUE_NONE, 'Disable output, overrides "verbose" option.')
        ;
    }

    /**
     * @param $option
     * @param $shortOption
     * @param null $message
     * @return mixed|string
     */
    protected function getPassword($option, $shortOption, $message = null)
    {
        $password = $this->input->getOption($option);

        $rawOptions = ["--$option"];
        $shortOption and $rawOptions[] = "-$shortOption";

        if(!$password && $this->input->hasParameterOption($rawOptions))
        {
            $password = $this->questioner->secret($message ?: "Enter your password: ");
        }

        return $password;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        if($input->getOption('verbose'))
        {
            $this->output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
        }else {
            $this->output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
        }

        if($input->getOption('quiet'))
        {
          $this->output->setVerbosity(OutputInterface::VERBOSITY_QUIET);
        }

        $this->input = $input;

        if(($config = $this->input->getOption('config-file')) && is_file($config)){

            $this->output->writeln("Reading ini file '$config'");
            foreach(parse_ini_file($config) as $name => $value){
                $this->input->setOption($name, $value);
            }
        }

        $this->questioner = new Questioner($input, $output, new QuestionHelper());

        $this->fire();
    }

    /**
     *
     */
    private function fire()
    {
        $charset = $this->input->getOption('charset');

        $user = $this->input->getOption('user');

        $password = $this->getPassword('password', 'p', "Enter password for local user '$user': ");

        if($remoteUser = $this->input->getOption('target.user'))
        {
            $remotePassword = $this->getPassword('target.password', null, "<info>Enter password for user '$remoteUser' on target host: </info>");
        }else{
            $remoteUser = $user;
            $remotePassword = $password;
        }

        $source = $this->createConnection($this->input->getArgument('source'), $user, $password, $charset);

        $target = $this->createConnection($this->input->getArgument('target'), $remoteUser, $remotePassword, $charset);

        list($sourceDatabase, $sourceTable) = $this->parseTableName($this->input->getArgument('table'));

        if($targetTableOption = $this->input->getOption('target.table'))
        {
            list($targetDatabase, $targetTable) = $this->parseTableName($targetTableOption);
        }else
        {
            $targetDatabase = $sourceDatabase;
            $targetTable = $sourceTable;
        }

        $logger = new ConsoleLogger($this->output);

        $dryRun = $this->input->getOption('execute') ? false : true;

        if($dryRun)
        {
            $logger->notice("Dry run only. No data will be written to target.");
        }

        $hash = HashAbstract::buildHashByName($this->input->getOption('hash'));

        $sync = new DbSync(new Transfer($hash, $this->input->getOption('block-size'), $this->input->getOption('transfer-size')));

        $sync->dryRun($dryRun);

        $sync->delete($this->input->getOption('delete'));

        $sync->setLogger($logger);

        $sourceTableObj = new Table($source, $sourceDatabase, $sourceTable);
        $destTableObj = new Table($target, $targetDatabase, $targetTable);

        if($where = $this->input->getOption('where'))
        {
            $sourceTableObj->setWhereClause(new WhereClause($where));
            $destTableObj->setWhereClause(new WhereClause($where));
        }

        $result = $sync->sync(
            $sourceTableObj,
            $destTableObj,
            new ColumnConfiguration($this->input->getOption('columns'), $this->input->getOption('ignore-columns')),
            new ColumnConfiguration($this->input->getOption('comparison'), $this->input->getOption('ignore-comparison'))
        );

        $logger->notice(json_encode($result->toArray()));
    }

    private function parseTableName($name)
    {
        return explode('.', $name, 2);
    }

    private function parseHostPort($host)
    {
        $parts = explode(':', $host, 2);

        isset($parts[1]) or $parts[1] = 3306;

        return $parts;
    }

    private function createConnection($host, $user, $password, $charset)
    {
        list($host, $port) = $this->parseHostPort($host);

        return (new ConnectionFactory())->make([
            'host'      => $host,
            'port'      => $port,
            'username'  => $user,
            'password'  => $password,
            'charset'   => $charset,
            'collation' => 'utf8_general_ci',
            'driver'    => 'mysql',

            'options' => [
                \PDO::ATTR_ERRMODE               => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE    => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES      => false,
            ]
        ]);
    }
}
