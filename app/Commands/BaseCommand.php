<?php declare(strict_types = 1);

namespace App\Commands;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Nette\Utils\Json;
use stdClass;
use Symfony\Component\Console\Command\Command;

abstract class BaseCommand extends Command
{

	/** @var string */
	protected $apiUrl = 'https://api.github.com';

	/** @var string */
	protected $apiKey;

	/** @var Client */
	protected $client;

	public function __construct(string $apiKey, Client $client)
	{
		parent::__construct();
		$this->apiKey = 'access_token=' . $apiKey;
		$this->client = $client;
	}

	protected function getRepository(string $owner, string $repository): stdClass
	{
		$url = sprintf('%s/repos/%s/%s?%s', $this->apiUrl, $owner, $repository, $this->apiKey);

		$response = $this->client->get($url, [
			'headers' => [
				'Accept' => 'application/vnd.github.v3+json',
			],
		]);

		return Json::decode($response->getBody()->getContents());
	}

	/**
	 * @return stdClass[]
	 */
	protected function getRepositories(string $owner): array
	{
		try {
			$url = sprintf('%s/orgs/%s/repos?%s', $this->apiUrl, $owner, $this->apiKey);
			$response = $this->client->get($url, [
				'headers' => [
					'Accept' => 'application/vnd.github.v3+json',
				],
			]);
		} catch (RequestException $e) {
			// If cannot get organization repos so try get user repos
			$url = sprintf('%s/users/%s/repos?%s', $this->apiUrl, $owner, $this->apiKey);
			$response = $this->client->get($url, [
				'headers' => [
					'Accept' => 'application/vnd.github.v3+json',
				],
			]);
		}

		return Json::decode($response->getBody());
	}

}
