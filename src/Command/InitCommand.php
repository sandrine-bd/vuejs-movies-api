<?php


namespace App\Command;

use App\Entity\CustomList;
use App\Entity\CustomListEntry;
use App\Entity\Genre;
use App\Entity\Movie;
use App\Entity\People;
use App\Entity\Rating;
use App\Entity\Review;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:init',
    description: 'Initialize movies data from JSON dump',
)]
class InitCommand extends Command
{
    private array $genres = [];
    private array $people = [];
    private array $users = [];

    public function __construct(private EntityManagerInterface $em, private UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $file = fopen(__DIR__ . "/movies-db.json", 'r');

        ini_set('memory_limit', '2048M');

        $batchSize = 50;
        $count = 0;

        while (!feof($file)) {
            $line = fgets($file);
            if (!$line) continue;

            $movieData = json_decode($line, true);
            if (!$movieData) continue;

            $movie = (new Movie())
                ->setTitle($movieData["title"] ?? 'Untitled')
                ->setPlot($movieData["plot"] ?? null)
                ->setFullPlot($movieData["fullplot"] ?? null)
                ->setYear( is_array($movieData["year"]) ? $movieData["year"]['$numberInt'] : (int) $movieData["year"])
                ->setImdb([
                    "rating" => $movieData["imdb"]["rating"]["\$numberDouble"] ?? null,
                    "votes" => $movieData["imdb"]["votes"]["\$numberInt"] ?? null,
                    "id" => $movieData["imdb"]["id"]["\$numberInt"] ?? null
                ])
                ->setTomatoes([
                    "rating" => $movieData["tomatoes"]["viewer"]["rating"]["\$numberInt"] ?? null,
                    "votes" => $movieData["tomatoes"]["viewer"]["numReviews"]["\$numberInt"] ?? null,
                    "meter" => $movieData["tomatoes"]["viewer"]["meter"]["\$numberInt"] ?? null
                ])
                ->setPoster($movieData["poster"] ?? null)
                ->setCountries(array_values($movieData["countries"] ?? []));

            // Genres
            foreach ($movieData["genres"] ?? [] as $label) {
                if (!isset($this->genres[$label])) {
                    $genre = (new Genre())->setLabel($label);
                    $this->em->persist($genre);
                    $this->genres[$label] = $genre;
                }
                $movie->addGenre($this->genres[$label]);
            }

            // Cast
            foreach ($movieData["cast"] ?? [] as $name) {
                if (!isset($this->people[$name])) {
                    $person = (new People())->setFullName($name);
                    $this->em->persist($person);
                    $this->people[$name] = $person;
                }
                $movie->addCastMember($this->people[$name]);
            }

            // Directors
            foreach ($movieData["directors"] ?? [] as $name) {
                if (!isset($this->people[$name])) {
                    $person = (new People())->setFullName($name);
                    $this->em->persist($person);
                    $this->people[$name] = $person;
                }
                $movie->addDirector($this->people[$name]);
            }

            $this->em->persist($movie);
            $count++;

            // Flush & clear every batch
            if ($count % $batchSize === 0) {
                $this->em->flush();
                $this->em->clear();

                // Rehydrate maps with lightweight references (by ID)
                $this->rehydrateMaps();

                $io->writeln("Processed $count movies...");
            }
        }

        fclose($file);

        $defaultPassword = "password";

        for($i = 1 ; $i < 10 ; $i++){
            $user = (new User())
                ->setEmail("test$i@test.com")
                ->setUsername("TestUser$i");
            $user->setPassword($this->passwordHasher->hashPassword($user, $defaultPassword));
            $this->em->persist($user);
            $this->users[] = $user;
        }

        $movie = $this->em->getRepository(Movie::class)->findOneBy(["title" => "Inside Out"]);

        $rating1 = (new Rating())->setMovie($movie)->setNote(8)->setUser($this->users[0]);
        $this->em->persist($rating1);

        sleep(1);
        $review1 = (new Review())->setMovie($movie)->setContent("Pretty good :)")->setUser($this->users[0]);
        $this->em->persist($review1);

        sleep(1);
        $rating2 = (new Rating())->setMovie($movie)->setNote(5)->setUser($this->users[1]);
        $this->em->persist($rating2);

        sleep(1);
        $review2 = (new Review())->setMovie($movie)->setContent("Okay but not excellent")->setUser($this->users[1]);
        $this->em->persist($review2);

        sleep(1);
        $collection = (new CustomList())->setTitle("My top movies")->setUser($this->users[0]);
        $collectionEntry = (new CustomListEntry())->setMovie($movie)->setPosition(1);
        $this->em->persist($collectionEntry);
        $collection->addEntry($collectionEntry);
        $this->em->persist($collection);

        $this->users[0]->addFollower($this->users[1]);
        $this->users[0]->addFollower($this->users[2]);
        $this->users[0]->addFollower($this->users[3]);
        $this->users[0]->addFollow($this->users[1]);
        $this->users[0]->addFollow($this->users[2]);
        $this->users[0]->addFollow($this->users[3]);


        $this->em->flush(); // final flush

        $io->success("All movies imported successfully!");

        return Command::SUCCESS;
    }

    private function rehydrateMaps(): void
    {
        foreach ($this->genres as $label => $genre) {
            $this->genres[$label] = $this->em->getReference(Genre::class, $genre->getId());
        }
        foreach ($this->people as $name => $person) {
            $this->people[$name] = $this->em->getReference(People::class, $person->getId());
        }
    }
}
