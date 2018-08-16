<?php declare(strict_types = 1);

namespace App\Commands;

use Nette\Utils\Json;
use stdClass;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ReplaceDefaultLabelsConfiguratorCommand extends BaseCommand
{

	/** @var string */
	protected static $defaultName = 'configurator:replace-default-labels';

	protected function configure(): void
	{
		$this->setName(self::$defaultName);
		$this->addArgument('owner', InputArgument::REQUIRED, 'Organization or user name');
		$this->addOption('repository', 'r', InputOption::VALUE_REQUIRED, 'If repository is not specified so all repositories in organization are modified.', null);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$owner = $input->getArgument('owner');
		$repository = $input->getOption('repository');

		if ($repository !== null) {
			$labels = $this->getLabels($owner, $repository);
			$this->addLabels($labels, $owner, $repository);
			$this->patchLabels($labels, $owner, $repository);
			$this->deleteLabels($labels, $owner, $repository);
		} else {
			$datas = $this->getRepositories($owner);
			foreach ($datas as $data) {
				if ($data->archived) {
					continue; // Cannot modify archived repository
				}

				$labels = $this->getLabels($owner, $data->name);
				$this->addLabels($labels, $owner, $data->name);
				$this->patchLabels($labels, $owner, $data->name);
				$this->deleteLabels($labels, $owner, $data->name);
			}
		}

		return 0;
	}

	/**
	 * @return stdClass[]
	 */
	private function getLabels(string $owner, string $repository): array
	{
		$url = sprintf('%s/repos/%s/%s/labels?%s', $this->apiUrl, $owner, $repository, $this->apiKey);
		$response = $this->client->get($url, [
			'headers' => [
				'Accept' => 'application/vnd.github.v3+json',
			],
		]);
		return Json::decode($response->getBody()->getContents());
	}

	/**
	 * @param stdClass[] $labels
	 */
	private function addLabels(array $labels, string $owner, string $repository): void
	{
		$newLabels = [
			'docs' => [
				'name' => 'docs',
				'description' => 'Documentation',
				'color' => 'e4e669',
			],
			'need more info' => [
				'name' => 'need more info',
				'description' => 'We don\'t fully understand the problem.',
				'color' => '7057ff',
			],
		];

		foreach ($labels as $label) {
			if (in_array($label->name, array_keys($newLabels), true)) {
				unset($newLabels[$label->name]);
			}
		}

		$url = sprintf('%s/repos/%s/%s/labels?%s', $this->apiUrl, $owner, $repository, $this->apiKey);
		foreach ($newLabels as $newLabel) {
			$this->client->post($url, [
				'headers' => [
					'Accept' => 'application/vnd.github.v3+json',
				],
				'body' => Json::encode($newLabel),
			]);
		}
	}

	/**
	 * @param stdClass[] $labels
	 */
	private function patchLabels(array $labels, string $owner, string $repository): void
	{
		foreach ($labels as $key => $label) {
			if ($label->name !== 'enhancement') {
				continue;
			}

			$label->name = 'feature';
			$label->description = 'New feature';
			$url = sprintf('%s/repos/%s/%s/labels/%s?%s', $this->apiUrl, $owner, $repository, 'enhancement', $this->apiKey);
			$this->client->patch($url, [
				'headers' => [
					'Accept' => 'application/vnd.github.v3+json',
				],
				'body' => Json::encode($label),
			]);
		}
	}

	/**
	 * @param stdClass[] $labels
	 */
	private function deleteLabels(array $labels, string $owner, string $repository): void
	{
		foreach ($labels as $key => $label) {
			if (!in_array($label->name, ['good first issue', 'help wanted', 'invalid', 'wontfix'], true)) {
				continue;
			}

			$url = sprintf('%s/repos/%s/%s/labels/%s?%s', $this->apiUrl, $owner, $repository, $label->name, $this->apiKey);
			$this->client->delete($url, [
				'headers' => [
					'Accept' => 'application/vnd.github.v3+json',
				],
			]);
		}
	}

}
