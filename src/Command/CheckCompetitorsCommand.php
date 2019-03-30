<?php

namespace App\Command;

use App\Entity\CompetitorCheck;
use App\Util\Competitors\CompetitorBooking;
use App\Util\Competitors\CompetitorException;
use DateTime;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CheckCompetitorsCommand extends Command
{
    protected static $defaultName = 'check:competitors';

    private $em;


    /**
     * CheckCompetitorsCommand constructor.
     * @param ObjectManager $em
     */
    public function __construct(ObjectManager $em)
    {
        $this->em = $em;
        parent::__construct();
    }


    protected function configure()
    {
        $this
            ->setDescription('Crawl all defined Competitors')
            ->addArgument('debug', InputArgument::OPTIONAL, 'Enable Debug');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $debug = $input->getArgument('debug') === 'y' ? true : false;

        /**
         * Table
         */
        $rows = array();
        $table = new Table($output);
        $table->setHeaders(['Hotel','Room', 'Price', 'Breakfast', 'Pax']);

        $competiors = $this->em->getRepository(CompetitorCheck::class)->findBy(array(
            'isActive' => true,
        ));

        $ci = new DateTime('2019-04-01');
        $pax = 1;
        $result = array();

        $debug ? $io->success('Started Booking.com Crawl. Start: '.$ci->format('d.m.Y').' | End: '.$co->format('d.m.Y')) : null;

        foreach ($competiors as $slug) {

            $debug ? $io->newLine(): null;
            $debug ? $io->text('Crawling <fg=green>'.$slug->getName().'</>') : null;

            try {
                $crawl = CompetitorBooking::crawl_hotel($slug->getLink(), $ci, $pax);
                $result[] = $crawl;

                $incl = $crawl['isIncl'] === true ? 'incl' : 'excl';
                $price = $crawl['price'] === 'booked' ? '<fg=red>booked</>' : '<fg=green>'.$crawl['price'].'</>';
                $rows[] = array(
                    $slug->getName(),
                    $crawl['roomName'],
                    $price,
                    $incl,
                    $crawl['pax']
                );
                $debug ? $io->text('<fg=green>SUCCESS</>') : null;

            } catch (CompetitorException $e) {
                $debug ? $io->text('<fg=red>FAILED</> '.$e->getMessage()) : null;
            }
        }


        $table->setRows($rows);
        $table->render();

    }
}
