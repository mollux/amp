<?php
namespace Amp\Command;

use Amp\Instance;
use Amp\Util\Filesystem;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

class SqlCommand extends ContainerAwareCommand {

  /**
   * @param \Amp\Application $app
   * @param string|null $name
   */
  public function __construct(\Amp\Application $app, $name = NULL) {
    $this->fs = new Filesystem();
    parent::__construct($app, $name);
  }

  protected function configure() {
    $this
      ->setName('sql')
      ->setDescription('Open the SQL CLI')
      ->addOption('root', 'r', InputOption::VALUE_REQUIRED, 'The local path to the document root', getcwd())
      ->addOption('name', 'N', InputOption::VALUE_REQUIRED, 'Brief technical identifier for the service', '');
  }

  protected function initialize(InputInterface $input, OutputInterface $output) {
    $root = $this->fs->toAbsolutePath($input->getOption('root'));
    if (!$this->fs->exists($root)) {
      throw new \Exception("Failed to locate root: " . $root);
    }
    else {
      $input->setOption('root', $root);
    }
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $instance = $this->getContainer()->get('instances')->find(Instance::makeId($input->getOption('root'), $input->getOption('name')));
    if (!$instance) {
      throw new \Exception("Failed to locate instance: " . Instance::makeId($input->getOption('root'), $input->getOption('name')));
    }

    $process = proc_open(
      "mysql " . $instance->getDatasource()->toMySQLArguments(),
      array(
        0 => STDIN,
        1 => STDOUT,
        2 => STDERR,
      ),
      $pipes,
      $input->getOption('root')
    );
    return proc_close($process);
  }

}
