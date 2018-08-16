<?php declare(strict_types = 1);

namespace App\Commands;

use InvalidArgumentException;
use Nette\Utils\Json;
use stdClass;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class MergeTypeConfiguratorCommand extends BaseCommand
{

	/** @var string */
	protected static $defaultName = 'configurator:merge-type';

	protected function configure(): void
	{
		$this->setName(self::$defaultName);
		$this->addArgument('do', InputArgument::REQUIRED, '[enable|disable] merge type');
		$this->addArgument('merge-type', InputArgument::REQUIRED, '[squash|rebase|merge]');
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

		$mergeType = $input->getArgument('merge-type');
		if (!in_array($mergeType, ['squash', 'rebase', 'merge'], true)) {
			throw new InvalidArgumentException('Argument "merge-type" could be only "squash", "rebase" or "merge"');
		}

		$owner = $input->getArgument('owner');
		$repository = $input->getOption('repository');

		if ($repository !== null) {
			$data = $this->getRepository($owner, $repository);
			$this->patchMergeType($data, $do, $mergeType, $owner, $repository);
		} else {
			$datas = $this->getRepositories($owner);
			foreach ($datas as $data) {
				$this->patchMergeType($data, $do, $mergeType, $owner, $data->name);
			}
		}

		return 0;
	}

	private function patchMergeType(stdClass $data, bool $enable, string $mergeType, string $owner, string $repository): void
	{
		if ($data->archived) {
			return; // Cannot modify archived repository
		}

		if ($data->size === 0) {
			return; // Cannot modify empty repo (in that case, because wtf github)
		}

		$body = [
			'name' => $data->name,
		];

		if ($mergeType === 'squash') {
			$body['allow_squash_merge'] = $enable;
		} elseif ($mergeType === 'rebase') {
			$body['allow_rebase_merge'] = $enable;
		} else {
			$body['allow_merge_commit'] = $enable;
		}

		$url = sprintf('%s/repos/%s/%s?%s', $this->apiUrl, $owner, $repository, $this->apiKey);
		$this->client->patch($url, [
			'headers' => [
				'Accept' => 'application/vnd.github.v3+json',
			],
			'body' => Json::encode($data),
		]);
	}

}
