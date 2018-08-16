<?php declare(strict_types = 1);

namespace App\Commands;

use InvalidArgumentException;
use Nette\Utils\Json;
use stdClass;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class WikiConfiguratorCommand extends BaseCommand
{

	/** @var string */
	protected static $defaultName = 'configurator:wiki';

	protected function configure(): void
	{
		$this->setName(self::$defaultName);
		$this->addArgument('do', InputArgument::REQUIRED, '[enable|disable] wiki pages');
		$this->addArgument('owner', InputArgument::REQUIRED, 'Organization or user name');
		$this->addOption('repository', 'r', InputOption::VALUE_REQUIRED, 'If repository is not specified so all repositories in organization are modified.', null);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$do = $input->getArgument('do');
		if ($do === 'enable') {
			$do = true;
		} elseif ($do === 'disable') {
			$do = false;
		} else {
			throw new InvalidArgumentException('Argument "do" could be only "enable" or "disable"');
		}

		$owner = $input->getArgument('owner');
		$repository = $input->getOption('repository');

		if ($repository !== null) {
			$data = $this->getRepository($owner, $repository);
			$this->patchWiki($data, $do, $owner, $repository);
		} else {
			$datas = $this->getRepositories($owner);
			foreach ($datas as $data) {
				$this->patchWiki($data, $do, $owner, $data->name);
			}
		}

		return 0;
	}

	private function patchWiki(stdClass $data, bool $enable, string $owner, string $repository): void
	{
		if ($data->archived) {
			return; // Cannot modify archived repository
		}

		$url = sprintf('%s/repos/%s/%s?%s', $this->apiUrl, $owner, $repository, $this->apiKey);
		$this->client->patch($url, [
			'headers' => [
				'Accept' => 'application/vnd.github.v3+json',
			],
			'body' => Json::encode([
				'name' => $data->name,
				'has_wiki' => $enable,
			]),
		]);
	}

}
