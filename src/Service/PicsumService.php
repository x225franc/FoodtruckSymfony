<?php 

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class PicsumService
{
    private $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function fetchPicsumImages(int $limit = 100): array
    {
        $response = $this->httpClient->request('GET', 'https://picsum.photos/v2/list', [
            'query' => [
                'limit' => $limit,
            ],
        ]);

        $data = $response->toArray();

        $imageUrls = [];
        foreach ($data as $image) {
            $imageUrls[] = $image['download_url'];
        }

        return $imageUrls;
    }
}