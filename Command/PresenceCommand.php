<?php
/*
 * This file is part of the FreshCentrifugoBundle.
 *
 * (c) Artem Henvald <genvaldartem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Fresh\CentrifugoBundle\Command;

use Fresh\CentrifugoBundle\Service\Centrifugo;
use Fresh\CentrifugoBundle\Service\CentrifugoChecker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * PresenceCommand.
 *
 * @author Artem Henvald <genvaldartem@gmail.com>
 */
final class PresenceCommand extends Command
{
    protected static $defaultName = 'centrifugo:presence';

    /** @var Centrifugo */
    private $centrifugo;

    /** @var CentrifugoChecker */
    private $centrifugoChecker;

    /** @var string */
    private $channel;

    /**
     * @param Centrifugo        $centrifugo
     * @param CentrifugoChecker $centrifugoChecker
     */
    public function __construct(Centrifugo $centrifugo, CentrifugoChecker $centrifugoChecker)
    {
        $this->centrifugo = $centrifugo;
        $this->centrifugoChecker = $centrifugoChecker;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Get channel presence information')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument('channel', InputArgument::REQUIRED, 'Channel name'),
                ])
            )
            ->setHelp(
                <<<'EOT'
The <info>%command.name%</info> command allows to get channel presence information:

<info>%command.full_name%</info> <comment>channelAbc</comment>

Read more at https://centrifugal.github.io/centrifugo/server/http_api/#presence
EOT
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        try {
            $channel = (string) $input->getArgument('channel');
            $this->centrifugoChecker->assertValidChannelName($channel);
            $this->channel = $channel;
        } catch (\Exception $e) {
            throw new InvalidArgumentException($e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $data = $this->centrifugo->presence($this->channel);

            if (!empty($data['presence'])) {
                $io->title('Presence');

                foreach ($data['presence'] as $id => $info) {
                    $io->text(\sprintf('<info>%s</info>', $id));
                    $io->text(\sprintf('  ├ client: <comment>%s</comment>', $info['client']));
                    $io->text(\sprintf('  └ user: <comment>%s</comment>', $info['user']));
                }

                $io->newLine();
            } else {
                $io->success('NO DATA');
            }
        } catch (\Exception $e) {
            $io->error($e->getMessage());
        }

        return 0;
    }
}
