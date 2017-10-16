<?php

namespace Tienvx\Bundle\MbtBundle\Command;

use Fhaculty\Graph\Edge\Directed;
use Fhaculty\Graph\Vertex;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Workflow\Workflow;
use Tienvx\Bundle\MbtBundle\Exception\ModelNotFoundException;
use Tienvx\Bundle\MbtBundle\Traversal\TraversalFactory;

class GenerateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('mbt:generate')
            ->setDescription('Generate test sequence from a model using a specific traversal.')
            ->setHelp('This command allows you to generate test sequence without actually testing the system.')
            ->addArgument('model', InputArgument::REQUIRED, 'The model to generate.')
            ->addOption('traversal', 't', InputOption::VALUE_OPTIONAL, 'The way to traverse through model to generate test sequence.', 'random(100,100)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $model = $input->getArgument('model');
        $workflow = $this->getContainer()->get("workflow.{$model}");
        if (!$workflow instanceof Workflow) {
            $message = sprintf('Can not load model by id "%s".', $model);
            throw new ModelNotFoundException($message);
        }

        $traversalOption = $input->getOption('traversal');
        $traversal = TraversalFactory::create($traversalOption);
        $traversal->setWorkflow($workflow);
        $traversal->init();

        $progress = new ProgressBar($output);
        $progress->setMessage(sprintf('Generating test sequence for model "%s"', $model));
        $progress->start($traversal->getMaxProgress());

        $testSequence = [];
        while ($traversal->hasNextStep()) {
            /** @var Vertex $vertex */
            /** @var Directed $edge */
            list($vertex, $edge) = $traversal->getNextStep();
            $testSequence[] = $vertex->getAttribute('text');
            $testSequence[] = $edge->getAttribute('text');
            $progress->setMessage($traversal->getCurrentProgressMessage());
            $progress->setProgress($traversal->getCurrentProgress());
        }
        $progress->finish();

        $output->writeln([
            '',
            sprintf('Generated test sequence for model "%s"', $model),
            '============',
        ]);

        $output->writeln($testSequence);
    }
}
