<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Command;

use Bwlab\SyliusWishlistPlugin\Purge\ExpiredGuestWishlistPurger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'bwlab:wishlist:purge-expired', description: 'Deletes guest wishlists not seen for longer than the channel lifetime, keeping anonymous counts.')]
final class PurgeExpiredWishlistsCommand extends Command
{
    public function __construct(private readonly ExpiredGuestWishlistPurger $purger)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Only count what would be deleted.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $result = $this->purger->purge(new \DateTimeImmutable(), (bool) $input->getOption('dry-run'));

        foreach ($result->purgedByChannel as $code => $count) {
            $io->writeln(sprintf('%s: %d', $code, $count));
        }
        $io->success(sprintf($result->dryRun ? 'Dry run: %d guest wishlists would be deleted.' : 'Deleted %d guest wishlists.', $result->total()));

        return Command::SUCCESS;
    }
}
